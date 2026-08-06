<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriData extends Model
{
    protected $table = 'kategori_data';
    protected $guarded = ['id'];

    public function datasetTerbuka(): HasMany
    {
        return $this->hasMany(DatasetTerbuka::class);
    }

    public function permintaanData(): HasMany
    {
        return $this->hasMany(PermintaanData::class);
    }
}
