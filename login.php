<?php
require 'koneksi.php';
session_start();

$email = $_POST['email'];
$password = $_POST['password'];

$query_sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
$result = mysqli_query($conn, $query_sql);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];

    header("Location: dashboard.php");
    exit;
} else {
    echo "<center><h1>Email atau Password Anda Salah. Silahkan Coba Login Kembali.</h1>
        <button><strong><a href='index.html'>Login</a></strong></button></center>";
}
