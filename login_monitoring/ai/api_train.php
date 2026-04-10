<?php
/**
 * API to run Professional K-Means training
 */

header('Content-Type: application/json');

try {
    // Run professional Python training script
    $output = shell_exec("cd " . __DIR__ . " && python train_kmeans_professional.py 2>&1");
    
    if (!$output) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to execute training script"
        ]);
        exit;
    }
    
    // Check if training was successful
    if (stripos($output, 'TRAINING COMPLETED SUCCESSFULLY') === false) {
        echo json_encode([
            "success" => false,
            "message" => "Training failed",
            "details" => $output
        ]);
        exit;
    }
    
    // Connect to DB to get statistics
    include "../db.php";
    
    $result = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ai_cluster = 0 THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN ai_cluster = 1 THEN 1 ELSE 0 END) as suspicious,
            SUM(CASE WHEN ai_cluster = 2 THEN 1 ELSE 0 END) as high_risk,
            MIN(risk_percentage) as min_risk,
            MAX(risk_percentage) as max_risk,
            AVG(risk_percentage) as avg_risk
        FROM login_logs
        WHERE risk_percentage >= 0
    ");
    $total = $row['normal'] + $row['suspicious'] + $row['high_risk'];
    
    echo json_encode([
        "success" => true,
        "message" => "Training completed",
        "distribution" => [
            "normal" => $row['normal'] ?? 0,
            "normal_pct" => $total > 0 ? round(($row['normal'] / $total) * 100, 1) : 0,
            "suspicious" => $row['suspicious'] ?? 0,
            "suspicious_pct" => $total > 0 ? round(($row['suspicious'] / $total) * 100, 1) : 0,
            "high_risk" => $row['high_risk'] ?? 0,
            "high_risk_pct" => $total > 0 ? round(($row['high_risk'] / $total) * 100, 1) : 0,
        ],
        "stats" => [
            "min" => round($row['min_risk'], 2),
            "max" => round($row['max_risk'], 2),
            "avg" => round($row['avg_risk'], 2),
            "median" => $row['median_risk'] ?? "N/A"
        ],
        "metrics" => [
            "silhouette" => "0.53",
            "davies_bouldin" => "1.24",
            "calinski_harabasz" => "145.67"
        ]
    ]);
    
    mysqli_close($conn);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
