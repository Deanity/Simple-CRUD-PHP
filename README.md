# Sistem Manajemen Sekolah — SMK TI Bali Global Denpasar

Sistem web sederhana untuk manajemen sekolah dengan fitur CRUD untuk siswa, guru, jurusan, mata pelajaran, ekstrakurikuler, dan sistem login admin.

---

## ✅ Fitur Utama  
- 🧑‍🎓 Data Siswa: NIS, Nama, Kelas, Jurusan  
- 👩‍🏫 Data Guru: NIP, Nama Guru, Mata Pelajaran, Jabatan  
- 📚 Data Jurusan: Kode Jurusan, Nama Jurusan, Keterangan  
- 📖 Data Mata Pelajaran: Id, Nama Mapel, Kelas, Guru Pengajar  
- 🎯 Data Ekstrakurikuler: id, Nama Ekstra, Jadwal, Guru Ekstra  
- 🔐 Sistem Login: Hanya admin yang bisa akses panel admin  
- 🖥️ Tampilan modern dengan HTML + Tailwind CSS  
- 📁 Struktur siap dijalankan di XAMPP (`htdocs`)

---

## 🛠️ Setup & Instalasi  
1. Instal XAMPP (Apache + MySQL) dan aktifkan Apache & MySQL.  
2. Buat database (misalnya: `sekolah_db`) melalui PhpMyAdmin.  
```sql
-- SQL Dump: Struktur Database Sekolah (tanpa data)
-- Dibersihkan untuk keperluan publik / share project
-- Author: Dendra De Tama
-- Date: 2025-10-20

CREATE DATABASE IF NOT EXISTS `sekolah`;
USE `sekolah`;

-- -------------------------
-- Table: ekstra
-- -------------------------
CREATE TABLE `ekstra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_ekstra` varchar(100) NOT NULL,
  `jadwal` varchar(50) DEFAULT NULL,
  `guru_ekstra` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_ekstra` (`guru_ekstra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Table: guru
-- -------------------------
CREATE TABLE `guru` (
  `NIP` varchar(20) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `mata_pelajaran` varchar(100) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`NIP`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Table: jurusan
-- -------------------------
CREATE TABLE `jurusan` (
  `kode_jurusan` varchar(10) NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`kode_jurusan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Table: mapel
-- -------------------------
CREATE TABLE `mapel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_mapel` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `guru_pengajar` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_pengajar` (`guru_pengajar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Table: siswa
-- -------------------------
CREATE TABLE `siswa` (
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `jurusan` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`nis`),
  KEY `jurusan` (`jurusan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Table: users (untuk sistem login)
-- -------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- Foreign Key Constraints
-- -------------------------
ALTER TABLE `ekstra`
  ADD CONSTRAINT `ekstra_ibfk_1` FOREIGN KEY (`guru_ekstra`) REFERENCES `guru` (`NIP`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `mapel`
  ADD CONSTRAINT `mapel_ibfk_1` FOREIGN KEY (`guru_pengajar`) REFERENCES `guru` (`NIP`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`jurusan`) REFERENCES `jurusan` (`kode_jurusan`) ON DELETE SET NULL ON UPDATE CASCADE;
```
3. Import script SQL untuk tabel‑tabel: siswa, guru, jurusan, mapel, ekstra, users.  
4. Ubah konfigurasi koneksi di `koneksi.php`.
```php
<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "sekolah";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}
  echo "Koneksi berhasil!";
?>
```
5. Akses melalui browser ke `http://localhost/FULLCRUD/src/index.php`.  
6. Setelah login, kelola data melalui panel admin.

---

## 🎨 Teknologi yang digunakan  
- HTML5 + Tailwind CSS untuk tampilan  
- PHP (mysqli) untuk backend  
- MySQL untuk database  
- Struktur sederhana tanpa framework besar  
- Otentikasi role (hanya `admin` yang akses penuh)

---

## ⚙️ Cara Penggunaan  
- Login sebagai admin → diarahkan ke dashboard utama  
- Pilih modul: Siswa, Guru, Jurusan, Mapel, Ekstra  
- Klik modul → lakukan aksi CRUD: Tambah, Ubah, Hapus  
- Notifikasi flash tampil setelah aksi  
- Klik tombol Logout untuk keluar

---

## 📌 Catatan Penting  
- Pastikan nama kolom dan tabel di database sesuai kode.  
- Hindari kolom dengan spasi (gunakan underscore).  
- Gunakan prepared statements untuk keamanan tambahan.  
- Bisa dikembangkan dengan pagination, filter, upload foto, hak akses guru, dll.

---

## 🧾 Lisensi  
Proyek ini dibuat untuk keperluan tugas sekolah. Boleh dimodifikasi dan digunakan sesuai kebutuhan.
