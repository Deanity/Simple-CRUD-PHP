<?php
session_start();
include '../koneksi.php';

$username = $_POST['username'];
$password = $_POST['password'];

$password_md5 = md5($password);

$query = "SELECT * FROM users WHERE username='$username' AND password='$password_md5' AND role='admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);

    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];
    $_SESSION['status'] = "login";

    header("Location: ../dashboard.php");
    exit;
} else {
    header("Location: ../index.php?error=gagal");
    exit;
}
?>