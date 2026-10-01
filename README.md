# Web Absen Pa (Admin Panel & REST API Android)

Sistem presensi rapat/acara yang terdiri dari **Admin Panel Web** dan **REST API** untuk terhubung dengan aplikasi **Android**.

---

## 🏗️ Arsitektur Sistem

- **Admin Panel (Web)**: Mengelola acara, melihat rekap presensi, memverifikasi tanda tangan digital, dan download laporan ke format Excel (`.xlsx`).
- **REST API (Backend)**: Menerima request dari aplikasi mobile Android (daftar acara dan submit absensi).
- **Aplikasi Android (Mobile)**: Antarmuka peserta untuk memilih acara, tanda tangan digital, input PIN kehadiran, dan kirim absensi.

---

## 📁 Struktur Direktori

```text
absen/
├── admin/                 # Panel Administrator
│   ├── auth_check.php     # Middleware proteksi sesi admin
│   ├── dashboard.php      # Rekap kehadiran, info REST API, dan export Excel
│   ├── login.php          # Halaman login admin
│   ├── logout.php         # Logout admin
│   └── manage_acara.php   # Handler tambah & ubah status acara (BUKA/TUTUP)
├── api/                   # REST API untuk Aplikasi Android
│   ├── index.php          # Informasi service & endpoint API
│   ├── acara.php          # GET: Daftar acara yang statusnya BUKA
│   ├── absen.php          # POST: Menerima absensi rapat dari Android
│   └── absen_apel.php     # POST: Menerima absensi apel (pagi/sore) dari Android
├── assets/                # Styling CSS & JS
├── config/
│   └── database.php       # Konfigurasi koneksi PDO MySQL & Helper
├── database.sql           # Skema MySQL database (db_absen)
└── index.php              # Redirect otomatis ke admin/login.php
```

---

## 🚀 Endpoint REST API

Semua endpoint menghasilkan response format **JSON** dengan header CORS aktif (`Access-Control-Allow-Origin: *`).

### 1. Ambil Acara Aktif
- **URL**: `GET /absen/api/acara.php`
- **Response Sukses (200 OK)**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama_acara": "Rapat Perdana Acara Default",
      "pin_acara": "123456",
      "status": "BUKA",
      "created_at": "2026-09-30 22:00:00"
    }
  ]
}
```

### 2. Kirim Absensi Rapat / Acara
- **URL**: `POST /absen/api/absen.php`
- **Content-Type**: `application/json` (atau `application/x-www-form-urlencoded`)
- **Request Body (JSON)**:
```json
{
  "acara_id": 1,
  "nama": "Budi Santoso",
  "jabatan": "Staff IT",
  "pin": "123456",
  "tanda_tangan": "data:image/png;base64,iVBORw0KGgoAAA..."
}
```
- **Response Sukses (200 OK)**:
```json
{
  "status": "success",
  "message": "Absensi berhasil disimpan."
}
```

### 3. Kirim Absensi Apel (Pagi / Sore)
- **URL**: `POST /absen/api/absen_apel.php`
- **Content-Type**: `application/json` (atau `application/x-www-form-urlencoded`)
- **Request Body (JSON)**:
```json
{
  "nama": "Budi Santoso",
  "jabatan": "Staf IT",
  "sesi": "pagi",
  "tanda_tangan": "data:image/png;base64,iVBORw0KGgoAAA...",
  "tanggal": "2026-10-05",
  "waktu": "07:30:00"
}
```
- **Response Sukses (200 OK)**:
```json
{
  "status": "success",
  "message": "Absensi apel berhasil disimpan."
}
```
- **Response Error (400 / 500)**:
```json
{
  "status": "error",
  "message": "Nilai sesi tidak valid ('siang'). Sesi harus 'pagi' atau 'sore'."
}
```

---

## 🗄️ Database & Akun Default

- **Database Name**: `db_absen`
- **Tabel Utama**:
  - `admin`: Data login administrator.
  - `acara`: Master acara rapat.
  - `presensi`: Transaksi presensi rapat (relasional ke `acara`).
  - `presensi_apel`: Transaksi presensi apel rutin (Senin pagi & Jumat sore).
- **Default Admin**:
  - Username: `admin`
  - Password: `admin123`


