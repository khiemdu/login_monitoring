<?php
session_start();
include("../db.php");

if (!isset($_POST['login'])) {
    header("Location: login.php");
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

/* ===============================
   DEVICE + COUNTRY (BẮT BUỘC)
================================ */
$device = "Desktop";    // tạm thời
$country = "Unknown";   // tạm thời

$q = mysqli_query($conn,
    "SELECT * FROM users WHERE username='$username' AND password='$password'"
);

if (mysqli_num_rows($q) == 1) {

    $_SESSION['modeB_user'] = $username;
    $status = "SUCCESS";

    // ===== GHI LOG LOGIN (ĐẦY ĐỦ CHO AI) =====
    mysqli_query($conn, "
        INSERT INTO login_logs (username, status, device, country, login_time)
        VALUES ('$username', '$status', '$device', '$country', NOW())
    ");

    header("Location: history.php");
    exit;

} else {

    $status = "FAIL";

    mysqli_query($conn, "
        INSERT INTO login_logs (username, status, device, country, login_time)
        VALUES ('$username', '$status', '$device', '$country', NOW())
    ");

    $_SESSION['error'] = "❌ Invalid username or password";
    header("Location: login.php");
    exit;
}
