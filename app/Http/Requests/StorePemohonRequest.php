<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePemohonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:pemohon,no_hp',
            'email' => 'nullable|email|max:255',
            'jenis_pemohon' => 'required|in:publik,instansi',
            'nama_instansi' => 'required_if:jenis_pemohon,instansi|nullable|string|max:255',
        ];
    }
}
