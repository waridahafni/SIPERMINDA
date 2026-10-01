<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanKendala extends Model
{
    protected $table = 'permintaan_kendala';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['diselesaikan_at' => 'datetime'];
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class, 'permintaan_data_id');
    }
}
