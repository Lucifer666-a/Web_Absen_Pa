<?php
// absen/success.php
$nama = $_GET['nama'] ?? 'Peserta';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kehadiran Berhasil</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .success-icon {
            font-size: 5rem;
            color: #10b981;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center text-center">
    <div class="glass-card">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
        </div>
        <h2 class="page-title text-success mb-3">Terima Kasih!</h2>
        <p class="page-subtitle text-dark fs-5">Kehadiran <strong><?= htmlspecialchars($nama) ?></strong> telah berhasil dicatat.</p>
        
        <p class="text-muted small mt-4">Silakan tutup halaman ini atau kembali mengikuti rangkaian acara.</p>
    </div>
</div>

</body>
</html>
