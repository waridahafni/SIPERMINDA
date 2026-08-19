<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnduhanLog extends Model
{
    protected $table = 'unduhan_log';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    public function datasetTerbuka(): BelongsTo
    {
        return $this->belongsTo(DatasetTerbuka::class);
    }

    public function permintaanData(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class);
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pemohon::class);
    }
}
