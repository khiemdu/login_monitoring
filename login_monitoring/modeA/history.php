<?php
session_start();
include("../db.php");

/* ===== CHỈ LẤY LOG MODE A ===== */
$result = mysqli_query(
    $conn,
    "SELECT id, username, ip_address, login_time, status
     FROM login_logs
     WHERE mode = 'A'
     ORDER BY login_time DESC"
);

$stt = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mode A - Login History</title>

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS RIÊNG -->
    <link rel="stylesheet" href="../assets/css/modeA_history.css">
</head>
<body>

<div class="history-wrapper">

    <h2 class="title">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Mode A – Login History
    </h2>

    <!-- NÚT THOÁT -->
    <div class="center-btn">
        <a href="../system.php" class="glow-back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to System
        </a>
    </div>

    <table class="history-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>User</th>
                <th>IP Address</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr class="row-hover">

                <!-- STT -->
                <td class="stt"><?= $stt++ ?></td>

                <!-- USER -->
                <td class="user">
                    <i class="fa-solid fa-user"></i>
                    <?= htmlspecialchars($row['username']) ?>
                </td>

                <!-- IP -->
                <td class="ip">
                    <i class="fa-solid fa-network-wired"></i>
                    <?= $row['ip_address'] ?>
                </td>

                <!-- TIME -->
                <td class="time">
                    <i class="fa-regular fa-clock"></i>
                    <?= date("H:i:s - d/m/Y", strtotime($row['login_time'])) ?>
                </td>

                <!-- STATUS -->
                <td class="status <?= strtolower($row['status']) ?>">
                    <?php if ($row['status'] == "SUCCESS") { ?>
                        <i class="fa-solid fa-circle-check"></i> Success
                    <?php } else { ?>
                        <i class="fa-solid fa-circle-xmark"></i> Fail
                    <?php } ?>
                </td>

            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>
