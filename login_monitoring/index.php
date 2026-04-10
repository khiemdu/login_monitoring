<?php
include "db.php";
$message = "";
$success = false;

if (isset($_POST['register'])) {
    $u = trim($_POST['username']);
    $p = trim($_POST['password']);

    if ($u == "" || $p == "") {
        $message = "Please fill in all fields";
    } else {
        $check = mysqli_query($conn,
            "SELECT * FROM users WHERE username='$u'");

        if (mysqli_num_rows($check) > 0) {
            $message = "❌ Username already exists. Please register a new one.";
        } else {
            mysqli_query($conn,
                "INSERT INTO users(username,password)
                 VALUES('$u','$p')");
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register – Login Monitoring System</title>

<!-- ICON -->
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="register-wrapper">

    <div class="register-card">

        <div class="icon-box">
            <i class="fa-solid fa-user-shield"></i>
        </div>

        <h1>Login Monitoring System</h1>
        <p class="subtitle">
            Research project – Secure login behavior analysis
        </p>

        <?php if (!$success): ?>

        <form method="post" class="register-form">

            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text"
                       name="username"
                       placeholder="Create username"
                       required
                       autofocus>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password"
                       name="password"
                       placeholder="Create password"
                       required>
            </div>

            <button type="submit" name="register">
                <i class="fa-solid fa-user-plus"></i>
                Register Account
            </button>

        </form>

        <?php if ($message): ?>
            <div class="msg error"><?= $message ?></div>
        <?php endif; ?>

        <?php else: ?>

            <div class="msg success">
                ✅ Registration successful!
            </div>

            <a href="system.php" class="enter-btn">
                Enter System
            </a>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

