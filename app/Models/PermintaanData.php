<?php

namespace App\Models;

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
        return $this->belongsTo(User::class);
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
