<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilPemohonRequest;
use App\Models\Pemohon;
use Illuminate\Http\Request;

class AkunPemohonController extends Controller
{
    public function dashboard(Request $request)
    {
        $pemohon = $this->pemohonAktif($request);
        $permintaanTerbaru = $pemohon->permintaanData()->latest()->take(3)->get();

        return view('public.akun.dashboard', compact('pemohon', 'permintaanTerbaru'));
    }

    public function profil(Request $request)
    {
        return view('public.akun.profil', ['pemohon' => $this->pemohonAktif($request)]);
    }

    public function editProfil(Request $request)
    {
        return view('public.akun.profil-edit', ['pemohon' => $this->pemohonAktif($request)]);
    }

    public function updateProfil(UpdateProfilPemohonRequest $request)
    {
        $pemohon = $this->pemohonAktif($request);
        $data = $request->validated();

        if ($data['jenis_pemohon'] !== 'instansi') {
            $data['nama_instansi'] = null;
        }

        $pemohon->update($data);
        $request->session()->put('pemohon_nama', $pemohon->nama);

        return redirect()->route('pemohon.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    private function pemohonAktif(Request $request): Pemohon
    {
        $pemohon = $request->attributes->get('pemohon');

        abort_unless($pemohon instanceof Pemohon, 403, 'Sesi pemohon tidak valid.');

        return $pemohon;
    }
}
