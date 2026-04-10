<?php
/**
 * Initialize database schema for AI model
 * Add risk_percentage column if it doesn't exist
 */

$conn = mysqli_connect("localhost", "root", "", "login_monitoring");

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]));
}

// Check if risk_percentage column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'risk_percentage'");

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_query = "ALTER TABLE login_logs ADD COLUMN risk_percentage DECIMAL(5, 2) DEFAULT 0.00";
    
    if (mysqli_query($conn, $alter_query)) {
        echo json_encode([
            "status" => "success",
            "message" => "✅ Column 'risk_percentage' added successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "❌ Failed to add column: " . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode([
        "status" => "info",
        "message" => "✅ Column 'risk_percentage' already exists"
    ]);
}

mysqli_close($conn);
?>
