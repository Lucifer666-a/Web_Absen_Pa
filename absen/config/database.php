<?php
// absen/config/database.php

date_default_timezone_set('Asia/Jakarta');

$host = '127.0.0.1';
$db   = 'db_absen';
$user = 'root';
$pass = ''; // Sesuaikan dengan password database local Anda
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Otomatis pastikan tabel presensi_apel tersedia
    $pdo->exec("CREATE TABLE IF NOT EXISTS `presensi_apel` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `nama` VARCHAR(150) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `tanda_tangan` MEDIUMTEXT NULL,
        `sesi` ENUM('pagi', 'sore') NOT NULL,
        `tanggal` DATE NOT NULL,
        `waktu` TIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (\PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

/**
 * Helper: Validasi sesi admin sudah login
 */
function checkAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: ../admin/login.php");
        exit;
    }
}
?>
