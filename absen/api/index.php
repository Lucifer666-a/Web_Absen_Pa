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
        'POST /api/absen.php' => 'Mengirim data absensi rapat/acara dari Android (parameter: acara_id/acara, nama, jabatan, pin, tanda_tangan)',
        'POST /api/absen_apel.php' => 'Mengirim data absensi apel pagi/sore dari Android (parameter: nama, jabatan, sesi [pagi/sore], tanda_tangan, optional: tanggal, waktu)'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
