<?php
// absen/index.php
// Absensi peserta via web telah dinonaktifkan dan dialihkan sepenuhnya ke aplikasi Android.
// Pengunjung halaman web langsung diarahkan ke Admin Panel.

header("Location: admin/login.php");
exit;
?>
