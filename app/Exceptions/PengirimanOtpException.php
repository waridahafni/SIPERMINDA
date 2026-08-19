<?php

namespace App\Exceptions;

use RuntimeException;

class PengirimanOtpException extends RuntimeException
{
    // Pesan exception ini harus aman untuk log dan tidak memuat OTP atau token.
}
