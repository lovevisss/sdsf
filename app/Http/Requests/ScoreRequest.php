<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => 'required|integer|exists:teachers,id',
            'dimension' => 'required|string|in:politics,teaching,ethics,integrity',
            'current_score' => 'required|integer|min:0|max:100',
        ];
    }
}