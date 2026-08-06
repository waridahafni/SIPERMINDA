<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_terbuka', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_data')->nullOnDelete();
            $table->string('periode', 50);
            $table->string('file_path');
            $table->bigInteger('ukuran_file')->default(0);
            $table->integer('versi')->default(1);
            $table->foreignId('dataset_induk_id')->nullable()->constrained('dataset_terbuka')->nullOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('kategori_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_terbuka');
    }
};
