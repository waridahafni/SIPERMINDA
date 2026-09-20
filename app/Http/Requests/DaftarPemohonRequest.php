<?php

namespace App\Http\Requests;

use App\Rules\NomorHpIndonesia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $kategoriPerorangan = in_array($jenisPemohon, ['masyarakat_umum', 'publik'], true);

        $this->merge([
            'no_hp' => is_string($this->input('no_hp')) ? trim($this->input('no_hp')) : $this->input('no_hp'),
            'nama' => is_string($this->input('nama')) ? trim($this->input('nama')) : $this->input('nama'),
            'email' => $email === '' ? null : $email,
            'jenis_pemohon' => $jenisPemohon,
            'nama_instansi' => ! $kategoriPerorangan
                ? ($namaInstansi === '' ? null : $namaInstansi)
                : null,
            'provinsi' => is_string($this->input('provinsi')) ? trim($this->input('provinsi')) : $this->input('provinsi'),
            'kabupaten_kota' => is_string($this->input('kabupaten_kota')) ? trim($this->input('kabupaten_kota')) : $this->input('kabupaten_kota'),
            'alamat_lengkap' => is_string($this->input('alamat_lengkap')) ? trim($this->input('alamat_lengkap')) : $this->input('alamat_lengkap'),
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
            'jenis_pemohon' => ['required', Rule::in(['masyarakat_umum', 'pelajar_mahasiswa', 'akademisi_peneliti', 'instansi_pemerintah', 'bumn_bumd', 'perusahaan_swasta', 'organisasi_lembaga', 'lainnya', 'publik', 'instansi'])],
            'nama_instansi' => ['nullable', 'required_unless:jenis_pemohon,masyarakat_umum,publik', 'string', 'max:255'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Silakan isi nama lengkap Anda.',
            'no_hp.required' => 'Silakan isi nomor WhatsApp Anda.',
            'no_hp.*' => 'Masukkan nomor WhatsApp Indonesia yang aktif.',
            'email.email' => 'Masukkan alamat email yang benar.',
            'jenis_pemohon.required' => 'Silakan pilih kategori Anda.',
            'jenis_pemohon.in' => 'Silakan pilih kategori Anda.',
            'nama_instansi.required_unless' => 'Silakan isi nama sekolah, kampus, instansi, atau lembaga Anda.',
            'provinsi.required' => 'Silakan pilih provinsi.',
            'kabupaten_kota.required' => 'Silakan pilih kabupaten/kota.',
            'alamat_lengkap.required' => 'Silakan isi alamat lengkap.',
        ];
    }
}
