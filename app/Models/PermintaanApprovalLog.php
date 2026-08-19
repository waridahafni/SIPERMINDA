<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanApprovalLog extends Model
{
    protected $table = 'permintaan_approval_log';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function permintaanData(): BelongsTo
    {
        return $this->belongsTo(PermintaanData::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
