<?php

namespace App\Support;

use Aws\Signature\S3SignatureV4;

class R2SignatureV4 extends S3SignatureV4
{
    protected function getPresignHeaderDenyList()
    {
        $headers = parent::getPresignHeaderDenyList();
        // SDK mengabaikan Content-Type secara default; upload kita mengikat nilainya.
        unset($headers['content-type']);

        return $headers;
    }
}
