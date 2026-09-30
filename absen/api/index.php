<?php
// absen/api/index.php
// Informasi Root REST API

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'online',
    'service' => 'REST API Presensi Android',
    'endpoints' => [
        'GET /api/acara.php' => 'Mengambil daftar acara yang sedang BUKA',
        'POST /api/absen.php' => 'Mengirim data absensi peserta dari Android (parameter: acara_id, nama, jabatan, pin, tanda_tangan)'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
