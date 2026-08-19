<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorTiketCounter extends Model
{
    protected $table = 'nomor_tiket_counters';

    protected $guarded = ['id'];

    public $timestamps = false;
}
