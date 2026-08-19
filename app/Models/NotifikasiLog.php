<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiLog extends Model
{
    protected $table = 'notifikasi_log';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function permintaanData(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class);
    }
}
