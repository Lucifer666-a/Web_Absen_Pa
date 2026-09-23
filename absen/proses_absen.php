<?php
// absen/proses_absen.php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

$key = $_POST['key'] ?? '';
$nama = trim($_POST['nama'] ?? '');
$jabatan = trim($_POST['jabatan'] ?? '');
$pin_acara = trim($_POST['pin_acara'] ?? '');
$tanda_tangan = $_POST['tanda_tangan'] ?? '';

if (empty($key) || empty($nama) || empty($jabatan) || empty($pin_acara) || empty($tanda_tangan)) {
    header("Location: index.php?key=" . urlencode($key) . "&error=empty");
    exit;
}

// 1. Validasi Token Statis
$stmt = $pdo->prepare("SELECT * FROM pengaturan WHERE access_token = ? LIMIT 1");
$stmt->execute([$key]);
$pengaturan = $stmt->fetch();

if (!$pengaturan) {
    http_response_code(403);
    die("Akses ditolak. Token tidak valid.");
}

// 2. Ambil Acara Aktif
$stmtAcara = $pdo->query("SELECT * FROM acara WHERE status = 'BUKA' ORDER BY id DESC LIMIT 1");
$acara_aktif = $stmtAcara->fetch();

if (!$acara_aktif) {
    header("Location: index.php?key=" . urlencode($key) . "&error=closed");
    exit;
}

// 3. Validasi PIN Acara terhadap acara aktif
if ($pin_acara !== $acara_aktif['pin_acara']) {
    header("Location: index.php?key=" . urlencode($key) . "&error=pin");
    exit;
}

// 4. Insert ke Database (Menyimpan acara_id dan tanpa ip_address)
$sql = "INSERT INTO presensi (acara_id, nama, jabatan, tanda_tangan) VALUES (?, ?, ?, ?)";
$insertStmt = $pdo->prepare($sql);
$success = $insertStmt->execute([$acara_aktif['id'], $nama, $jabatan, $tanda_tangan]);

if ($success) {
    header("Location: success.php?nama=" . urlencode($nama));
    exit;
} else {
    die("Terjadi kesalahan saat menyimpan data.");
}
?>
