<?php

return [
    // Nilai dibatasi satu atau dua hari kerja agar indikator dashboard konsisten.
    'sla_hari_kerja' => min(2, max(1, (int) env('PERMINTAAN_SLA_HARI_KERJA', 2))),
];
