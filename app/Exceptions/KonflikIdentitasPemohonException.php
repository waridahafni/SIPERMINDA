<?php

namespace App\Exceptions;

use RuntimeException;

class KonflikIdentitasPemohonException extends RuntimeException
{
    // Nomor fisik yang sama tidak boleh dipetakan diam-diam ke beberapa pemohon.
}
