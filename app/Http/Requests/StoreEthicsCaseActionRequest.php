<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEthicsCaseActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action_type' => ['required', 'in:accept,assign,investigate,rectify,feedback,close,reject,note'],
            'notes' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'action_type.required' => '请提供处置动作。',
            'notes.required' => '请填写动作说明。',
        ];
    }
}

