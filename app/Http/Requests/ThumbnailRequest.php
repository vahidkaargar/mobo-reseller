<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'url' => Str::replace(' ', '%20', $this->url),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'w' => ['required', 'integer', 'min:16', 'max:1024'],
            'h' => ['required', 'integer', 'min:16', 'max:1024'],
            'q' => ['nullable', 'integer', 'min:80', 'max:100'],
            'fit' => ['nullable', 'in:cover']
        ];
    }
}
