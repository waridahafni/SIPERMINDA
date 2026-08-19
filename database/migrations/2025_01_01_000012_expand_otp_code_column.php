<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kode lama masih berupa teks biasa dan berumur sangat singkat, sehingga
        // lebih aman dibatalkan saat beralih ke penyimpanan berbentuk hash.
        DB::table('otp_verifications')->delete();

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('kode_otp', 255)->change();
        });
    }

    public function down(): void
    {
        DB::table('otp_verifications')->delete();

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('kode_otp', 6)->change();
        });
    }
};
