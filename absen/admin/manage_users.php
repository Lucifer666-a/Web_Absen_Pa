<?php
// absen/admin/manage_users.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php?tab=users");
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $nip      = trim($_POST['nip'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $jabatan  = trim($_POST['jabatan'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($nama === '' || $jabatan === '' || $username === '' || $password === '') {
            header("Location: dashboard.php?tab=users&msg=error_fields");
            exit;
        }

        $hashPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (nip, nama, jabatan, username, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nip ?: null, $nama, $jabatan, $username, $hashPassword]);

        header("Location: dashboard.php?tab=users&msg=success");
        exit;
    }

    if ($action === 'edit') {
        $id       = (int)($_POST['user_id'] ?? 0);
        $nip      = trim($_POST['nip'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $jabatan  = trim($_POST['jabatan'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($id <= 0 || $nama === '' || $jabatan === '' || $username === '') {
            header("Location: dashboard.php?tab=users&msg=error_fields");
            exit;
        }

        if ($password !== '') {
            $hashPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nip = ?, nama = ?, jabatan = ?, username = ?, password = ? WHERE id = ?");
            $stmt->execute([$nip ?: null, $nama, $jabatan, $username, $hashPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nip = ?, nama = ?, jabatan = ?, username = ? WHERE id = ?");
            $stmt->execute([$nip ?: null, $nama, $jabatan, $username, $id]);
        }

        header("Location: dashboard.php?tab=users&msg=success");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: dashboard.php?tab=users&msg=success");
        exit;
    }

} catch (\PDOException $e) {
    if (strpos($e->getMessage(), '1062') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        header("Location: dashboard.php?tab=users&msg=error_duplicate");
        exit;
    }
    header("Location: dashboard.php?tab=users&msg=error");
    exit;
}
