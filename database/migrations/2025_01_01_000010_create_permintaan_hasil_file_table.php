<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_hasil_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->constrained('permintaan_data')->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('ukuran_byte')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_hasil_file');
    }
};
