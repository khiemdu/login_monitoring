<?php
header('Content-Type: application/json');
include "../db.php";

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'No data provided'
    ]);
    exit;
}

// Validate and sanitize input
$username = isset($data['username']) ? trim($data['username']) : 'test_user';
$device_type = isset($data['device_type']) ? $data['device_type'] : 'Desktop';
$country = isset($data['country']) ? $data['country'] : 'Unknown';
$login_time = isset($data['login_time']) ? $data['login_time'] : date('Y-m-d\TH:i');
$status = isset($data['status']) ? strtoupper($data['status']) : 'SUCCESS';

// Prepare JSON for Python script
$python_input = json_encode([
    'username' => $username,
    'device_type' => $device_type,
    'country' => $country,
    'login_time' => $login_time,
    'status' => $status
]);

// Try to call Python script for advanced risk analysis
$python_path = defined('PYTHON_PATH') ? PYTHON_PATH : 'python';
$script_path = __DIR__ . '/api_calculate_risk_advanced.py';

if (file_exists($script_path)) {
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    
    $process = proc_open($python_path . ' ' . escapeshellarg($script_path), 
                        $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Send JSON to Python script
        fwrite($pipes[0], $python_input);
        fclose($pipes[0]);
        
        // Read output from Python
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Close process
        proc_close($process);
        
        // Parse Python result
        $result = json_decode($output, true);
        
        if ($result && $result['success']) {
            echo json_encode($result);
            mysqli_close($conn);
            exit;
        }
    }
}

// ===== FALLBACK: PHP-BASED CALCULATION =====
// If Python is not available, use simplified PHP calculation

$esc_user = mysqli_real_escape_string($conn, $username);
$time_obj = new DateTime();

try {
    $time_obj = new DateTime($login_time);
} catch (Exception $e) {
    $time_obj = new DateTime();
}

$login_hour = (int)$time_obj->format('H');
$risk_percentage = 0;
$breakdown = array();

// 1. LOGIN STATUS
if ($status === 'FAIL') {
    $risk_percentage += 20;
    $breakdown['status_risk'] = 20;
    $breakdown['status_reason'] = 'Failed login attempt';
} else {
    $risk_percentage += 2;
    $breakdown['status_risk'] = 2;
    $breakdown['status_reason'] = 'Successful login';
}

// 2. DEVICE TYPE (0-12%)
$device_risk = 0;
$device_reason = 'Desktop (normal)';
if ($device_type === 'Mobile' || $device_type === 'Tablet') {
    $device_risk = 12;
    $device_reason = $device_type . ' (higher risk)';
} elseif ($device_type === 'Laptop') {
    $device_risk = 5;
    $device_reason = 'Laptop (moderate)';
}
$risk_percentage += $device_risk;
$breakdown['device_risk'] = $device_risk;
$breakdown['device_reason'] = $device_reason;

// 3. COUNTRY (0-10%)
$country_risk = 0;
$country_reason = $country . ' (known location)';
if ($country === 'Unknown') {
    $country_risk = 10;
    $country_reason = 'Unknown location (high risk)';
}
$risk_percentage += $country_risk;
$breakdown['country_risk'] = $country_risk;
$breakdown['country_reason'] = $country_reason;

// 4. LOGIN TIME (0-15%)
$time_risk = 0;
$time_reason = $login_hour . ':00 (normal hours)';
if ($login_hour >= 0 && $login_hour < 6) {
    $time_risk = 15;
    $time_reason = $login_hour . ':00 (midnight-6am - unusual)';
} elseif ($login_hour >= 22) {
    $time_risk = 8;
    $time_reason = $login_hour . ':00 (late night)';
} elseif ($login_hour >= 6 && $login_hour < 8) {
    $time_risk = 5;
    $time_reason = $login_hour . ':00 (early morning)';
}
$risk_percentage += $time_risk;
$breakdown['time_risk'] = $time_risk;
$breakdown['time_reason'] = $time_reason;
$breakdown['login_hour'] = $login_hour;

// 5. FAILED ATTEMPTS (0-25%)
$fails_24h_result = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as cnt FROM login_logs 
     WHERE username='$esc_user' AND status='FAIL' 
     AND login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")
);
$fail_count_24h = (int)($fails_24h_result['cnt'] ?? 0);
$fail_risk = min($fail_count_24h * 2, 25);
$risk_percentage += $fail_risk;
$breakdown['status_risk'] = $fail_risk;
$breakdown['failed_24h'] = $fail_count_24h;
$breakdown['status_reason'] = $fail_count_24h . ' failed attempts in 24h';

// 6. BRUTE-FORCE PATTERN (0-15%)
$recent_fails_result = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as cnt FROM login_logs 
     WHERE username='$esc_user' AND status='FAIL'
     AND login_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")
);
$recent_count = (int)($recent_fails_result['cnt'] ?? 0);
$brute_risk = 0;
$brute_reason = 'No brute-force pattern';

if ($recent_count >= 5) {
    $brute_risk = 15;
    $brute_reason = $recent_count . ' failed attempts in 15 min - ATTACK PATTERN';
} elseif ($recent_count >= 3) {
    $brute_risk = 10;
    $brute_reason = $recent_count . ' failed attempts in 15 min';
} elseif ($recent_count >= 1) {
    $brute_risk = 5;
    $brute_reason = $recent_count . ' failed attempt(s) recently';
}
$risk_percentage += $brute_risk;
$breakdown['brute_force_risk'] = $brute_risk;
$breakdown['brute_force_reason'] = $brute_reason;
$breakdown['recent_fails'] = $recent_count;

// Cap at 100%
$risk_percentage = min(max($risk_percentage, 0), 100);

// Determine risk level
if ($risk_percentage <= 25) {
    $risk_level = '🟢 NORMAL';
    $color = 'green';
} elseif ($risk_percentage <= 50) {
    $risk_level = '🟡 SUSPICIOUS';
    $color = 'yellow';
} elseif ($risk_percentage <= 75) {
    $risk_level = '🟠 WARNING';
    $color = 'orange';
} else {
    $risk_level = '🔴 HIGH RISK';
    $color = 'red';
}

echo json_encode([
    'success' => true,
    'risk_percentage' => round($risk_percentage, 1),
    'risk_level' => $risk_level,
    'risk_color' => $color,
    'explanation' => 'Risk analysis based on multiple security factors',
    'breakdown' => $breakdown,
    'details' => [
        'username' => $username,
        'device' => $device_type,
        'country' => $country,
        'login_time' => $login_time,
        'status' => $status
    ]
]);

mysqli_close($conn);
?>
