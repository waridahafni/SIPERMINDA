<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unduhan_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_terbuka_id')->nullable()->constrained('dataset_terbuka')->nullOnDelete();
            $table->foreignId('permintaan_data_id')->nullable()->constrained('permintaan_data')->nullOnDelete();
            $table->foreignId('pemohon_id')->nullable()->constrained('pemohon')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('downloaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unduhan_log');
    }
};
