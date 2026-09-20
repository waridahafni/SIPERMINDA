<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohon', function (Blueprint $table) {
            $table->string('provinsi', 100)->nullable()->after('nama_instansi');
            $table->string('kabupaten_kota', 100)->nullable()->after('provinsi');
            $table->text('alamat_lengkap')->nullable()->after('kabupaten_kota');
        });
    }

    public function down(): void
    {
        Schema::table('pemohon', function (Blueprint $table) {
            $table->dropColumn(['provinsi', 'kabupaten_kota', 'alamat_lengkap']);
        });
    }
};
