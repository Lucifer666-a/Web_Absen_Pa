<?php
// absen/admin/dashboard.php
require_once 'auth_check.php';

// Ambil Token Statis
$stmtToken = $pdo->query("SELECT access_token FROM pengaturan LIMIT 1");
$pengaturan = $stmtToken->fetch();
$access_token = $pengaturan['access_token'] ?? '';

$url_presensi = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/admin/dashboard.php', '/index.php', $_SERVER['PHP_SELF']) . "?key=" . $access_token;

// Ambil daftar semua acara untuk dropdown
$stmtListAcara = $pdo->query("SELECT * FROM acara ORDER BY id DESC");
$list_acara = $stmtListAcara->fetchAll();

// Tentukan acara yang sedang dipilih untuk dirender
$selected_acara_id = $_GET['acara_id'] ?? null;

if (!$selected_acara_id && count($list_acara) > 0) {
    // Default ke acara terbaru/aktif
    $selected_acara_id = $list_acara[0]['id'];
}

$active_acara = null;
foreach ($list_acara as $acara) {
    if ($acara['id'] == $selected_acara_id) {
        $active_acara = $acara;
        break;
    }
}

// Ambil data presensi berdasarkan acara yang dipilih
$data_presensi = [];
if ($selected_acara_id) {
    $presensiStmt = $pdo->prepare("SELECT * FROM presensi WHERE acara_id = ? ORDER BY waktu_absen DESC");
    $presensiStmt->execute([$selected_acara_id]);
    $data_presensi = $presensiStmt->fetchAll();
}
$total_hadir = count($data_presensi);

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Presensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .dashboard-header { background: #fff; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-wrapper { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto;}
        .signature-img { max-height: 50px; background: #fff; border: 1px solid #eee; border-radius: 4px; padding: 2px;}
        .status-badge { font-size: 0.8rem; padding: 0.35em 0.65em; }
    </style>
</head>
<body>

<div class="dashboard-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="m-0 text-primary fw-bold">Admin Panel Presensi</h4>
        <small class="text-muted">Halo, <?= htmlspecialchars($_SESSION['admin_nama']) ?></small>
    </div>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
</div>

<div class="container pb-5">
    <?php if ($msg === 'success'): ?>
        <div class="alert alert-success">Perubahan acara berhasil disimpan.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger">Terjadi kesalahan pada sistem.</div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <h6 class="text-muted fw-bold">Pengaturan Aplikasi Android</h6>
                    <p class="mb-1 fw-bold">URL Endpoint Statis (Untuk Webview):</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="linkPresensi" value="<?= htmlspecialchars($url_presensi) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">Copy</button>
                    </div>
                    <small class="text-info">*Link ini bersifat statis dan permanen. Gunakan link ini di aplikasi mobile Android Anda.</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <button type="button" class="btn btn-primary w-100 mb-2 py-3" data-bs-toggle="modal" data-bs-target="#newAcaraModal">
                        + Buat Acara Baru
                    </button>
                    <small class="text-muted">Setiap acara baru otomatis akan menutup acara sebelumnya.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter dan Tabel -->
    <div class="table-wrapper">
        <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <h5 class="m-0 fw-bold">Rekap Presensi</h5>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
                <form method="GET" action="dashboard.php" class="d-flex align-items-center">
                    <label class="me-2 fw-bold whitespace-nowrap text-nowrap">Pilih Acara:</label>
                    <select name="acara_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach($list_acara as $acara): ?>
                            <option value="<?= $acara['id'] ?>" <?= $selected_acara_id == $acara['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acara['nama_acara']) ?> (<?= $acara['status'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($active_acara): ?>
                <button class="btn btn-success btn-sm text-nowrap" onclick="exportToExcel()">Download Excel</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($active_acara): ?>
            <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                <div>
                    <strong>Informasi Acara Dipilih:</strong><br>
                    Nama: <?= htmlspecialchars($active_acara['nama_acara']) ?> <br>
                    PIN: <span class="badge bg-dark"><?= htmlspecialchars($active_acara['pin_acara']) ?></span><br>
                    Status: <span class="badge status-badge <?= $active_acara['status'] == 'BUKA' ? 'bg-success' : 'bg-danger' ?>"><?= $active_acara['status'] ?></span>
                </div>
                <div>
                    <!-- Form Toggle Status Acara -->
                    <form method="POST" action="manage_acara.php">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="acara_id" value="<?= $active_acara['id'] ?>">
                        <input type="hidden" name="current_status" value="<?= $active_acara['status'] ?>">
                        <button type="submit" class="btn btn-sm <?= $active_acara['status'] == 'BUKA' ? 'btn-danger' : 'btn-success' ?>">
                            Ubah ke <?= $active_acara['status'] == 'BUKA' ? 'TUTUP' : 'BUKA' ?>
                        </button>
                    </form>
                </div>
            </div>

            <table class="table table-hover align-middle" id="presensiTable">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Waktu Absen</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_hadir > 0): ?>
                        <?php $no=1; foreach($data_presensi as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($row['waktu_absen'])) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                <td>
                                    <img src="<?= $row['tanda_tangan'] ?>" alt="Tanda Tangan" class="signature-img">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Belum ada data kehadiran pada acara ini</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-4">Belum ada acara yang dibuat.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Buat Acara Baru -->
<div class="modal fade" id="newAcaraModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buat Acara Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="manage_acara.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nama Acara</label>
                <input type="text" class="form-control" name="nama_acara" required placeholder="Contoh: Seminar IT 2026">
            </div>
            <div class="mb-3">
                <label class="form-label">PIN Acara</label>
                <input type="text" class="form-control" name="pin_acara" required placeholder="Contoh: 123456">
                <div class="form-text">Peserta harus memasukkan PIN ini untuk bisa absen.</div>
            </div>
            <div class="alert alert-warning py-2 mb-0 mt-3" style="font-size: 0.9rem;">
                Acara lain yang berstatus BUKA akan otomatis ditutup saat acara baru dibuat.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Buat & Buka Acara</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyLink() {
    var copyText = document.getElementById("linkPresensi");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Link disalin!");
}

function exportToExcel() {
    var table = document.getElementById("presensiTable");
    var cloneTable = table.cloneNode(true);
    for (var i = 0; i < cloneTable.rows.length; i++) {
        // Hapus kolom tanda tangan (index ke-4 / terakhir)
        if(cloneTable.rows[i].cells.length > 4){
            cloneTable.rows[i].deleteCell(4);
        }
    }
    
    var wb = XLSX.utils.table_to_book(cloneTable, {sheet:"Presensi"});
    XLSX.writeFile(wb, "Rekap_Presensi_<?= $active_acara ? preg_replace('/[^A-Za-z0-9\-]/', '_', $active_acara['nama_acara']) : 'Acara' ?>.xlsx");
}
</script>
</body>
</html>
