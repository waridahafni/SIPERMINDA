<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use MassPrunable;

    protected $guarded = ['id'];

    protected $hidden = ['kode_otp'];

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Hapus metadata OTP yang telah kedaluwarsa melewati masa retensi.
     */
    public function prunable(): Builder
    {
        $hariRetensi = min(30, max(1, (int) config('otp.retensi_hari', 7)));

        return static::where('expired_at', '<=', now()->subDays($hariRetensi));
    }
}
