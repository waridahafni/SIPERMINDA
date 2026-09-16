<?php

return [
    // '*' hanya pada container privat di belakang edge Vercel yang tepercaya.
    'proxies' => env('TRUSTED_PROXIES'),
];
