<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('no_hp', 20);
            $table->string('kode_otp', 6);
            $table->timestamp('expired_at');
            $table->timestamp('verified_at')->nullable();
            $table->tinyInteger('attempt_count')->default(0);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
