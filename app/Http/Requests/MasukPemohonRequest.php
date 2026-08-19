<?php

namespace App\Http\Requests;

use App\Rules\NomorHpIndonesia;
use Illuminate\Foundation\Http\FormRequest;

class MasukPemohonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('no_hp'))) {
            $this->merge(['no_hp' => trim($this->input('no_hp'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_hp' => ['required', 'string', 'max:20', new NomorHpIndonesia],
        ];
    }
}
