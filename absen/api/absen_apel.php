<?php
// absen/api/absen_apel.php
// Endpoint REST API: Menerima data presensi apel (pagi/sore) dari aplikasi Android

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan method POST.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Baca body: JSON atau Form POST
$rawInput = file_get_contents('php://input');
$rawInput = ltrim($rawInput, "\xEF\xBB\xBF"); // Strip BOM jika ada
$input    = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

// Petakan field:
$nama          = trim($input['nama']         ?? $input['name']       ?? '');
$jabatan       = trim($input['jabatan']      ?? $input['role']       ?? $input['instansi']  ?? '');
$tanda_tangan  = trim($input['tanda_tangan'] ?? $input['tandaTangan']?? $input['signature'] ?? '');
$sesi          = strtolower(trim($input['sesi'] ?? $input['session'] ?? ''));
$tanggal       = trim($input['tanggal']      ?? $input['date']       ?? date('Y-m-d'));
$waktu         = trim($input['waktu']        ?? $input['time']       ?? date('H:i:s'));

// Validasi field kosong
$missing = [];
if ($nama === '')         $missing[] = 'nama';
if ($jabatan === '')      $missing[] = 'jabatan';
if ($sesi === '')         $missing[] = 'sesi';
if ($tanda_tangan === '') $missing[] = 'tanda_tangan';

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'status'          => 'error',
        'message'         => 'Field kosong/tidak dikirim: ' . implode(', ', $missing),
        'missing_fields'  => $missing,
        'received_fields' => is_array($input) ? array_keys($input) : [],
        'raw_body_length' => strlen($rawInput),
    ]);
    exit;
}

// Validasi nilai sesi (hanya 'pagi' atau 'sore')
if (!in_array($sesi, ['pagi', 'sore'], true)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => "Nilai sesi tidak valid ('" . htmlspecialchars($sesi) . "'). Sesi harus 'pagi' atau 'sore'."
    ]);
    exit;
}

// Auto-tambah prefix jika Android kirim raw Base64
if (strpos($tanda_tangan, 'data:image/') !== 0) {
    $tanda_tangan = 'data:image/png;base64,' . str_replace(["\r", "\n", " "], ['', '', '+'], $tanda_tangan);
}

try {
    // Simpan presensi apel
    $stmt = $pdo->prepare("INSERT INTO presensi_apel (nama, jabatan, tanda_tangan, sesi, tanggal, waktu) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $jabatan, $tanda_tangan, $sesi, $tanggal, $waktu]);

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Absensi apel berhasil disimpan.'
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
