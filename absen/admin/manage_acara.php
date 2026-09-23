<?php
// absen/admin/manage_acara.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama_acara = trim($_POST['nama_acara'] ?? '');
        $pin_acara = trim($_POST['pin_acara'] ?? '');

        if (!empty($nama_acara) && !empty($pin_acara)) {
            // Tutup semua acara yang sedang buka
            $pdo->query("UPDATE acara SET status = 'TUTUP' WHERE status = 'BUKA'");

            // Insert acara baru
            $sql = "INSERT INTO acara (nama_acara, pin_acara, status) VALUES (?, ?, 'BUKA')";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nama_acara, $pin_acara])) {
                header("Location: dashboard.php?msg=success");
                exit;
            }
        }
    } elseif ($action === 'toggle_status') {
        $acara_id = $_POST['acara_id'] ?? '';
        $current_status = $_POST['current_status'] ?? '';
        
        $new_status = ($current_status === 'BUKA') ? 'TUTUP' : 'BUKA';

        // Jika mengubah menjadi BUKA, tutup yang lain dulu agar hanya ada 1 yg BUKA
        if ($new_status === 'BUKA') {
            $pdo->query("UPDATE acara SET status = 'TUTUP' WHERE status = 'BUKA'");
        }

        $stmt = $pdo->prepare("UPDATE acara SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $acara_id])) {
            header("Location: dashboard.php?acara_id=" . $acara_id . "&msg=success");
            exit;
        }
    }

    // Fallback jika error
    header("Location: dashboard.php?msg=error");
    exit;
} else {
    http_response_code(405);
    die("Method Not Allowed");
}
?>
