<?php
// absen/index.php
require_once 'config/database.php';

$key = $_GET['key'] ?? '';
$error = '';

if (empty($key)) {
    http_response_code(403);
    die("<h1>403 Forbidden</h1><p>Akses ditolak. Token tidak ditemukan.</p>");
}

// 1. Validasi Token Statis
$stmt = $pdo->prepare("SELECT * FROM pengaturan WHERE access_token = ? LIMIT 1");
$stmt->execute([$key]);
$pengaturan = $stmt->fetch();

if (!$pengaturan) {
    http_response_code(403);
    die("<h1>403 Forbidden</h1><p>Akses ditolak. Token tidak valid.</p>");
}

// 2. Ambil Acara Aktif
$stmtAcara = $pdo->query("SELECT * FROM acara WHERE status = 'BUKA' ORDER BY id DESC LIMIT 1");
$acara_aktif = $stmtAcara->fetch();

$is_closed = !$acara_aktif;

// Cek pesan error dari redirect
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'pin') {
        $error = "PIN Acara tidak valid!";
    } elseif ($_GET['error'] == 'empty') {
        $error = "Mohon lengkapi semua data, termasuk tanda tangan.";
    } elseif ($_GET['error'] == 'closed') {
        $error = "Presensi sedang ditutup oleh Admin.";
        $is_closed = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi - <?= $is_closed ? 'Ditutup' : htmlspecialchars($acara_aktif['nama_acara']) ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="glass-card">
        <h2 class="page-title">Presensi Kehadiran</h2>
        <p class="page-subtitle">
            <?= $is_closed ? '<span class="text-danger fw-bold">Tidak ada acara yang sedang aktif</span>' : htmlspecialchars($acara_aktif['nama_acara']) ?>
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($is_closed): ?>
            <div class="alert alert-warning text-center">
                <strong>Mohon Maaf!</strong><br>
                Presensi saat ini sedang ditutup oleh Admin.
            </div>
        <?php else: ?>
            <form id="absen-form" action="proses_absen.php" method="POST">
                <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" required placeholder="Masukkan nama lengkap Anda">
                </div>

                <div class="mb-3">
                    <label for="jabatan" class="form-label">Instansi / Jabatan</label>
                    <input type="text" class="form-control" id="jabatan" name="jabatan" required placeholder="Contoh: PT. ABC / Direktur">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanda Tangan</label>
                    <div class="signature-wrapper">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-clear" id="clear-signature">Bersihkan</button>
                        <canvas id="signature-pad"></canvas>
                    </div>
                    <input type="hidden" name="tanda_tangan" id="signature64">
                </div>

                <div class="mb-4">
                    <label for="pin_acara" class="form-label">PIN Acara</label>
                    <input type="password" class="form-control" id="pin_acara" name="pin_acara" required placeholder="Masukkan PIN dari panitia">
                </div>

                <button type="submit" class="btn btn-primary w-100">Kirim Kehadiran</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Signature Pad JS -->
<?php if (!$is_closed): ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/signature.js"></script>
<?php endif; ?>
</body>
</html>
