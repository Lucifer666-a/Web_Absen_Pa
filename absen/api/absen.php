<?php
// absen/api/absen.php
// Endpoint REST API untuk menerima data absensi dari aplikasi Android via POST JSON / Form

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tangani Preflight Request CORS jika ada
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hanya menerima method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Gunakan method POST.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Baca payload: dukung JSON body (php://input) maupun POST form-data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

// Ambil dan bersihkan input
$acara_id     = isset($input['acara_id']) ? (int)$input['acara_id'] : 0;
$nama         = isset($input['nama']) ? trim($input['nama']) : '';
$jabatan      = isset($input['jabatan']) ? trim($input['jabatan']) : '';
$pin          = isset($input['pin']) ? trim($input['pin']) : '';
$tanda_tangan = isset($input['tanda_tangan']) ? trim($input['tanda_tangan']) : '';

// 1. Validasi: Semua field wajib diisi
if ($acara_id <= 0 || empty($nama) || empty($jabatan) || empty($pin) || empty($tanda_tangan)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Semua field wajib diisi (acara_id, nama, jabatan, pin, tanda_tangan).'
    ]);
    exit;
}

try {
    // 2. Cek apakah acara ada dan berstatus BUKA
    $stmt = $pdo->prepare("SELECT id, nama_acara, pin_acara, status FROM acara WHERE id = ? LIMIT 1");
    $stmt->execute([$acara_id]);
    $acara = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$acara || $acara['status'] !== 'BUKA') {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Acara tidak ditemukan atau sudah ditutup.'
        ]);
        exit;
    }

    // 3. Validasi PIN Acara
    if ($pin !== $acara['pin_acara']) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'PIN acara salah.'
        ]);
        exit;
    }

    // 4. Validasi format tanda tangan (Base64 Image PNG)
    if (strpos($tanda_tangan, 'data:image/png;base64,') !== 0 && strpos($tanda_tangan, 'data:image/') !== 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Format tanda tangan tidak valid. Harus diawali data:image/png;base64,'
        ]);
        exit;
    }

    // 5. Simpan data absensi ke database
    $insertStmt = $pdo->prepare("INSERT INTO presensi (acara_id, nama, jabatan, tanda_tangan) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([$acara_id, $nama, $jabatan, $tanda_tangan]);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Absensi berhasil disimpan.'
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ]);
}
