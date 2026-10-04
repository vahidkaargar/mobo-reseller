<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ThumbnailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'w' => ['required', 'integer', 'min:16', 'max:1024'],
            'h' => ['required', 'integer', 'min:16', 'max:1024'],
            'q' => ['nullable', 'integer', 'min:80', 'max:100'],
            'fit' => ['nullable', 'in:cover'],
        ];
    }
}
