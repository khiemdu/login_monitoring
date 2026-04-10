<?php
/**
 * Test Risk Levels - Insert various login scenarios
 * để kiểm tra từng risk level
 */

$conn = mysqli_connect("localhost", "root", "", "login_monitoring");

if (!$conn) {
    die("❌ Database connection failed");
}

// Xóa dữ liệu test cũ
mysqli_query($conn, "DELETE FROM login_logs WHERE username LIKE 'test_%'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Risk Levels</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/ai_global.css">
</head>
<body>

<div class="ai-wrapper">

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
    <a href="ai_index.php"><i class="fa-solid fa-brain"></i> AI Module</a> / 
    <span><i class="fa-solid fa-flask"></i> Test Data</span>
</div>

<?php

$test_cases = [
    // ===== NORMAL LOGINS (0-30% risk) =====
    [
        "username" => "test_normal_user1",
        "device_type" => "Desktop",
        "country" => "Vietnam",
        "login_time" => "2026-02-01 09:00:00",
        "status" => "SUCCESS",
        "label" => "✅ NORMAL - Desktop, Vietnam, 9:00 AM"
    ],
    [
        "username" => "test_normal_user1",
        "device_type" => "Desktop",
        "country" => "Vietnam",
        "login_time" => "2026-02-01 14:30:00",
        "status" => "SUCCESS",
        "label" => "✅ NORMAL - Desktop, Vietnam, 2:30 PM"
    ],
    [
        "username" => "test_normal_user2",
        "device_type" => "Laptop",
        "country" => "Vietnam",
        "login_time" => "2026-02-01 10:15:00",
        "status" => "SUCCESS",
        "label" => "✅ NORMAL - Laptop, Vietnam, 10:15 AM"
    ],
    [
        "username" => "test_normal_user3",
        "device_type" => "Desktop",
        "country" => "Thailand",
        "login_time" => "2026-02-01 11:00:00",
        "status" => "SUCCESS",
        "label" => "✅ NORMAL - Desktop, Thailand, 11:00 AM"
    ],
    
    // ===== SUSPICIOUS LOGINS (30-65% risk) =====
    [
        "username" => "test_suspicious_user1",
        "device_type" => "Mobile",
        "country" => "Unknown",
        "login_time" => "2026-02-01 01:30:00",
        "status" => "SUCCESS",
        "label" => "⚠️ SUSPICIOUS - Mobile, Unknown, 1:30 AM"
    ],
    [
        "username" => "test_suspicious_user2",
        "device_type" => "Tablet",
        "country" => "Unknown",
        "login_time" => "2026-02-01 03:00:00",
        "status" => "SUCCESS",
        "label" => "⚠️ SUSPICIOUS - Tablet, Unknown, 3:00 AM"
    ],
    [
        "username" => "test_suspicious_user1",
        "device_type" => "Mobile",
        "country" => "Vietnam",
        "login_time" => "2026-02-01 04:45:00",
        "status" => "SUCCESS",
        "label" => "⚠️ SUSPICIOUS - Mobile, Vietnam, 4:45 AM"
    ],
    
    // ===== HIGH RISK LOGINS (65-100% risk) =====
    [
        "username" => "test_attacker_user",
        "device_type" => "Desktop",
        "country" => "Unknown",
        "login_time" => "2026-02-01 02:00:00",
        "status" => "FAIL",
        "label" => "🔴 HIGH RISK - Failed login, Unknown, 2:00 AM"
    ],
    [
        "username" => "test_attacker_user",
        "device_type" => "Desktop",
        "country" => "Unknown",
        "login_time" => "2026-02-01 02:03:00",
        "status" => "FAIL",
        "label" => "🔴 HIGH RISK - 2nd failed attempt (brute-force)"
    ],
    [
        "username" => "test_attacker_user",
        "device_type" => "Desktop",
        "country" => "Unknown",
        "login_time" => "2026-02-01 02:06:00",
        "status" => "FAIL",
        "label" => "🔴 HIGH RISK - 3rd failed attempt (attack pattern)"
    ],
    [
        "username" => "test_attacker_user",
        "device_type" => "Desktop",
        "country" => "Unknown",
        "login_time" => "2026-02-01 02:09:00",
        "status" => "FAIL",
        "label" => "🔴 HIGH RISK - 4th failed attempt"
    ],
    [
        "username" => "test_highrisk_user2",
        "device_type" => "Mobile",
        "country" => "Unknown",
        "login_time" => "2026-02-01 02:30:00",
        "status" => "FAIL",
        "label" => "🔴 HIGH RISK - Failed + Unknown + 2:30 AM"
    ],
];


echo "<h1 class='ai-title'><i class='fa-solid fa-flask'></i> Generating Test Data for Risk Level Classification</h1>";
echo "<hr>";

echo "<div class='table-container'>";
echo "<table class='test-table'>";
echo "<tr>";
echo "<th>User</th>";
echo "<th>Device</th>";
echo "<th>Country</th>";
echo "<th>Login Time</th>";
echo "<th>Status</th>";
echo "<th>Expected Risk</th>";
echo "</tr>";

$inserted = 0;
foreach ($test_cases as $case) {
    $username = mysqli_real_escape_string($conn, $case['username']);
    $device_type = mysqli_real_escape_string($conn, $case['device_type']);
    $country = mysqli_real_escape_string($conn, $case['country']);
    $login_time = mysqli_real_escape_string($conn, $case['login_time']);
    $status = mysqli_real_escape_string($conn, $case['status']);
    
    $query = "INSERT INTO login_logs (username, device_type, country, login_time, status) 
              VALUES ('$username', '$device_type', '$country', '$login_time', '$status')";
    
    if (mysqli_query($conn, $query)) {
        $inserted++;
        echo "<tr>";
        echo "<td>{$case['username']}</td>";
        echo "<td>{$case['device_type']}</td>";
        echo "<td>{$case['country']}</td>";
        echo "<td>{$case['login_time']}</td>";
        echo "<td><strong>{$case['status']}</strong></td>";
        echo "<td>{$case['label']}</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "</div>";

echo "<h2 class='ai-title'><i class='fa-solid fa-check-circle'></i> Inserted $inserted test records</h2>";

echo "<div class='test-info'>";
echo "<h3 class='ai-subtitle'><i class='fa-solid fa-rocket'></i> Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Run Training:</strong> Go to terminal and run:<br>";
echo "<code class='code-block'>cd c:\\xampp\\htdocs\\login_monitoring\\ai<br>python train_kmeans.py</code>";
echo "</li>";
echo "<li><strong>View Results:</strong> Open AI Dashboard<br>";
echo "<a href='ai_dashboard.php' class='footer-link'><i class='fa-solid fa-chart-pie'></i> AI Dashboard</a>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "<div class='ai-footer'>";
echo "<a href='ai_index.php'><i class='fa-solid fa-arrow-left'></i> Back to AI Module</a> / ";
echo "<a href='../system.php'><i class='fa-solid fa-home'></i> System</a>";
echo "</div>";

echo "</div>";

mysqli_close($conn);
?>

</body>
</html>
