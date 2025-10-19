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

## 🗂️ Struktur Folder  
FULLCRUD/
 ├── src/
 │   ├── config/
 │   │   ├── loginSistem.php
 │   │   ├── logoutSistem.php
 │   ├── siswaCrud/
 │   │   ├── dashboard.php
 │   ├── guruCrud/
 │   │   ├── dashboard.php
 │   ├── jurusanCrud/
 │   │   ├── dashboard.php
 │   ├── mapelCrud/
 │   │   ├── dashboard.php
 │   ├── ekstraCrud/
 │   │   ├── dashboard.php
 │   ├── koneksi.php
 │   ├── dashboard.php
 │   ├── index.php  (halaman login)
 │   ├── output.css  (hasil build Tailwind)
 └── …

---

## 🛠️ Setup & Instalasi  
1. Instal XAMPP (Apache + MySQL) dan aktifkan Apache & MySQL.  
2. Buat database (misalnya: `sekolah_db`) melalui PhpMyAdmin.  
3. Import script SQL untuk tabel‑tabel: siswa, guru, jurusan, mapel, ekstra, users.  
4. Ubah konfigurasi koneksi di `koneksi.php`.
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
