<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    use MassPrunable;

    protected $table = 'whatsapp_messages';

    protected $guarded = ['id'];

    protected $hidden = ['phone'];

    protected function casts(): array
    {
        return ['phone' => 'encrypted', 'sent_at' => 'datetime', 'status_at' => 'datetime'];
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(
            min(90, max(1, (int) config('otp.whatsapp.retention_days', 30))),
        ));
    }
}
