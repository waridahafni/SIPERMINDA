<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->constrained('permintaan_data')->cascadeOnDelete();
            $table->foreignId('pemohon_id')->constrained('pemohon')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('komentar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_feedback');
    }
};
