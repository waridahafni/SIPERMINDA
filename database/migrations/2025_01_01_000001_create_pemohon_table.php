<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemohon', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp', 20)->unique();
            $table->string('email')->nullable();
            $table->string('jenis_pemohon', 20);
            $table->string('nama_instansi')->nullable();
            $table->timestamp('no_hp_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemohon');
    }
};
