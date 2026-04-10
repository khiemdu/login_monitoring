<?php
session_start();
date_default_timezone_set("Asia/Ho_Chi_Minh");
include("../db.php");

$msg = "";

/* =========================
   LẤY IP
========================= */
function getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

/* =========================
   NHẬN DIỆN DEVICE / OS / BROWSER
========================= */
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$device_type = "Desktop";
if (preg_match("/mobile/i", $userAgent)) $device_type = "Mobile";
elseif (preg_match("/tablet|ipad/i", $userAgent)) $device_type = "Tablet";

$os = "Unknown";
if (preg_match("/windows/i", $userAgent)) $os = "Windows";
elseif (preg_match("/android/i", $userAgent)) $os = "Android";
elseif (preg_match("/iphone|ios/i", $userAgent)) $os = "iOS";

$browser = "Unknown";
if (preg_match("/edge/i", $userAgent)) $browser = "Edge";
elseif (preg_match("/chrome/i", $userAgent)) $browser = "Chrome";
elseif (preg_match("/firefox/i", $userAgent)) $browser = "Firefox";
elseif (preg_match("/safari/i", $userAgent)) $browser = "Safari";

/* =========================
   XỬ LÝ LOGIN
========================= */
if (isset($_POST['login'])) {

    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $p = mysqli_real_escape_string($conn, $_POST['password']);

    $ip = getIP();
    $country = "Unknown";

    /* Kiểm tra đúng tài khoản */
    $check = mysqli_query($conn,"
        SELECT * FROM users 
        WHERE username='$u' AND password='$p'
    ");

    /* =========================
       LOGIN SUCCESS
    ========================= */
    if (mysqli_num_rows($check) == 1) {

        $_SESSION['modeB_user'] = $u;

        $status = "SUCCESS";

        /* ===== MẶC ĐỊNH ===== */
        $alert_level  = "GREEN";
        $alert_reason = "Normal login behavior";

        /* ===== KIỂM TRA FAIL TRƯỚC (QUAN TRỌNG) ===== */
        $failCheck = mysqli_query($conn,"
            SELECT COUNT(*) AS total_fail
            FROM login_logs
            WHERE username='$u'
              AND status='FAIL'
              AND mode='B'
              AND login_time >= NOW() - INTERVAL 15 MINUTE
        ");
        $failRow = mysqli_fetch_assoc($failCheck);

        if ($failRow['total_fail'] >= 3) {
            $alert_level  = "RED";
            $alert_reason = "Correct password after multiple failed attempts";
        }

        /* ===== LOGIN BAN ĐÊM (CHỈ ÁP DỤNG NẾU CHƯA RED) ===== */
        $hour = date("H");
        if ($alert_level == "GREEN" && $hour >= 0 && $hour <= 5) {
            $alert_level  = "YELLOW";
            $alert_reason = "Login at unusual time (" . date("H:i") . ")";
        }

        /* ===== GHI LOG ===== */
        mysqli_query($conn,"
            INSERT INTO login_logs (
                username, ip_address, device_type, os, browser,
                country, status, alert_level, alert_reason, mode
            ) VALUES (
                '$u','$ip','$device_type','$os','$browser',
                '$country','$status','$alert_level','$alert_reason','B'
            )
        ");

        header("Location: history.php");
        exit;
    }

    /* =========================
       LOGIN FAIL
    ========================= */
    else {

        /* ĐẾM FAIL */
        $failCount = mysqli_query($conn,"
            SELECT COUNT(*) AS total_fail
            FROM login_logs
            WHERE username='$u'
              AND status='FAIL'
              AND mode='B'
              AND login_time >= NOW() - INTERVAL 15 MINUTE
        ");
        $row = mysqli_fetch_assoc($failCount);
        $totalFail = $row['total_fail'] + 1;

        if ($totalFail >= 3) {
            $msg = "⚠️ You have entered the wrong password 3 times!";
        } else {
            $msg = "❌ Invalid username or password";
        }

        mysqli_query($conn,"
            INSERT INTO login_logs (
                username, ip_address, device_type, os, browser,
                country, status, alert_level, alert_reason, mode
            ) VALUES (
                '$u','$ip','$device_type','$os','$browser',
                '$country','FAIL','RED','Invalid credentials','B'
            )
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Mode B Login</title>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/modeB.css">
</head>

<body>

<div class="login-box">

    <h2>
        <i class="fa-solid fa-shield-halved"></i>
        Mode B Login
    </h2>

    <p class="desc">
        Advanced Login Monitoring (Device • Location • Behavior)
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

        <button type="submit" name="login">
            <i class="fa-solid fa-right-to-bracket"></i>
            Login
        </button>

        <a href="../system.php" class="glow-back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to System
        </a>

    </form>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

</div>

</body>
</html>
