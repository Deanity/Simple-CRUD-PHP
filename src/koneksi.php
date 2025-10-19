<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "sekolah";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}
// Hapus echo supaya tidak tampil di halaman lain
// echo "Koneksi berhasil!";
?>
