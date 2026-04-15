<?php

namespace App\Http\Requests;

use App\Models\EthicsProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEthicsWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ethics_profile_id' => ['required', 'exists:ethics_profiles,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'warning_level' => ['required', 'in:yellow,orange,red'],
            'source_type' => ['required', 'in:teaching,research,behavior,training,manual'],
            'reason' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ethics_profile_id.required' => '请选择预警对象。',
            'warning_level.required' => '请选择预警级别。',
            'source_type.required' => '请选择预警来源。',
            'reason.required' => '请填写预警原因。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null || $user->role === 'admin') {
                return;
            }

            $profileId = $this->input('ethics_profile_id');

            if (! is_numeric($profileId)) {
                return;
            }

            $profile = EthicsProfile::query()->find((int) $profileId);

            if ($profile !== null && $user->department_id !== $profile->department_id) {
                $validator->errors()->add('ethics_profile_id', '只能为本部门教师创建预警。');
            }
        });
    }
}

