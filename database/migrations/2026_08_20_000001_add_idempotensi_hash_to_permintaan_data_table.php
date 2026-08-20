<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_data', function (Blueprint $table): void {
            $table->char('idempotensi_hash', 64)
                ->nullable()
                ->unique()
                ->after('nomor_tiket');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_data', function (Blueprint $table): void {
            $table->dropUnique(['idempotensi_hash']);
            $table->dropColumn('idempotensi_hash');
        });
    }
};
