<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PermintaanKlarifikasi extends Model
{
    protected $table = 'permintaan_klarifikasi';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dijawab_at' => 'datetime',
        ];
    }

    public function scopeBelumDijawab(Builder $query): Builder
    {
        return $query->whereNull('dijawab_at');
    }

    public function statusSetelahDijawab(): string
    {
        return match ($this->tahap) {
            'staf' => 'diajukan',
            'kasi' => 'diverifikasi_staf',
            'kabid' => 'disetujui_kasi',
            default => throw new LogicException('Tahap klarifikasi tidak valid.'),
        };
    }

    public function permintaanData(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class);
    }

    public function peminta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }
}
