<?php
session_start();
include("../db.php");

$msg = "";

function getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $ip = getIP();
    $time = date("Y-m-d H:i:s");

    $q = mysqli_query($conn,
        "SELECT * FROM users WHERE username='$u' AND password='$p'"
    );

    if (mysqli_num_rows($q) == 1) {

        $_SESSION['modeA_user'] = $u;

        // ✅ GHI LOG ĐẦY ĐỦ
        mysqli_query($conn,"
            INSERT INTO login_logs
            (username, ip_address, login_time, status, mode)
            VALUES
            ('$u', '$ip', '$time', 'SUCCESS', 'A')
        ");

        header("Location: history.php");
        exit;

    } else {

        $msg = "❌ Invalid username or password";

        mysqli_query($conn,"
            INSERT INTO login_logs
            (username, ip_address, login_time, status, mode)
            VALUES
            ('$u', '$ip', '$time', 'FAIL', 'A')
        ");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mode A Login</title>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="../assets/css/modeA.css">
</head>

<body>

<div class="login-box">

    <h2>
        <i class="fa-solid fa-user"></i>
        Mode A Login
    </h2>

    <p class="desc">
        Basic login system without monitoring or alerts
    </p>

    <form method="post">
        <div class="input-group">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" placeholder="Username" required>
        </div>

        <div class="input-group">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <button name="login">
            <i class="fa-solid fa-right-to-bracket"></i>
            Login
        </button>

        <a href="../system.php" class="glow-back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to System
        </a>
    </form>

    <div class="msg"><?= $msg ?></div>

</div>

</body>
</html>
