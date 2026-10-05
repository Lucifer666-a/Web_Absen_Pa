<?php
// absen/api/user_status.php
// Endpoint REST API: Mengecek status absensi harian & riwayat absensi user untuk aplikasi Android

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Ambil user_id dari GET atau POST JSON
$user_id = (int)($_GET['user_id'] ?? $_GET['userId'] ?? 0);

if ($user_id <= 0) {
    $rawInput = file_get_contents('php://input');
    $rawInput = ltrim($rawInput, "\xEF\xBB\xBF");
    $input    = json_decode($rawInput, true);
    if (is_array($input)) {
        $user_id = (int)($input['user_id'] ?? $input['userId'] ?? 0);
    }
}

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter user_id wajib dikirim.'
    ]);
    exit;
}

$today = date('Y-m-d');

try {
    // 1. Cek Profil User
    $stmtUser = $pdo->prepare("SELECT id, nip, nama, jabatan, username FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => 'User tidak ditemukan.'
        ]);
        exit;
    }

    // 2. Cek Status Apel Hari Ini
    $stmtApelPagi = $pdo->prepare("SELECT id, waktu, tanda_tangan FROM presensi_apel WHERE user_id = ? AND tanggal = ? AND sesi = 'pagi' LIMIT 1");
    $stmtApelPagi->execute([$user_id, $today]);
    $apelPagi = $stmtApelPagi->fetch(PDO::FETCH_ASSOC);

    $stmtApelSore = $pdo->prepare("SELECT id, waktu, tanda_tangan FROM presensi_apel WHERE user_id = ? AND tanggal = ? AND sesi = 'sore' LIMIT 1");
    $stmtApelSore->execute([$user_id, $today]);
    $apelSore = $stmtApelSore->fetch(PDO::FETCH_ASSOC);

    $statusHariIni = [
        'tanggal'   => $today,
        'apel_pagi' => [
            'sudah_absen'  => !empty($apelPagi),
            'waktu'        => $apelPagi['waktu'] ?? null,
            'tanda_tangan' => $apelPagi['tanda_tangan'] ?? null
        ],
        'apel_sore' => [
            'sudah_absen'  => !empty($apelSore),
            'waktu'        => $apelSore['waktu'] ?? null,
            'tanda_tangan' => $apelSore['tanda_tangan'] ?? null
        ]
    ];

    // 3. Ambil Riwayat Keseluruhan (Apel + Rapat) milik user_id
    // Query Apel
    $stmtRiwayatApel = $pdo->prepare("
        SELECT 'apel' AS jenis, 
               CONCAT('Apel ', UPPER(sesi)) AS judul, 
               tanggal, 
               waktu, 
               tanda_tangan, 
               created_at
        FROM presensi_apel 
        WHERE user_id = ?
    ");
    $stmtRiwayatApel->execute([$user_id]);
    $riwayatApel = $stmtRiwayatApel->fetchAll(PDO::FETCH_ASSOC);

    // Query Rapat / Acara
    $stmtRiwayatRapat = $pdo->prepare("
        SELECT 'rapat' AS jenis, 
               a.nama_acara AS judul, 
               DATE(p.waktu_absen) AS tanggal, 
               TIME(p.waktu_absen) AS waktu, 
               p.tanda_tangan, 
               p.waktu_absen AS created_at
        FROM presensi p
        JOIN acara a ON p.acara_id = a.id
        WHERE p.user_id = ?
    ");
    $stmtRiwayatRapat->execute([$user_id]);
    $riwayatRapat = $stmtRiwayatRapat->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan & urutkan descending berdasarkan created_at
    $semuaRiwayat = array_merge($riwayatApel, $riwayatRapat);
    usort($semuaRiwayat, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    http_response_code(200);
    echo json_encode([
        'status'          => 'success',
        'user'            => [
            'id'       => (int)$user['id'],
            'nip'      => $user['nip'],
            'nama'     => $user['nama'],
            'jabatan'  => $user['jabatan'],
            'username' => $user['username']
        ],
        'status_hari_ini' => $statusHariIni,
        'riwayat_terbaru' => array_values($semuaRiwayat)
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
