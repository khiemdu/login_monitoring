<?php
include("../db.php");

// Query with proper columns
$result = mysqli_query($conn,
    "SELECT id, username, ip_address, device_type, login_time, status, country, ai_cluster, risk_percentage 
     FROM login_logs 
     ORDER BY id DESC"
);

// Count statistics
$stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN status = 'FAIL' THEN 1 ELSE 0 END) as failed
    FROM login_logs
");
$stat_data = mysqli_fetch_assoc($stats);

// Get accounts with more than 3 failed attempts in 24 hours
$suspicious_accounts = mysqli_query($conn, "
    SELECT 
        username, 
        COUNT(*) as failed_count,
        MAX(login_time) as last_attempt,
        GROUP_CONCAT(DISTINCT country SEPARATOR ', ') as countries
    FROM login_logs 
    WHERE status = 'FAIL' 
    AND login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY username 
    HAVING COUNT(*) > 3
    ORDER BY failed_count DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login History - Logs</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/ai_global.css">

    <style>
        .logs-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .logs-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .logs-stat-item {
            background: rgba(0, 50, 100, 0.3);
            border: 2px solid #00ffff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .logs-stat-item strong {
            display: block;
            font-size: 1.8em;
            color: #00ff99;
            margin: 10px 0;
        }

        .logs-stat-item span {
            color: #00ccff;
            font-size: 0.9em;
        }

        .ai-table td.status-success {
            color: #00ff99;
        }

        .ai-table td.status-fail {
            color: #ff5555;
        }

        .ai-table td.risk-low {
            color: #00ff99;
        }

        .ai-table td.risk-medium {
            color: #ffff00;
        }

        .ai-table td.risk-high {
            color: #ff5555;
        }
    </style>

</head>

<body>

<div class="ai-container">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
        <i class="fa-solid fa-history"></i> Login History
    </div>

    <!-- HEADER -->
    <div class="ai-header logs-header">
        <h1><i class="fa-solid fa-history"></i> Login History</h1>
        <p>All login attempts with AI-based risk analysis</p>
    </div>

    <!-- STATISTICS -->
    <div class="logs-stats">
        <div class="logs-stat-item">
            <span>Total Logins</span>
            <strong><?= $stat_data['total'] ?></strong>
        </div>
        <div class="logs-stat-item">
            <span>Success</span>
            <strong class="stat-strong stat-value-normal"><?= $stat_data['success'] ?? 0 ?></strong>
        </div>
        <div class="logs-stat-item">
            <span>Failed</span>
            <strong class="stat-strong risk-cell"><?= $stat_data['failed'] ?? 0 ?></strong>
        </div>
    </div>

    <!-- SUSPICIOUS ACCOUNTS ALERT -->
    <?php
    $suspicious_count = mysqli_num_rows($suspicious_accounts);
    if ($suspicious_count > 0) {
    ?>
    <div style="background: rgba(255, 0, 0, 0.15); border: 2px solid #ff5555; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
        <h3 style="color: #ff5555; margin-bottom: 15px;">
            <i class="fa-solid fa-exclamation-triangle"></i> ⚠️ <?= $suspicious_count ?> Suspicious Account(s) Detected
        </h3>
        <p style="color: #ffaaaa; margin-bottom: 15px; font-size: 0.9em;">
            Accounts with more than 3 failed login attempts in the last 24 hours:
        </p>
        <table class="ai-table" style="border-color: #ff5555;">
            <tr style="background: rgba(255, 0, 0, 0.1);">
                <th style="color: #ff5555;">Username</th>
                <th style="color: #ff5555;">Failed Attempts (24h)</th>
                <th style="color: #ff5555;">Last Attempt</th>
                <th style="color: #ff5555;">Attempted From</th>
            </tr>
            <?php
            while ($account = mysqli_fetch_assoc($suspicious_accounts)) {
                echo "<tr>";
                echo "<td style='color: #ff5555; font-weight: bold;'>" . htmlspecialchars($account['username']) . "</td>";
                echo "<td style='color: #ff5555; text-align: center;'><strong>" . $account['failed_count'] . "</strong></td>";
                echo "<td style='color: #ff9999;'>" . htmlspecialchars($account['last_attempt']) . "</td>";
                echo "<td style='color: #ff9999;'>" . htmlspecialchars($account['countries']) . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
    <?php
    }
    ?>

    <!-- TABLE -->
    <table class="ai-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>IP Address</th>
                <th>Device</th>
                <th>Country</th>
                <th>Login Time</th>
                <th>Status</th>
                <th>AI Cluster</th>
                <th>Risk %</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                // Determine login hour from login_time
                $login_time = new DateTime($row['login_time']);
                $login_hour = (int)$login_time->format('H');
                
                // Status class
                $status_class = $row['status'] === 'SUCCESS' ? 'status-success' : 'status-fail';
                
                // Risk level class
                $risk_class = 'risk-low';
                if (isset($row['risk_percentage'])) {
                    if ($row['risk_percentage'] >= 65) {
                        $risk_class = 'risk-high';
                    } elseif ($row['risk_percentage'] >= 35) {
                        $risk_class = 'risk-medium';
                    }
                }
                
                // AI Cluster label
                $cluster_label = 'Unanalyzed';
                $cluster_color = '#888';
                if ($row['ai_cluster'] == 0) {
                    $cluster_label = 'Normal';
                    $cluster_color = '#00ff99';
                } elseif ($row['ai_cluster'] == 1) {
                    $cluster_label = 'Suspicious';
                    $cluster_color = '#ffff00';
                } elseif ($row['ai_cluster'] == 2) {
                    $cluster_label = 'High Risk';
                    $cluster_color = '#ff5555';
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['ip_address'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['device_type'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['country'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($row['login_time'] ?? 'N/A') ?> (<?= $login_hour ?>:00)</td>
                    <td class="<?= $status_class ?>">
                        <i class="fa-solid <?= $row['status'] === 'SUCCESS' ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        <?= htmlspecialchars($row['status']) ?>
                    </td>
                    <td style="color: <?= $cluster_color ?>;">
                        <strong><?= $cluster_label ?></strong>
                    </td>
                    <td class="<?= $risk_class ?>">
                        <?= isset($row['risk_percentage']) ? number_format($row['risk_percentage'], 1) . '%' : 'N/A' ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="log-footer">
        <a href="../system.php" class="log-footer a">
            <i class="fa-solid fa-arrow-left"></i> Back to System
        </a>
        <a href="../ai/ai_index.php" class="log-footer a">
            <i class="fa-solid fa-brain"></i> AI Module
        </a>
    </div>

</div>

</body>
</html>
