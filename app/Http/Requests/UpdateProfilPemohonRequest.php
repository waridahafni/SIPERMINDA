<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilPemohonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('pemohon') !== null;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'jenis_pemohon' => ['required', Rule::in(['publik', 'instansi'])],
            'nama_instansi' => ['nullable', 'required_if:jenis_pemohon,instansi', 'string', 'max:255'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama lengkap',
            'jenis_pemohon' => 'kategori pemohon',
            'nama_instansi' => 'asal instansi/lembaga',
            'kabupaten_kota' => 'kabupaten/kota',
            'alamat_lengkap' => 'alamat lengkap',
        ];
    }
}
