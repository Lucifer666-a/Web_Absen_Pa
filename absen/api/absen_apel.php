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
$user_id       = (int)($input['user_id']     ?? $input['userId']     ?? 0);
$nama          = trim($input['nama']         ?? $input['name']       ?? '');
$jabatan       = trim($input['jabatan']      ?? $input['role']       ?? $input['instansi']  ?? '');
$tanda_tangan  = trim($input['tanda_tangan'] ?? $input['tandaTangan']?? $input['signature'] ?? '');
$sesi          = strtolower(trim($input['sesi'] ?? $input['session'] ?? ''));
$tanggal       = trim($input['tanggal']      ?? $input['date']       ?? date('Y-m-d'));
$waktu         = trim($input['waktu']        ?? $input['time']       ?? date('H:i:s'));

// Jika user_id dikirim tapi nama/jabatan kosong -> auto-fetch dari DB users
if ($user_id > 0 && ($nama === '' || $jabatan === '')) {
    $stmtFetchUser = $pdo->prepare("SELECT nama, jabatan FROM users WHERE id = ? LIMIT 1");
    $stmtFetchUser->execute([$user_id]);
    $uData = $stmtFetchUser->fetch(PDO::FETCH_ASSOC);
    if ($uData) {
        if ($nama === '') $nama = $uData['nama'];
        if ($jabatan === '') $jabatan = $uData['jabatan'];
    }
}

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
    // Cek duplikasi jika user_id dikirim
    if ($user_id > 0) {
        $stmtCek = $pdo->prepare("SELECT id FROM presensi_apel WHERE user_id = ? AND tanggal = ? AND sesi = ? LIMIT 1");
        $stmtCek->execute([$user_id, $tanggal, $sesi]);
        if ($stmtCek->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                'status'  => 'error',
                'message' => 'Anda sudah melakukan absensi apel ' . strtoupper($sesi) . ' pada tanggal ini (' . $tanggal . ').'
            ]);
            exit;
        }
    }

    // Simpan presensi apel
    $stmt = $pdo->prepare("INSERT INTO presensi_apel (user_id, nama, jabatan, tanda_tangan, sesi, tanggal, waktu) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id > 0 ? $user_id : null, $nama, $jabatan, $tanda_tangan, $sesi, $tanggal, $waktu]);

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

