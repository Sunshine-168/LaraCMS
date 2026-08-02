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

            return (object) $field;
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
        $action = route('form.submit', $form->slug);
        $html = '<form method="post" action="'.e($action).'" class="cms-form" data-form="'.e($form->slug).'">';
        $html .= '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';

        foreach ($this->get(['slug' => $form->slug]) as $field) {
            $html .= $this->renderField($field);
        }

        if ($captcha) {
            $q = Captcha::generate();
            $html .= '<div class="cms-form-item"><label>验证码：'.e($q['question']).'</label>';
            $html .= '<input type="text" name="captcha" required autocomplete="off"></div>';
        }

        $html .= '<button type="submit">'.e((string) ($options['submit'] ?? '提交')).'</button></form>';

        return new HtmlString($html);
    }

    protected function renderField(object $field): string
    {
        $name = e($field->name);
        $label = e($field->label ?? $field->name);
        $type = $field->type ?? 'text';
        $required = ! empty($field->required) ? ' required' : '';
        $placeholder = e((string) ($field->placeholder ?? ''));
        $choices = is_array($field->choices ?? null) ? $field->choices : [];

        $html = '<div class="cms-form-item"><label>'.$label.(! empty($field->required) ? ' *' : '').'</label>';

        if ($type === 'textarea') {
            $html .= '<textarea name="'.$name.'" rows="4" placeholder="'.$placeholder.'"'.$required.'></textarea>';
        } elseif ($type === 'select') {
            $html .= '<select name="'.$name.'"'.$required.'>';
            $html .= '<option value="">请选择</option>';
            foreach ($choices as $c) {
                $html .= '<option value="'.e($c).'">'.e($c).'</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'radio') {
            foreach ($choices as $c) {
                $html .= '<label class="inline"><input type="radio" name="'.$name.'" value="'.e($c).'"'.$required.'> '.e($c).'</label> ';
            }
        } elseif ($type === 'checkbox') {
            foreach ($choices as $c) {
                $html .= '<label class="inline"><input type="checkbox" name="'.$name.'[]" value="'.e($c).'"> '.e($c).'</label> ';
            }
        } else {
            $inputType = in_array($type, ['email', 'number', 'tel'], true) ? $type : 'text';
            $html .= '<input type="'.$inputType.'" name="'.$name.'" placeholder="'.$placeholder.'"'.$required.'>';
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
}
