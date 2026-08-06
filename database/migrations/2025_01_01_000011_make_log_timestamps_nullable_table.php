<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // notifikasi_log dan permintaan_approval_log memakai model dengan
        // $timestamps = false, sehingga created_at tidak diisi otomatis.
        Schema::table('notifikasi_log', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
        });

        Schema::table('permintaan_approval_log', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_log', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable(false)->change();
        });

        Schema::table('permintaan_approval_log', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable(false)->change();
        });
    }
};