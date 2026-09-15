<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\CmsForm;
use App\Support\Captcha;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class FormTag
{
    /**
     * 循环表单字段
     *
     * @param  array<string, mixed>  $options
     */
    public function get(array $options = []): Collection
    {
        $form = $this->resolve($options);
        if (! $form) {
            return collect();
        }

        return collect($form->fields ?? [])->map(function ($field, $i) {
            $field = is_array($field) ? $field : ['name' => 'field_'.$i, 'label' => (string) $field, 'type' => 'text'];
            $field['type'] = $field['type'] ?? 'text';
            $field['required'] = ! empty($field['required']);
            if (isset($field['choices']) && is_string($field['choices'])) {
                $field['choices'] = array_values(array_filter(array_map('trim', explode(',', $field['choices']))));
            }

            $obj = (object) $field;
            $obj->label = $this->fieldLabel($obj);

            return $obj;
        });
    }

    /**
     * 渲染完整表单（含验证码）
     *
     * @param  array<string, mixed>  $options
     */
    public function render(array $options = []): HtmlString
    {
        $form = $this->resolve($options);
        if (! $form) {
            return new HtmlString('<!-- cms form not found -->');
        }

        $captcha = array_key_exists('captcha', $options)
            ? ! empty($options['captcha'])
            : (bool) config('cms.form_captcha', true);
        $action = cms_route('form.submit', $form->slug);
        $html = '';
        $status = session('status');
        $error = session('error');
        if (is_string($status) && $status !== '') {
            $html .= '<p class="muted cms-form-flash" style="color:green">'.e($status).'</p>';
        }
        if (is_string($error) && $error !== '') {
            $html .= '<p class="muted cms-form-flash" style="color:#c0392b">'.e($error).'</p>';
        }
        $html .= '<form method="post" action="'.e($action).'" class="cms-form" data-form="'.e($form->slug).'">';
        $html .= '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';

        foreach ($this->get(['slug' => $form->slug]) as $field) {
            $html .= $this->renderField($field);
        }

        if ($captcha) {
            $q = Captcha::generate();
            $html .= '<div class="cms-form-item"><label>'.e(cms_ui('captcha')).'：'.e($q['question']).'</label>';
            $html .= '<input type="text" name="captcha" required autocomplete="off"></div>';
        }

        $html .= '<button class="btn" type="submit">'.e((string) ($options['submit'] ?? cms_ui('submit_form'))).'</button></form>';

        return new HtmlString($html);
    }

    protected function renderField(object $field): string
    {
        $rawName = (string) $field->name;
        $name = e($rawName);
        $label = e($this->fieldLabel($field));
        $type = $field->type ?? 'text';
        $required = ! empty($field->required) ? ' required' : '';
        $placeholder = e((string) ($field->placeholder ?? ''));
        $choices = is_array($field->choices ?? null) ? $field->choices : [];
        $old = old($rawName);

        $html = '<div class="cms-form-item"><label>'.$label.(! empty($field->required) ? ' *' : '').'</label>';

        if ($type === 'textarea') {
            $html .= '<textarea name="'.$name.'" rows="4" placeholder="'.$placeholder.'"'.$required.'>'.e(is_scalar($old) ? (string) $old : '').'</textarea>';
        } elseif ($type === 'select') {
            $html .= '<select name="'.$name.'"'.$required.'>';
            $html .= '<option value="">'.e(cms_ui('please_select')).'</option>';
            foreach ($choices as $c) {
                $sel = is_scalar($old) && (string) $old === (string) $c ? ' selected' : '';
                $html .= '<option value="'.e($c).'"'.$sel.'>'.e($c).'</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'radio') {
            foreach ($choices as $c) {
                $chk = is_scalar($old) && (string) $old === (string) $c ? ' checked' : '';
                $html .= '<label class="inline"><input type="radio" name="'.$name.'" value="'.e($c).'"'.$required.$chk.'> '.e($c).'</label> ';
            }
        } elseif ($type === 'checkbox') {
            $olds = is_array($old) ? $old : (is_scalar($old) && $old !== '' && $old !== null ? [$old] : []);
            $olds = array_map(fn ($v) => (string) $v, $olds);
            foreach ($choices as $c) {
                $chk = in_array((string) $c, $olds, true) ? ' checked' : '';
                $html .= '<label class="inline"><input type="checkbox" name="'.$name.'[]" value="'.e($c).'"'.$chk.'> '.e($c).'</label> ';
            }
        } else {
            $inputType = in_array($type, ['email', 'number', 'tel'], true) ? $type : 'text';
            $html .= '<input type="'.$inputType.'" name="'.$name.'" value="'.e(is_scalar($old) ? (string) $old : '').'" placeholder="'.$placeholder.'"'.$required.'>';
        }

        return $html.'</div>';
    }

    /** @param  array<string, mixed>  $options */
    protected function resolve(array $options): ?CmsForm
    {
        $slug = $options['slug'] ?? $options['id'] ?? $options['name'] ?? null;
        if (! $slug) {
            return null;
        }

        return CmsForm::query()->where('slug', (string) $slug)->where('is_active', true)->first();
    }

    protected function fieldLabel(object $field): string
    {
        $raw = $field->label ?? null;
        if (is_array($raw)) {
            $locale = \App\Support\Locale::current();
            foreach ([$locale, \App\Support\Locale::default(), 'en', 'zh_cn'] as $code) {
                if (! empty($raw[$code]) && is_string($raw[$code])) {
                    return $raw[$code];
                }
            }
        }
        $en = trim((string) ($field->label_en ?? ''));
        if ($en !== '' && \App\Support\Locale::isEnglish()) {
            return $en;
        }
        $map = [
            'name' => 'form_name',
            'email' => 'email',
            'message' => 'form_message',
            'phone' => 'phone',
            'tel' => 'phone',
        ];
        $key = $map[(string) $field->name] ?? null;
        if ($key && (\App\Support\Locale::isEnglish() || ! is_string($raw) || trim($raw) === '')) {
            return cms_ui($key);
        }

        return is_string($raw) && $raw !== '' ? $raw : (string) $field->name;
    }
}
