<?php
// absen/admin/dashboard.php
require_once 'auth_check.php';

// Base URL REST API untuk Aplikasi Android
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$api_base_url = $protocol . $_SERVER['HTTP_HOST'] . str_replace('/admin/dashboard.php', '/api/', $_SERVER['PHP_SELF']);
$api_acara_url = $api_base_url . 'acara.php';
$api_absen_url = $api_base_url . 'absen.php';
$api_apel_url  = $api_base_url . 'absen_apel.php';

// Tentukan Tab Aktif: 'rapat', 'apel', atau 'users'
$active_tab = $_GET['tab'] ?? 'rapat';
if (!in_array($active_tab, ['rapat', 'apel', 'users'], true)) {
    $active_tab = 'rapat';
}

// ----------------------------------------------------
// DATA TAB 1: PRESENSI RAPAT / ACARA
// ----------------------------------------------------
$stmtListAcara = $pdo->query("SELECT * FROM acara ORDER BY id DESC");
$list_acara = $stmtListAcara->fetchAll();

$selected_acara_id = $_GET['acara_id'] ?? null;
if (!$selected_acara_id && count($list_acara) > 0) {
    $selected_acara_id = $list_acara[0]['id'];
}

$active_acara = null;
foreach ($list_acara as $acara) {
    if ($acara['id'] == $selected_acara_id) {
        $active_acara = $acara;
        break;
    }
}

$data_presensi = [];
if ($selected_acara_id) {
    $presensiStmt = $pdo->prepare("SELECT * FROM presensi WHERE acara_id = ? ORDER BY waktu_absen DESC");
    $presensiStmt->execute([$selected_acara_id]);
    $data_presensi = $presensiStmt->fetchAll();
}
$total_hadir = count($data_presensi);

// ----------------------------------------------------
// DATA TAB 2: PRESENSI APEL (PAGI / SORE)
// ----------------------------------------------------
$sesi_filter  = $_GET['sesi'] ?? 'semua';
$tgl_mulai    = $_GET['tgl_mulai'] ?? '';
$tgl_selesai  = $_GET['tgl_selesai'] ?? '';

$sqlApel = "SELECT * FROM presensi_apel WHERE 1=1";
$paramsApel = [];

if (in_array($sesi_filter, ['pagi', 'sore'], true)) {
    $sqlApel .= " AND sesi = ?";
    $paramsApel[] = $sesi_filter;
}
if (!empty($tgl_mulai)) {
    $sqlApel .= " AND tanggal >= ?";
    $paramsApel[] = $tgl_mulai;
}
if (!empty($tgl_selesai)) {
    $sqlApel .= " AND tanggal <= ?";
    $paramsApel[] = $tgl_selesai;
}

$sqlApel .= " ORDER BY tanggal DESC, waktu DESC, id DESC";
$stmtApel = $pdo->prepare($sqlApel);
$stmtApel->execute($paramsApel);
$data_presensi_apel = $stmtApel->fetchAll();
$total_apel = count($data_presensi_apel);

// ----------------------------------------------------
// DATA TAB 3: KELOLA USERS / PEGAWAI
// ----------------------------------------------------
$stmtUsers = $pdo->query("SELECT * FROM users ORDER BY nama ASC");
$list_users = $stmtUsers->fetchAll();
$total_users = count($list_users);

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
        .table-wrapper { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        .signature-img { max-height: 50px; background: #fff; border: 1px solid #eee; border-radius: 4px; padding: 2px; }
        .status-badge { font-size: 0.8rem; padding: 0.35em 0.65em; }
        .nav-tabs .nav-link { color: #6c757d; border: none; border-bottom: 3px solid transparent; font-weight: 500; }
        .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; background: transparent; }
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
        <div class="alert alert-success">Perubahan berhasil disimpan.</div>
    <?php elseif ($msg === 'error_duplicate'): ?>
        <div class="alert alert-danger">NIP atau Username sudah digunakan oleh pengguna lain.</div>
    <?php elseif ($msg === 'error_fields'): ?>
        <div class="alert alert-warning">Mohon lengkapi seluruh field yang wajib diisi.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger">Terjadi kesalahan pada sistem.</div>
    <?php endif; ?>

    <!-- Header REST API & Tombol Buat Acara -->
    <div class="row mb-4">
        <div class="col-md-8 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted fw-bold mb-0">Integrasi REST API (Aplikasi Android)</h6>
                        <span class="badge bg-success-subtle text-success border">REST API Aktif</span>
                    </div>
                    <p class="mb-1 text-secondary small">Base URL API yang dimasukkan ke aplikasi Android:</p>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light text-muted small">Base API</span>
                        <input type="text" class="form-control font-monospace" id="linkApiBase" value="<?= htmlspecialchars($api_base_url) ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copyApiLink('linkApiBase')">Copy Base URL</button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-info-subtle text-info-emphasis border" title="Login pengguna/pegawai">POST /api/login.php</span>
                        <span class="badge bg-purple-subtle text-primary border" title="Ambil status harian & riwayat lintas device">GET /api/user_status.php</span>
                        <span class="badge bg-success-subtle text-success border" title="Check-in & Check-out harian">POST /api/absen_harian.php</span>
                        <span class="badge bg-primary-subtle text-primary border" title="Ambil daftar acara rapat BUKA">GET /api/acara.php</span>
                        <span class="badge bg-success-subtle text-success border" title="Kirim presensi rapat">POST /api/absen.php</span>
                        <span class="badge bg-warning-subtle text-warning border text-dark" title="Kirim presensi apel pagi/sore">POST /api/absen_apel.php</span>
                    </div>
                    <small class="text-muted d-block mt-2">*Aplikasi Android memanggil endpoint di atas untuk login, sinkronisasi riwayat, dan absensi.</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-stat h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center gap-2">
                    <button type="button" class="btn btn-primary w-100 py-2" data-bs-toggle="modal" data-bs-target="#newAcaraModal">
                        + Buat Acara Rapat Baru
                    </button>
                    <button type="button" class="btn btn-outline-success w-100 py-2" data-bs-toggle="modal" data-bs-target="#newUserModal">
                        + Tambah Pegawai Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigasi Tab (Absen Rapat vs Absen Apel vs Kelola Pegawai) -->
    <ul class="nav nav-tabs mb-3 border-bottom-0">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'rapat' ? 'active' : '' ?>" href="?tab=rapat<?= $selected_acara_id ? '&acara_id='.$selected_acara_id : '' ?>">
                📋 Presensi Rapat / Acara
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'apel' ? 'active' : '' ?>" href="?tab=apel">
                📣 Presensi Apel (Pagi / Sore)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'users' ? 'active' : '' ?>" href="?tab=users">
                👥 Data Pegawai / Users (<?= $total_users ?>)
            </a>
        </li>
    </ul>

    <div class="table-wrapper">
        <?php if ($active_tab === 'rapat'): ?>
            <!-- ================= TAB 1: PRESENSI RAPAT ================= -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h5 class="m-0 fw-bold">Rekap Presensi Rapat</h5>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
                    <form method="GET" action="dashboard.php" class="d-flex align-items-center">
                        <input type="hidden" name="tab" value="rapat">
                        <label class="me-2 fw-bold text-nowrap">Pilih Acara:</label>
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
                <p class="text-muted text-center py-4">Belum ada acara rapat yang dibuat.</p>
            <?php endif; ?>

        <?php elseif ($active_tab === 'apel'): ?>
            <!-- ================= TAB 2: PRESENSI APEL ================= -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <h5 class="m-0 fw-bold">Rekap Presensi Apel</h5>
                    <small class="text-muted">Apel Senin Pagi & Apel Jumat Sore</small>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-success btn-sm" onclick="exportApelToExcel()">
                        📊 Download Excel Apel
                    </button>
                </div>
            </div>

            <!-- Form Filter Presensi Apel -->
            <form method="GET" action="dashboard.php" class="row g-2 mb-4 align-items-end p-3 bg-light rounded border">
                <input type="hidden" name="tab" value="apel">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Filter Sesi Apel:</label>
                    <select name="sesi" class="form-select form-select-sm">
                        <option value="semua" <?= $sesi_filter === 'semua' ? 'selected' : '' ?>>Semua Sesi (Pagi & Sore)</option>
                        <option value="pagi" <?= $sesi_filter === 'pagi' ? 'selected' : '' ?>>Apel Pagi (Senin)</option>
                        <option value="sore" <?= $sesi_filter === 'sore' ? 'selected' : '' ?>>Apel Sore (Jumat)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Tanggal Mulai:</label>
                    <input type="date" name="tgl_mulai" class="form-control form-select-sm" value="<?= htmlspecialchars($tgl_mulai) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Tanggal Selesai:</label>
                    <input type="date" name="tgl_selesai" class="form-control form-select-sm" value="<?= htmlspecialchars($tgl_selesai) ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">Terapkan Filter</button>
                    <a href="dashboard.php?tab=apel" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>

            <table class="table table-hover align-middle" id="presensiApelTable">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Jam</th>
                        <th>Sesi Apel</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_apel > 0): ?>
                        <?php $no=1; foreach($data_presensi_apel as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($row['tanggal'])) ?> 
                                    <small class="text-muted ms-1">(<?= date('H:i:s', strtotime($row['waktu'])) ?>)</small>
                                </td>
                                <td>
                                    <?php if ($row['sesi'] === 'pagi'): ?>
                                        <span class="badge bg-primary">🌅 Pagi (Senin)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">🌇 Sore (Jumat)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                <td>
                                    <?php if (!empty($row['tanda_tangan'])): ?>
                                        <img src="<?= $row['tanda_tangan'] ?>" alt="Tanda Tangan" class="signature-img">
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data presensi apel sesuai filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($active_tab === 'users'): ?>
            <!-- ================= TAB 3: DATA PEGAWAI / USERS ================= -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h5 class="m-0 fw-bold">Daftar Pegawai / Pengguna Android</h5>
                    <small class="text-muted">Akun ini digunakan pegawai untuk login di aplikasi Android.</small>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
                        + Tambah Pegawai Baru
                    </button>
                </div>
            </div>

            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Username</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_users > 0): ?>
                        <?php $no=1; foreach($list_users as $user): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($user['nip'] ?: '-') ?></td>
                                <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                                <td><?= htmlspecialchars($user['jabatan']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($user['username']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="openEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        Edit / Password
                                    </button>
                                    <form action="manage_users.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pegawai ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada pegawai terdaftar. Silakan klik tombol "Tambah Pegawai Baru".</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Buat Acara Baru -->
<div class="modal fade" id="newAcaraModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buat Acara Rapat Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="manage_acara.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nama Acara</label>
                <input type="text" class="form-control" name="nama_acara" required placeholder="Contoh: Rapat Koordinasi Mingguan">
            </div>
            <div class="mb-3">
                <label class="form-label">PIN Acara</label>
                <input type="text" class="form-control" name="pin_acara" required placeholder="Contoh: 123456">
                <div class="form-text">Peserta harus memasukkan PIN ini untuk bisa absen rapat.</div>
            </div>
            <div class="alert alert-warning py-2 mb-0 mt-3" style="font-size: 0.9rem;">
                Acara lain yang berstatus BUKA akan otomatis ditutup saat acara rapat baru dibuat.
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

<!-- Modal Tambah Pegawai Baru -->
<div class="modal fade" id="newUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Pegawai Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="manage_users.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">NIP (Opsional)</label>
                <input type="text" class="form-control" name="nip" placeholder="Contoh: 199001012020121001">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" class="form-control" name="nama" required placeholder="Contoh: Ahmad Fauzi">
            </div>
            <div class="mb-3">
                <label class="form-label">Jabatan *</label>
                <input type="text" class="form-control" name="jabatan" required placeholder="Contoh: Staf IT">
            </div>
            <div class="mb-3">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" name="username" required placeholder="Contoh: pegawai1">
            </div>
            <div class="mb-3">
                <label class="form-label">Password *</label>
                <input type="password" class="form-control" name="password" required placeholder="Masukkan password awal">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Simpan Pegawai</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Pegawai -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Pegawai / Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="manage_users.php" method="POST">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="user_id" id="edit_user_id">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">NIP (Opsional)</label>
                <input type="text" class="form-control" name="nip" id="edit_nip">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" class="form-control" name="nama" id="edit_nama" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Jabatan *</label>
                <input type="text" class="form-control" name="jabatan" id="edit_jabatan" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" name="username" id="edit_username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru (Biarkan kosong jika tidak diubah)</label>
                <input type="password" class="form-control" name="password" placeholder="Isi hanya jika ingin mereset password">
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
function copyApiLink(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Base URL REST API disalin:\n" + copyText.value);
}

function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_nip').value = user.nip || '';
    document.getElementById('edit_nama').value = user.nama || '';
    document.getElementById('edit_jabatan').value = user.jabatan || '';
    document.getElementById('edit_username').value = user.username || '';
    var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function exportToExcel() {
    var table = document.getElementById("presensiTable");
    if (!table) return;
    var cloneTable = table.cloneNode(true);
    for (var i = 0; i < cloneTable.rows.length; i++) {
        if (cloneTable.rows[i].cells.length > 4) {
            cloneTable.rows[i].deleteCell(4);
        }
    }
    var wb = XLSX.utils.table_to_book(cloneTable, {sheet:"Presensi Rapat"});
    XLSX.writeFile(wb, "Rekap_Presensi_Rapat_<?= $active_acara ? preg_replace('/[^A-Za-z0-9\-]/', '_', $active_acara['nama_acara']) : 'Acara' ?>.xlsx");
}

function exportApelToExcel() {
    var table = document.getElementById("presensiApelTable");
    if (!table) return;
    var cloneTable = table.cloneNode(true);
    for (var i = 0; i < cloneTable.rows.length; i++) {
        if (cloneTable.rows[i].cells.length > 5) {
            cloneTable.rows[i].deleteCell(5);
        }
    }
    var wb = XLSX.utils.table_to_book(cloneTable, {sheet:"Presensi Apel"});
    XLSX.writeFile(wb, "Rekap_Presensi_Apel_<?= htmlspecialchars($sesi_filter) ?>_<?= date('Y-m-d') ?>.xlsx");
}
</script>
</body>
</html>
