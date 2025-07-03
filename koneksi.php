<?php
$servername = "localhost";
$database   = "sistem_pengingat";
$username   = "root";
$password   = "";

// Buat koneksi
$conn = mysqli_connect($servername, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

// Tidak perlu echo apa-apa di sini agar tidak ganggu tampilan HTML
?>
