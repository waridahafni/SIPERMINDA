<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
