<?php
// absen/api/acara.php
// Endpoint REST API untuk mengambil daftar acara yang sedang BUKA

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tangani Preflight Request CORS jika ada
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hanya menerima method GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Gunakan method GET.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Ambil semua acara yang statusnya BUKA
    $stmt = $pdo->query("SELECT id, nama_acara, pin_acara, status, created_at FROM acara WHERE status = 'BUKA' ORDER BY id DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format tipe data agar konsisten
    $formattedData = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'nama_acara' => $row['nama_acara'],
            'pin_acara' => $row['pin_acara'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }, $data);

    echo json_encode([
        'status' => 'success',
        'data' => $formattedData
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ]);
}
