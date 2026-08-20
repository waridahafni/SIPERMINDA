<?php

namespace Tests\Feature;

use Tests\TestCase;

class KonfigurasiWaktuTest extends TestCase
{
    public function test_aplikasi_menggunakan_waktu_indonesia_barat(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }
}
