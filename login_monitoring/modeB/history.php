<?php
session_start();
include("../db.php");

$result = mysqli_query($conn,"
    SELECT
        username,
        ip_address,
        device_type,
        os,
        browser,
        country,
        login_time,
        status,
        alert_level
    FROM login_logs
    WHERE mode='B'
    ORDER BY login_time DESC
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Mode B – Login Monitoring History</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/modeB_history.css">
</head>

<body>

<div class="history-box">

    <h2 class="title">🛡️ Mode B – Login Monitoring History</h2>
    <p class="subtitle">AI-based Login Behavior Classification</p>

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
                <th>IP</th>
                <th>Device</th>
                <th>Location</th>
                <th>Time</th>
                <th>Status</th>
                <th>Alert Level</th>
            </tr>
        </thead>

        <tbody>
        <?php
        $i = 1;
        while ($row = mysqli_fetch_assoc($result)) {

            /* ===== AI STATUS LOGIC ===== */
            $displayStatus = "";
            $badgeClass = "";
            $icon = "";

            if ($row['status'] === "FAIL") {
                $displayStatus = "FAILED";
                $badgeClass = "badge badge-fail";
                $icon = "fa-circle-xmark";
            } else {
                switch ($row['alert_level']) {

                    case "GREEN":
                        $displayStatus = "SUCCESS";
                        $badgeClass = "badge badge-success-green";
                        $icon = "fa-circle-check";
                        break;

                    case "YELLOW":
                        $displayStatus = "WARNING";
                        $badgeClass = "badge badge-success-yellow";
                        $icon = "fa-triangle-exclamation";
                        break;

                    case "RED":
                        $displayStatus = "SUSPICIOUS";
                        $badgeClass = "badge badge-success-red";
                        $icon = "fa-shield-halved";
                        break;
                }
            }
        ?>
            <tr class="row-hover">

                <td><?= $i++ ?></td>

                <td>
                    <i class="fa-solid fa-user"></i>
                    <?= htmlspecialchars($row['username']) ?>
                </td>

                <td><?= $row['ip_address'] ?></td>

                <td class="device">
                    <i class="fa-solid fa-desktop"></i>
                    <?= $row['device_type'] ?><br>
                    <small><?= $row['os'] ?> / <?= $row['browser'] ?></small>
                </td>

                <td>
                    <i class="fa-solid fa-location-dot"></i>
                    <?= $row['country'] ?>
                </td>

                <td>
                    <i class="fa-regular fa-clock"></i>
                    <?= date("H:i:s - d/m/Y", strtotime($row['login_time'])) ?>
                </td>

                <!-- AI STATUS -->
                <td>
                    <span class="<?= $badgeClass ?>">
                        <i class="fa-solid <?= $icon ?>"></i>
                        <?= $displayStatus ?>
                    </span>
                </td>

                <!-- ALERT RAW -->
                <td><?= $row['alert_level'] ?></td>

            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>
