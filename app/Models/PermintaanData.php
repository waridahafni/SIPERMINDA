<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermintaanData extends Model
{
    public const STATUS_AKTIF = ['diajukan', 'diverifikasi_staf', 'disetujui_kasi', 'disetujui_kabid', 'menunggu_info_pemohon'];

    public function batasLayanan(): Carbon
    {
        $batas = $this->created_at->copy();
        $sisaHari = (int) config('layanan.sla_hari_kerja', 2);

        while ($sisaHari > 0) {
            $batas->addDay();
            $liburNasional = config('hari_libur.'.$batas->year, []);

            if ($batas->isWeekday() && ! in_array($batas->toDateString(), $liburNasional, true)) {
                $sisaHari--;
            }
        }

        return $batas;
    }

    public function kendala(): HasMany
    {
        return $this->hasMany(PermintaanKendala::class)->latest('id');
    }

    protected $table = 'permintaan_data';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function scopeJumlahPerBulan(Builder $query): Builder
    {
        $kolomTanggal = $query->getModel()->qualifyColumn($query->getModel()->getCreatedAtColumn());
        $driver = $query->getQuery()->getConnection()->getDriverName();

        $ekspresiBulan = match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$kolomTanggal})",
            'pgsql' => "to_char({$kolomTanggal}, 'YYYY-MM')",
            'sqlsrv' => "FORMAT({$kolomTanggal}, 'yyyy-MM')",
            'mysql', 'mariadb' => "DATE_FORMAT({$kolomTanggal}, '%Y-%m')",
            default => throw new \LogicException("Driver database [{$driver}] belum mendukung rekap bulanan."),
        };

        return $query
            ->selectRaw("{$ekspresiBulan} as bulan, count(*) as total")
            ->groupByRaw($ekspresiBulan);
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pemohon::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriData::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvalLog(): HasMany
    {
        return $this->hasMany(PermintaanApprovalLog::class);
    }

    public function klarifikasi(): HasMany
    {
        return $this->hasMany(PermintaanKlarifikasi::class)->oldest('id');
    }

    public function unduhanLog(): HasMany
    {
        return $this->hasMany(UnduhanLog::class);
    }

    public function hasilFiles(): HasMany
    {
        return $this->hasMany(PermintaanHasilFile::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(PermintaanFeedback::class);
    }

    public function notifikasiLog(): HasMany
    {
        return $this->hasMany(NotifikasiLog::class);
    }
}
