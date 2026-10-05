<?php
// absen/api/login.php
// Endpoint REST API: Login User / Pegawai dari aplikasi Android

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan method POST.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$rawInput = file_get_contents('php://input');
$rawInput = ltrim($rawInput, "\xEF\xBB\xBF");
$input    = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Username dan password wajib diisi.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nip, nama, jabatan, username, password FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Username atau password salah.'
        ]);
        exit;
    }

    // Success response - sertakan jabatan & profil baik di root maupun di 'data' agar kompatibel dengan berbagai model Android
    http_response_code(200);
    echo json_encode([
        'status'   => 'success',
        'message'  => 'Login berhasil.',
        'user_id'  => (int)$user['id'],
        'username' => $user['username'],
        'nama'     => $user['nama'],
        'jabatan'  => $user['jabatan'],
        'nip'      => $user['nip'],
        'data'     => [
            'user_id'  => (int)$user['id'],
            'nip'      => $user['nip'],
            'nama'     => $user['nama'],
            'jabatan'  => $user['jabatan'],
            'username' => $user['username']
        ]
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
