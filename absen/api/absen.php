<?php
// absen/api/absen.php
// Endpoint REST API: Menerima data absensi dari aplikasi Android (POST JSON / Form)

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
// Strip BOM (\xEF\xBB\xBF) jika ada di awal body
$rawInput = file_get_contents('php://input');
$rawInput = ltrim($rawInput, "\xEF\xBB\xBF");
$input    = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

// Petakan field:
// Prioritas: acara_id > acaraId > event_id
// TAMBAHAN: 'acara' (nama acara string) -> cari id-nya dari DB nanti
$user_id        = (int)(  $input['user_id']      ?? $input['userId']      ?? 0);
$acara_id       = (int)(  $input['acara_id']     ?? $input['acaraId']     ?? $input['event_id']   ?? 0);
$acara_nama_raw = trim(   $input['acara']        ?? ''); // Android mengirim nama acara sebagai string
$nama           = trim(   $input['nama']          ?? $input['name']        ?? '');
$jabatan        = trim(   $input['jabatan']       ?? $input['role']        ?? $input['instansi']   ?? '');
$pin            = trim(   $input['pin']           ?? $input['pin_acara']   ?? $input['pinAcara']   ?? '');
$tanda_tangan   = trim(   $input['tanda_tangan']  ?? $input['tandaTangan'] ?? $input['signature']  ?? '');

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

// Validasi: laporkan field mana yang kosong agar mudah debug di Android
$missing = [];
// acara_id boleh 0 jika 'acara' (nama) dikirim -- akan di-resolve dari DB
if ($acara_id <= 0 && $acara_nama_raw === '') $missing[] = 'acara_id (atau acara)';
if ($nama === '')         $missing[] = 'nama';
if ($jabatan === '')      $missing[] = 'jabatan';
if ($pin === '')          $missing[] = 'pin';
if ($tanda_tangan === '') $missing[] = 'tanda_tangan (atau signature)';

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

// Auto-tambah prefix jika Android kirim raw Base64
if (strpos($tanda_tangan, 'data:image/') !== 0) {
    $tanda_tangan = 'data:image/png;base64,' . str_replace(["\r","\n"," "], ['','','+'], $tanda_tangan);
}

try {
    // Jika acara_id tidak dikirim tapi nama acara dikirim -> cari by nama
    if ($acara_id <= 0 && $acara_nama_raw !== '') {
        $stmtCari = $pdo->prepare("SELECT id, pin_acara, status FROM acara WHERE nama_acara = ? AND status = 'BUKA' LIMIT 1");
        $stmtCari->execute([$acara_nama_raw]);
        $acara = $stmtCari->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, pin_acara, status FROM acara WHERE id = ? LIMIT 1");
        $stmt->execute([$acara_id]);
        $acara = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$acara || $acara['status'] !== 'BUKA') {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Acara tidak ditemukan atau sudah ditutup. Nama/ID: ' . ($acara_nama_raw ?: $acara_id)]);
        exit;
    }

    // Gunakan ID dari hasil lookup
    $acara_id = (int)$acara['id'];

    // Validasi PIN
    if ($pin !== $acara['pin_acara']) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'PIN acara salah.']);
        exit;
    }

    // Cek duplikasi jika user_id dikirim
    if ($user_id > 0) {
        $stmtCek = $pdo->prepare("SELECT id FROM presensi WHERE acara_id = ? AND user_id = ? LIMIT 1");
        $stmtCek->execute([$acara_id, $user_id]);
        if ($stmtCek->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode([
                'status'  => 'error',
                'message' => 'Anda sudah terdaftar/absen pada acara/rapat ini.'
            ]);
            exit;
        }
    }

    // Simpan presensi
    $stmt = $pdo->prepare("INSERT INTO presensi (acara_id, user_id, nama, jabatan, tanda_tangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$acara_id, $user_id > 0 ? $user_id : null, $nama, $jabatan, $tanda_tangan]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Absensi berhasil disimpan.']);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

