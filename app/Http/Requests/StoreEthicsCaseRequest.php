<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEthicsCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ethics_profile_id' => ['nullable', 'exists:ethics_profiles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'channel' => ['required', 'in:pc,mobile,wechat,wecom,other'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'risk_level' => ['required', 'in:low,medium,high'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => '请提供举报渠道。',
            'title.required' => '请填写问题标题。',
            'content.required' => '请填写问题内容。',
            'risk_level.required' => '请提供风险等级。',
        ];
    }
}

