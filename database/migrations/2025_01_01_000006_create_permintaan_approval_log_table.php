<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_approval_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_data_id')->constrained('permintaan_data')->cascadeOnDelete();
            $table->string('tahap', 10);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keputusan', 15);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_approval_log');
    }
};
