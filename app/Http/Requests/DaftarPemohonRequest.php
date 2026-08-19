<?php

namespace App\Http\Requests;

use App\Rules\NomorHpIndonesia;
use Illuminate\Foundation\Http\FormRequest;

class DaftarPemohonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $jenisPemohon = is_string($this->input('jenis_pemohon'))
            ? trim($this->input('jenis_pemohon'))
            : $this->input('jenis_pemohon');
        $email = is_string($this->input('email'))
            ? trim($this->input('email'))
            : $this->input('email');
        $namaInstansi = is_string($this->input('nama_instansi'))
            ? trim($this->input('nama_instansi'))
            : $this->input('nama_instansi');

        $this->merge([
            'no_hp' => is_string($this->input('no_hp')) ? trim($this->input('no_hp')) : $this->input('no_hp'),
            'nama' => is_string($this->input('nama')) ? trim($this->input('nama')) : $this->input('nama'),
            'email' => $email === '' ? null : $email,
            'jenis_pemohon' => $jenisPemohon,
            'nama_instansi' => $jenisPemohon === 'instansi'
                ? ($namaInstansi === '' ? null : $namaInstansi)
                : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_hp' => ['required', 'string', 'max:20', new NomorHpIndonesia],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'jenis_pemohon' => ['required', 'in:publik,instansi'],
            'nama_instansi' => ['required_if:jenis_pemohon,instansi', 'nullable', 'string', 'max:255'],
        ];
    }
}
