<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanHasilFile extends Model
{
    protected $table = 'permintaan_hasil_file';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class, 'permintaan_data_id');
    }
}
