<?php

namespace App\Http\Requests;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEthicsEducationViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'staff_no' => ['required', 'string', 'max:50'],
            'staff_name' => ['required', 'string', 'max:100'],
            'staff_unit_name' => ['nullable', 'string', 'max:255'],
            'violation_type' => ['required', 'integer', 'between:8,15'],
            'violation_at' => ['required', 'date'],
            'deduction_points' => ['required', 'numeric', 'min:0.01', 'max:25'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'violation_type.between' => '违规类型必须是8-15之间的编号。',
            'deduction_points.max' => '教育教学行为单次扣分不能超过25分。',
            'staff_no.required' => '请选择违规人员（工号）。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null || $user->role === 'admin' || $user->is_admin === true) {
                return;
            }

            $staffNo = (string) $this->input('staff_no');

            try {
                $staff = Staff::query()->select(['gh', 'xm', 'dwmc', 'bmmc'])->find($staffNo);
            } catch (\Throwable) {
                return;
            }

            if ($staff === null) {
                $validator->errors()->add('staff_no', '未找到对应的教职工档案。');

                return;
            }

            if ($this->filled('staff_name') && $this->input('staff_name') !== $staff->xm) {
                $validator->errors()->add('staff_name', '姓名与工号不匹配。');
            }
        });
    }
}

