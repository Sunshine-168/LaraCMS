<?php

namespace App\Services\Cms;

use App\Events\FormSubmitted;
use App\Models\Cms\CmsForm;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Member;
use App\Support\Captcha;
use App\Support\Result;
use Illuminate\Http\Request;

class FormSubmitService
{
    public function __construct(private readonly OpsNotifyService $opsNotify) {}
    public function submit(string $slug, Request $request, ?Member $member, bool $skipCaptcha = false): array
    {
        $form = CmsForm::query()->where('slug', $slug)->where('is_active', true)->first();
        if (! $form) {
            return Result::fail('表单不存在');
        }

        if (! $skipCaptcha && config('cms.form_captcha', true) && ! Captcha::check($request->input('captcha'))) {
            return Result::fail('验证码错误');
        }

        $rules = [];
        $allowed = [];
        foreach ($form->fields ?? [] as $field) {
            if (! is_array($field) || empty($field['name'])) {
                continue;
            }
            $name = (string) $field['name'];
            $allowed[] = $name;
            $type = $field['type'] ?? 'text';
            $rule = ! empty($field['required']) ? ['required'] : ['nullable'];
            if ($type === 'email') {
                $rule[] = 'email';
            } elseif ($type === 'number') {
                $rule[] = 'numeric';
            } elseif ($type === 'checkbox') {
                $rule[] = 'array';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:5000';
            }
            $rules[$name] = $rule;
        }

        $validated = $rules === []
            ? $request->except(['_token', 'captcha', 'api_token'])
            : $request->validate($rules);

        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        if ($member && ! $member->canMember('form_submit')) {
            return Result::fail('当前会员组无权提交表单');
        }

        $submission = FormSubmission::query()->create([
            'form_id' => $form->id,
            'member_id' => $member?->id,
            'data' => $data,
            'ip' => $request->ip(),
        ]);

        if ($form->notify_email) {
            $this->opsNotify->formSubmitted(
                (string) $form->name,
                (string) $form->notify_email,
                $data,
                (string) $request->ip()
            );
        }
        event(new FormSubmitted($submission, $form));

        return Result::ok($submission, '提交成功，我们会尽快联系您。');
    }
}
