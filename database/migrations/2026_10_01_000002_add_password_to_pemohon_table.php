<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohon', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->unsignedInteger('auth_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pemohon', fn (Blueprint $table) => $table->dropColumn(['password', 'auth_version']));
    }
};
