Dari tiga screenshot, panelmu sudah cukup jauh. Yang kurang terutama tab absen harian dan beberapa hal pelengkap.

## Yang sudah ada

| Bagian | Isi yang sudah jalan |
|---|---|
| **Header** | Nama panel, sapaan admin, tombol Logout |
| **Kartu REST API** | Base URL, tombol salin, daftar endpoint (`login`, `user_status`, `absen_harian`, `acara`, `absen`, `absen_apel`) |
| **Aksi cepat** | Buat Acara Rapat Baru, Tambah Pegawai Baru |
| **Tab Presensi Rapat** | Pilih acara, info acara (nama, PIN, status), tombol ubah ke TUTUP, tabel (waktu, nama, jabatan, tanda tangan), Download Excel |
| **Tab Presensi Apel** | Filter sesi dan rentang tanggal, tabel (tanggal dan jam, sesi, nama, jabatan, tanda tangan), Download Excel Apel |
| **Tab Data Pegawai** | Daftar akun (NIP, nama, jabatan, username), Edit/Password, Hapus, Tambah Pegawai |

## Yang masih perlu ditambah

**Wajib (sesuai spesifikasi dan judul laporanmu)**

| Yang ditambah | Keterangan |
|---|---|
| **Tab Absen Harian** | Satu-satunya tab yang belum ada. Kolom: nama, jabatan, tanggal, jam masuk, jam pulang, plus filter tanggal dan pegawai, plus **Download Excel** |
| **Foto dokumentasi** | Kalau foto absen kerja dikirim ke server, tampilkan di tab harian (kolom atau tombol "lihat foto") dan sertakan di Excel |
| **Proteksi akses admin di server** | Halaman dan endpoint unduh Excel harus mengecek login admin di PHP. Coba buka link unduh dalam jendela incognito tanpa login, dan pastikan ditolak |

**Sebaiknya ada**

| Yang ditambah | Alasan |
|---|---|
| **Rekap per pegawai per bulan** | Jumlah apel pagi, apel sore, dan rapat yang diikuti. Ini yang paling berguna untuk admin dan membuat sisi "informasi" terasa |
| **Daftar yang tidak hadir** | Pegawai tanpa absen apel pada tanggal tertentu. Datanya bisa diturunkan dari tabel yang ada |
| **Ringkasan angka di atas tabel** | Contoh: "Hadir 4 dari 5 pegawai" |
| **Filter pegawai di tab apel** | Sekarang hanya ada filter sesi dan tanggal |
| **Pencarian dan pagination** | Supaya tabel tetap cepat saat data bertambah |
| **Daftar semua rapat** | Sekarang rapat dipilih lewat dropdown. Tambahkan tampilan daftar rapat dengan tanggal, status, serta aksi buka, tutup, edit, dan hapus |
| **Nonaktifkan pegawai** | Ganti atau lengkapi tombol Hapus, karena menghapus akun bisa memutus relasi ke riwayat absennya |

**Opsional**

- Log aktivitas admin (siapa membuat atau menutup rapat, mereset password).
- Ganti password admin sendiri.
- Grafik kehadiran sederhana.

## Yang perlu diperbaiki dari screenshot

1. **Label "Pagi (Senin)" salah untuk 07/10/2026.** Tanggal itu hari Rabu, tapi baris deni tetap berlabel Senin. Label sepertinya ditempel dari nilai `sesi`, bukan dari harinya. Kalau validasi hari dan jam apel sudah ada di server, baris itu seharusnya tidak pernah masuk. Ini sekaligus bukti bahwa "validasi server" di judulmu belum terpasang.
2. **Data pegawai tidak konsisten.** Contohnya akun "akjfh31" berusername `dain`, NIP `q9u3uprq` berisi huruf, dan username "Fernando Hasiholan" memuat spasi. Tambahkan validasi saat tambah dan edit: NIP angka dan unik, username tanpa spasi dan unik. Rapikan juga data ujinya sebelum screenshot masuk laporan.
3. **PIN rapat `123456`.** Wajar untuk uji coba. Pastikan admin bisa mengisi PIN sendiri saat membuat rapat, sesuai spesifikasimu, dan batasi percobaan PIN salah di server.
4. **Kartu "Integrasi REST API".** Berguna untuk dokumentasi dan demo, tapi menampilkan alamat dan daftar endpoint di halaman utama kurang aman untuk penggunaan nyata. Pindahkan ke tab Pengaturan atau sembunyikan setelah uji coba selesai.
5. **Isi file Excel belum kulihat.** Dari screenshot hanya tombolnya yang terlihat. Cek sendiri hasil unduhan: apakah tanda tangan muncul sebagai gambar (bukan teks `data:image/png;base64...`), ada judul dan periode di atas tabel, dan ada catatan bahwa data berasal dari konfirmasi pegawai, bukan pengganti presensi resmi.

## Urutan pengerjaan yang kusarankan

1. Tab Absen Harian beserta unduh Excel.
2. Cek proteksi login admin pada halaman dan unduhan.
3. Validasi data pegawai dan nonaktifkan akun.
4. Rekap per pegawai dan daftar tidak hadir.
5. Perbaikan tampilan dan filter lainnya.

Satu hal yang menentukan isi tab harian: **foto dokumentasi absen kerja itu ikut dikirim ke server, atau hanya tersimpan di HP?** Kalau dikirim, kolom foto perlu ada di tabel dan Excel. Kalau tidak, tab harian cukup berisi jam masuk dan pulang.