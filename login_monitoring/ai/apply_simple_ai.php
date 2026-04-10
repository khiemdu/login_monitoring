<?php
/**
 * Simple PHP fallback to compute risk % and assign ai_cluster
 * Can be included and called as a function, or accessed via ?run_fallback=1
 */

function apply_simple_ai_fallback() {
    $out_lines = [];
    $conn = mysqli_connect("localhost", "root", "", "login_monitoring");
    if (!$conn) {
        return "Database connection failed";
    }

    // Ensure columns exist
    $res = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'ai_cluster'");
    if (mysqli_num_rows($res) == 0) {
        mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN ai_cluster TINYINT NULL");
        $out_lines[] = "Added column ai_cluster";
    }
    $res2 = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'risk_percentage'");
    if (mysqli_num_rows($res2) == 0) {
        mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN risk_percentage DECIMAL(5,2) DEFAULT 0.00");
        $out_lines[] = "Added column risk_percentage";
    }

    $q = mysqli_query($conn, "SELECT id, username, status, device_type, country, login_time FROM login_logs");
    $updated = 0;
    $counts = [0=>0,1=>0,2=>0];

    while ($row = mysqli_fetch_assoc($q)) {
        $username = $row['username'];
        $status = strtoupper($row['status'] ?? 'SUCCESS');
        $device = $row['device_type'] ?? '';
        $country = $row['country'] ?? '';
        $login_time = $row['login_time'] ?? date('Y-m-d H:i:00');
        $id = (int)$row['id'];

        // parse hour
        $t = DateTime::createFromFormat('Y-m-d H:i:s', $login_time);
        if (!$t) $t = new DateTime($login_time);
        $hour = (int)$t->format('H');
        $esc_user = mysqli_real_escape_string($conn, $username);

        // ===== IMPROVED RISK CALCULATION =====
        $risk = 0;

        // 1. CURRENT LOGIN STATUS (0-35%)
        if ($status === 'FAIL') {
            $risk += 25;  // Failed login = +25%
        } else {
            $risk += 5;   // Success = +5%
        }

        // 2. FAILED ATTEMPTS (0-40%)
        // Count fails in last 24 hours
        $fails_24h = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(*) as cnt FROM login_logs 
             WHERE username='$esc_user' AND status='FAIL' 
             AND login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")
        );
        $fail_count_24h = (int)($fails_24h['cnt'] ?? 0);
        
        // Scale: each fail adds ~2%, max 40%
        $risk += min($fail_count_24h * 2.5, 40);

        // 3. DEVICE TYPE (0-15%)
        if ($device === 'Mobile' || $device === 'Tablet') {
            $risk += 12;
        } elseif ($device === 'Laptop') {
            $risk += 5;
        }

        // 4. COUNTRY (0-15%)
        if ($country === 'Unknown') {
            $risk += 15;
        }

        // 5. LOGIN TIME (0-10%)
        if ($hour >= 0 && $hour < 6) {
            $risk += 10;  // Midnight-6am
        }

        // 6. UNUSUAL PATTERNS (0-10%)
        // Check if multiple failures in short time
        $recent_fails = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM login_logs 
             WHERE username='$esc_user' AND status='FAIL'
             AND login_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")
        );
        $recent_count = (int)($recent_fails['cnt'] ?? 0);
        if ($recent_count >= 3) {
            $risk += 10;  // Brute-force pattern
        } elseif ($recent_count >= 1) {
            $risk += 5;
        }

        // Cap at 100%
        $risk = min(max($risk, 0), 100);

        // Classify based on risk
        if ($risk <= 35) $cluster = 0;
        elseif ($risk <= 65) $cluster = 1;
        else $cluster = 2;

        $update = mysqli_query($conn, "UPDATE login_logs SET ai_cluster=".(int)$cluster.
            ", risk_percentage=".floatval($risk)." WHERE id=".(int)$id);
        if ($update) {
            $updated++;
            $counts[$cluster]++;
        }
    }

    $out_lines[] = "Updated records: $updated";
    $out_lines[] = "Distribution - NORMAL: {$counts[0]}, SUSPICIOUS: {$counts[1]}, HIGH: {$counts[2]}";

    mysqli_close($conn);
    return implode("\n", $out_lines);
}

if (php_sapi_name() !== 'cli' && isset($_GET['run_fallback'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo apply_simple_ai_fallback();
    exit;
}

?>
