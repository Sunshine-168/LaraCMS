@php
    $lines = [
        'standard' => [
            [cms_t('前台', 'Front'), cms_t('九套里选，换名字、Logo、主色', 'One of nine. Name, logo, color'), false],
            [cms_t('后台', 'Admin'), cms_t('含', 'Yes'), false],
            [cms_t('代理台', 'Agent'), cms_t('含', 'Yes'), false],
            [cms_t('游戏', 'Games'), cms_t('按市场接入', 'By market'), false],
            [cms_t('按稿改界面', 'Custom layout'), cms_t('不含', 'No'), true],
        ],
        'custom' => [
            [cms_t('前台', 'Front'), cms_t('按确认过的稿改结构', 'Structure from an approved design'), false],
            [cms_t('后台', 'Admin'), cms_t('含', 'Yes'), false],
            [cms_t('代理台', 'Agent'), cms_t('含', 'Yes'), false],
            [cms_t('游戏', 'Games'), cms_t('按市场接入', 'By market'), false],
            [cms_t('按稿改界面', 'Custom layout'), cms_t('含', 'Yes'), false],
        ],
        'api' => [
            [cms_t('前台', 'Front'), cms_t('不含，用你现有的站', 'No. Keep the current site'), true],
            [cms_t('后台', 'Admin'), cms_t('不含整套', 'Not the full admin'), true],
            [cms_t('代理台', 'Agent'), cms_t('不含', 'No'), true],
            [cms_t('游戏', 'Games'), cms_t('一次对接，按品类开关', 'One connection, by category'), false],
            [cms_t('联调', 'Integration'), cms_t('含', 'Yes'), false],
        ],
    ][$slug ?? ''] ?? null;
@endphp
@if($lines)
    <ul class="ig-check">
        @foreach($lines as [$label, $value, $off])
            <li @class(['is-off' => $off])>
                <span>{{ $label }}</span><i>{{ $value }}</i>
            </li>
        @endforeach
    </ul>
@endif
