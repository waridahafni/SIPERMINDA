<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatasetTerbuka extends Model
{
    protected $table = 'dataset_terbuka';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'ukuran_file' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriData::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function datasetInduk(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function revisi(): HasMany
    {
        return $this->hasMany(self::class, 'dataset_induk_id');
    }

    public function unduhanLog(): HasMany
    {
        return $this->hasMany(UnduhanLog::class);
    }
}
