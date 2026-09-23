<?php
// absen/admin/dashboard.php
require_once 'auth_check.php';

// Ambil data pengaturan (Token & PIN)
$stmt = $pdo->query("SELECT * FROM pengaturan ORDER BY id ASC LIMIT 1");
$pengaturan = $stmt->fetch();

$access_token = $pengaturan['access_token'] ?? '';
$pin_acara = $pengaturan['pin_acara'] ?? '';
$nama_acara = $pengaturan['nama_acara'] ?? 'Acara';

$url_presensi = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/admin/dashboard.php', '/index.php', $_SERVER['PHP_SELF']) . "?key=" . $access_token;

// Ambil data presensi
$presensiStmt = $pdo->query("SELECT * FROM presensi ORDER BY waktu_absen DESC");
$data_presensi = $presensiStmt->fetchAll();
$total_hadir = count($data_presensi);

// Handle pesan sukses/error dari update PIN
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Presensi</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SheetJS (Excel Export) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .dashboard-header { background: #fff; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-wrapper { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto;}
        .signature-img { max-height: 50px; background: #fff; border: 1px solid #eee; border-radius: 4px; padding: 2px;}
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
        <div class="alert alert-success">Pengaturan berhasil diperbarui.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger">Gagal memperbarui pengaturan.</div>
    <?php elseif ($msg === 'error_pin'): ?>
        <div class="alert alert-warning">PIN tidak boleh kosong.</div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <h6 class="text-muted fw-bold">Informasi Acara</h6>
                    <h5 class="mb-3"><?= htmlspecialchars($nama_acara) ?></h5>
                    
                    <p class="mb-1 fw-bold">Link Presensi:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="linkPresensi" value="<?= htmlspecialchars($url_presensi) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">Copy</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted fw-bold">Total Kehadiran</h6>
                    <h1 class="display-4 fw-bold text-primary my-2"><?= $total_hadir ?></h1>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pinModal">
                        Update PIN / Token
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 fw-bold">Data Rekap Presensi</h5>
            <button class="btn btn-success btn-sm" onclick="exportToExcel()">Download Excel</button>
        </div>
        <table class="table table-hover align-middle" id="presensiTable">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Waktu Absen</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>IP Address</th>
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
                            <td><?= htmlspecialchars($row['ip_address']) ?></td>
                            <td>
                                <img src="<?= $row['tanda_tangan'] ?>" alt="Tanda Tangan" class="signature-img">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Belum ada data kehadiran</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Update PIN & Token -->
<div class="modal fade" id="pinModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Pengaturan Keamanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="update_pin.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">PIN Acara Saat Ini</label>
                <input type="text" class="form-control" name="pin_acara" value="<?= htmlspecialchars($pin_acara) ?>" required>
                <div class="form-text">Ubah PIN untuk membatasi peserta yang hadir di sesi tertentu.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="regenerate_token" id="regenToken">
                <label class="form-check-label" for="regenToken">
                    Generate Ulang Link Presensi (Token Baru)
                </label>
                <div class="form-text text-danger">Awas: Link presensi yang lama akan hangus dan tidak bisa diakses lagi!</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
    // Karena SheetJS tidak bisa export gambar base64 langsung dengan format ini (perlu penanganan ekstra),
    // kita hapus kolom ke-6 (Tanda Tangan) dari export untuk menghindari output text base64 yang sangat panjang di excel.
    
    // Clone table untuk dimanipulasi
    var cloneTable = table.cloneNode(true);
    for (var i = 0; i < cloneTable.rows.length; i++) {
        if(cloneTable.rows[i].cells.length > 5){
            cloneTable.rows[i].deleteCell(5);
        }
    }
    
    var wb = XLSX.utils.table_to_book(cloneTable, {sheet:"Presensi"});
    XLSX.writeFile(wb, "Rekap_Presensi_<?= date('Y-m-d') ?>.xlsx");
}
</script>
</body>
</html>
