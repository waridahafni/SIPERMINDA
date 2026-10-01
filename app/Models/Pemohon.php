<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pemohon extends Model
{
    protected $table = 'pemohon';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'auth_version'];

    protected function casts(): array
    {
        return [
            'no_hp_verified_at' => 'datetime',
            'password' => 'hashed',
            'auth_version' => 'integer',
        ];
    }

    public function permintaanData(): HasMany
    {
        return $this->hasMany(PermintaanData::class);
    }

    public function unduhanLog(): HasMany
    {
        return $this->hasMany(UnduhanLog::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(PermintaanFeedback::class);
    }
}
