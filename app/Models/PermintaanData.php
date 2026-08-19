<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanData extends Model
{
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

    public function unduhanLog(): HasMany
    {
        return $this->hasMany(UnduhanLog::class);
    }

    public function notifikasiLog(): HasMany
    {
        return $this->hasMany(NotifikasiLog::class);
    }
}
