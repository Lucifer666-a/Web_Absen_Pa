<?php
// absen/admin/update_pin.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pin = trim($_POST['pin_acara'] ?? '');
    $regenerate_token = isset($_POST['regenerate_token']) ? true : false;
    
    if (empty($new_pin)) {
        header("Location: dashboard.php?msg=error_pin");
        exit;
    }

    $updates = ["pin_acara = ?"];
    $params = [$new_pin];

    if ($regenerate_token) {
        $new_token = bin2hex(random_bytes(16)); // Generate 32 char token
        $updates[] = "access_token = ?";
        $params[] = $new_token;
    }

    $sql = "UPDATE pengaturan SET " . implode(", ", $updates) . " ORDER BY id ASC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($params)) {
        header("Location: dashboard.php?msg=success");
    } else {
        header("Location: dashboard.php?msg=error");
    }
    exit;
} else {
    http_response_code(405);
    die("Method Not Allowed");
}
?>
