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

// 1. Validasi Token dan Ambil PIN Aktif
$stmt = $pdo->prepare("SELECT * FROM pengaturan WHERE access_token = ? LIMIT 1");
$stmt->execute([$key]);
$pengaturan = $stmt->fetch();

if (!$pengaturan) {
    http_response_code(403);
    die("Akses ditolak. Token tidak valid.");
}

// 2. Validasi PIN Acara
if ($pin_acara !== $pengaturan['pin_acara']) {
    header("Location: index.php?key=" . urlencode($key) . "&error=pin");
    exit;
}

// 3. Sanitasi & Siapkan IP Address
$ip_address = $_SERVER['REMOTE_ADDR'];

// 4. Insert ke Database
$sql = "INSERT INTO presensi (nama, jabatan, tanda_tangan, ip_address) VALUES (?, ?, ?, ?)";
$insertStmt = $pdo->prepare($sql);
$success = $insertStmt->execute([$nama, $jabatan, $tanda_tangan, $ip_address]);

if ($success) {
    header("Location: success.php?nama=" . urlencode($nama));
    exit;
} else {
    die("Terjadi kesalahan saat menyimpan data.");
}
?>
