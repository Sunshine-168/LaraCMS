<?php

namespace App\Http\Controllers\Admin\Routine;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Cms\SiteConfig;
use App\Services\Cms\SiteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ConfigController extends BaseAdminController
{
    public function __construct(
        \App\Services\Admin\AdminAuthService $auth,
        private readonly SiteService $siteService,
    ) {
        parent::__construct($auth);
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->update($request);
        }

        $locale = config('cms.default_locale', 'zh-cn');
        $groups = $this->groupMap();
        $configs = $this->configRows($locale);

        return $this->adminView('admin.routine.config.index', [
            'pageTitle' => '站点配置',
            'pageDescription' => '迁移版系统配置页，支持常用配置项直接读写旧 `config` 表。',
            'groups' => $groups,
            'configs' => $configs,
            'activeTab' => $request->query('tabs_page', array_key_first($groups) ?: 'basics'),
        ]);
    }

    public function create(): View
    {
        return $this->adminView('admin.routine.config.create', [
            'pageTitle' => '新增配置项',
            'pageDescription' => '迁移版配置扩展表单，优先支持常见字段类型。',
            'groups' => $this->groupMap(),
            'types' => $this->supportedTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $locale = config('cms.default_locale', 'zh-cn');
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->supportedTypes()))],
            'group' => ['required', 'string', 'max:30'],
            'name' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'max:20'],
            'title' => ['required', 'string', 'max:100'],
            'value' => ['nullable', 'string'],
            'data_list' => ['nullable', 'string'],
            'tips' => ['nullable', 'string', 'max:100'],
            'error_tips' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:255'],
            'extend' => ['nullable', 'string', 'max:500'],
            'max_number' => ['nullable', 'integer', 'min:0'],
            'setting_ext' => ['nullable', 'string', 'max:255'],
            'setting_filesize' => ['nullable', 'integer', 'min:0'],
            'array_key_name' => ['nullable', 'string', 'max:20'],
            'array_value_name' => ['nullable', 'string', 'max:20'],
        ]);

        if (SiteConfig::query()->where('name', $data['name'])->whereIn('lang', [$locale, '-1', -1])->exists()) {
            return back()->withErrors(['name' => '配置字段已存在'])->withInput();
        }

        $dataList = null;
        if (in_array($data['type'], ['radio', 'checkbox', 'select', 'selects'], true)) {
            $dataList = $this->normalizeOptions((string) ($data['data_list'] ?? ''));
        } elseif ($data['type'] === 'array') {
            $dataList = json_encode([
                'key' => $data['array_key_name'] ?: '键名',
                'value' => $data['array_value_name'] ?: '键值',
            ], JSON_UNESCAPED_UNICODE);
            $data['value'] = json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
        }

        $setting = [];
        if (in_array($data['type'], ['image', 'images', 'downfile', 'downfiles'], true)) {
            $setting = array_filter([
                'ext' => (string) ($data['setting_ext'] ?? ''),
                'filesize' => (string) ($data['setting_filesize'] ?? ''),
                'filter_option' => '',
            ], static fn ($value) => $value !== '');
        }

        SiteConfig::query()->create([
            'group' => $data['group'],
            'name' => $data['name'],
            'title' => $data['title'],
            'value' => (string) ($data['value'] ?? ''),
            'type' => $data['type'],
            'data_list' => $dataList,
            'tips' => (string) ($data['tips'] ?? ''),
            'error_tips' => (string) ($data['error_tips'] ?? ''),
            'rules' => (string) ($data['rules'] ?? ''),
            'extend' => (string) ($data['extend'] ?? ''),
            'max_number' => (int) ($data['max_number'] ?? 0),
            'is_default' => 0,
            'lang' => $locale,
            'setting' => $setting ? json_encode($setting, JSON_UNESCAPED_UNICODE) : null,
            'weigh' => ((int) SiteConfig::query()->where('group', $data['group'])->max('weigh')) + 1,
        ]);

        $this->siteService->clearCache($locale);

        return redirect()->route('admin.config.index', ['tabs_page' => $data['group']])->with('status', '配置项已新增');
    }

    public function destroy(SiteConfig $config): RedirectResponse
    {
        if ((int) $config->is_default === 1 || $config->name === 'group') {
            return back()->withErrors(['delete' => '系统默认配置不允许删除']);
        }

        $group = $config->group;
        $locale = (string) $config->lang;
        $config->delete();
        $this->siteService->clearCache($locale);

        return redirect()->route('admin.config.index', ['tabs_page' => $group])->with('status', '配置项已删除');
    }

    private function update(Request $request): RedirectResponse
    {
        $locale = config('cms.default_locale', 'zh-cn');
        $configs = $this->configRows($locale)->flatten(1)->keyBy('name');
        $rows = $request->input('row', []);

        foreach ($configs as $name => $config) {
            $value = $rows[$name] ?? null;

            if (in_array($config['type'], ['checkbox', 'selects'], true)) {
                $value = is_array($value) ? implode('|', $value) : '';
            } elseif ($config['type'] === 'array') {
                $keys = (array) data_get($value, 'key', []);
                $values = (array) data_get($value, 'value', []);
                $payload = [];
                foreach ($keys as $index => $key) {
                    $key = trim((string) $key);
                    if ($key === '') {
                        continue;
                    }
                    $payload[$key] = (string) ($values[$index] ?? '');
                }
                $value = json_encode($payload, JSON_UNESCAPED_UNICODE);
            } elseif (is_array($value)) {
                $value = implode('|', $value);
            } elseif ($value === null) {
                $value = '';
            }

            $target = SiteConfig::query()
                ->where('name', $name)
                ->whereIn('lang', [$locale, '-1', -1])
                ->orderByRaw("case when lang = ? then 0 else 1 end", [$locale])
                ->first();

            if ($target) {
                $target->update([
                    'value' => (string) $value,
                ]);
            }
        }

        $groupKeys = (array) $request->input('group.key', []);
        $groupValues = (array) $request->input('group.value', []);
        $groupMap = [];
        foreach ($groupKeys as $index => $key) {
            $key = trim((string) $key);
            $value = trim((string) ($groupValues[$index] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }
            $groupMap[$key] = $value;
        }

        if ($groupMap !== []) {
            SiteConfig::query()
                ->where('name', 'group')
                ->whereIn('lang', ['-1', -1])
                ->update([
                    'value' => json_encode($groupMap, JSON_UNESCAPED_UNICODE),
                ]);
        }

        $this->siteService->clearCache($locale);

        return redirect()
            ->route('admin.config.index', ['tabs_page' => $request->input('active_tab', 'basics')])
            ->with('status', '配置已保存');
    }

    private function groupMap(): array
    {
        $group = SiteConfig::query()
            ->where('name', 'group')
            ->whereIn('lang', ['-1', -1])
            ->value('value');

        $decoded = json_decode((string) $group, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function supportedTypes(): array
    {
        return [
            'text' => '文本',
            'number' => '数字',
            'textarea' => '多行文本',
            'editor' => '编辑器',
            'radio' => '单选',
            'checkbox' => '复选',
            'select' => '下拉选择',
            'selects' => '多选下拉',
            'image' => '单图',
            'images' => '多图',
            'downfile' => '单文件',
            'downfiles' => '多文件',
            'date' => '日期',
            'datetime' => '日期时间',
            'array' => '键值对',
        ];
    }

    private function normalizeOptions(string $input): string
    {
        $rows = preg_split('/\r\n|\r|\n/', trim($input)) ?: [];
        $result = [];

        foreach ($rows as $row) {
            if ($row === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('|', $row, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === '' || $value === '') {
                continue;
            }
            $result[$key] = $value;
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private function configRows(string $locale): Collection
    {
        return SiteConfig::query()
            ->where('name', '<>', 'group')
            ->whereIn('lang', [$locale, '-1', -1])
            ->orderBy('weigh')
            ->orderBy('id')
            ->get()
            ->filter(fn (SiteConfig $item) => ! in_array($item->name, ['category_format', 'content_format', 'url_file'], true))
            ->map(function (SiteConfig $item): array {
                $row = $item->toArray();
                $row['data_list_array'] = $row['data_list'] ? (json_decode((string) $row['data_list'], true) ?: []) : [];
                $row['setting_array'] = $row['setting'] ? (json_decode((string) $row['setting'], true) ?: []) : [];
                $row['value_array'] = $row['type'] === 'array' ? (json_decode((string) $row['value'], true) ?: []) : [];
                return $row;
            })
            ->groupBy('group');
    }
}
