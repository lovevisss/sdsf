<?php

namespace App\Http\Requests;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEthicsDisciplineViolationRequest extends FormRequest
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
            'violation_type' => ['required', 'integer', 'between:35,39'],
            'severity_level' => ['nullable', 'string', 'in:A,B,C,a,b,c'],
            'violation_at' => ['required', 'date'],
            'deduction_points' => ['nullable', 'numeric', 'min:0.01', 'max:20'],
            'data_source' => ['nullable', 'string', 'max:50'],
            'handler_department' => ['nullable', 'string', 'max:255'],
            'deduction_basis' => ['nullable', 'string'],
            'evidence_attachments' => ['nullable', 'array'],
            'verification_status' => ['nullable', 'string', 'in:pending,verified,rejected'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStaff($validator);

            if (! $this->filled('severity_level') && ! $this->filled('deduction_points')) {
                $validator->errors()->add('deduction_points', '请填写扣分值。');
            }
        });
    }

    private function validateStaff(Validator $validator): void
    {
        $user = $this->user();

        if ($user === null || $user->role === 'admin' || $user->is_admin === true) {
            return;
        }

        try {
            $staff = Staff::query()->select(['gh', 'xm', 'dwmc', 'bmmc'])->find((string) $this->input('staff_no'));
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
    }
}
