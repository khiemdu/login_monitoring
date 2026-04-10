<?php
/**
 * Reset AI data and re-run training
 */

$conn = mysqli_connect("localhost", "root", "", "login_monitoring");

if (!$conn) {
    die("Connection failed");
}

// Check if risk_percentage column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'risk_percentage'");

if (mysqli_num_rows($result) == 0) {
    // Add column if it doesn't exist
    mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN risk_percentage DECIMAL(5, 2) DEFAULT 0.00");
    echo "✅ Added risk_percentage column\n";
}

// Reset AI data
mysqli_query($conn, "UPDATE login_logs SET ai_cluster=NULL, risk_percentage=0.00");
echo "✅ Reset old AI data\n";

// Run Python training
echo "\n🤖 Running K-Means training...\n";
$output = shell_exec("python C:/xampp/htdocs/login_monitoring/ai/kmeans_login.py 2>&1");
echo $output;

mysqli_close($conn);
?>
