<?php
/**
 * API to get training metrics from accuracy curve data
 */
header('Content-Type: application/json');
include "../db.php";

try {
    // Try to read accuracy curve data from Python output
    $curve_file = __DIR__ . '/accuracy_curve.json';
    $metrics_file = __DIR__ . '/training_metrics.json';
    
    // Get cluster distribution from database
    $result = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ai_cluster = 0 THEN 1 ELSE 0 END) as cluster_0,
            SUM(CASE WHEN ai_cluster = 1 THEN 1 ELSE 0 END) as cluster_1,
            SUM(CASE WHEN ai_cluster = 2 THEN 1 ELSE 0 END) as cluster_2,
            MIN(risk_percentage) as min_risk,
            MAX(risk_percentage) as max_risk,
            AVG(risk_percentage) as avg_risk
        FROM login_logs
        WHERE ai_cluster IS NOT NULL
    ");
    
    $row = mysqli_fetch_assoc($result);
    $total = $row['total'] ?? 0;
    
    // Calculate percentages
    $cluster_0_pct = $total > 0 ? round(($row['cluster_0'] / $total) * 100, 1) : 0;
    $cluster_1_pct = $total > 0 ? round(($row['cluster_1'] / $total) * 100, 1) : 0;
    $cluster_2_pct = $total > 0 ? round(($row['cluster_2'] / $total) * 100, 1) : 0;
    
    // Default metrics
    $accuracy = 50;
    $silhouette = 0;
    $davies_bouldin = 1.5;
    $calinski_harabasz = 100;
    
    // Try to read metrics from Python training output
    if (file_exists($metrics_file)) {
        $metrics_data = json_decode(file_get_contents($metrics_file), true);
        if ($metrics_data) {
            $accuracy = $metrics_data['accuracy'] ?? 50;
            $silhouette = $metrics_data['silhouette'] ?? 0;
            $davies_bouldin = $metrics_data['davies_bouldin'] ?? 1.5;
            $calinski_harabasz = $metrics_data['calinski_harabasz'] ?? 100;
        }
    }
    
    // If no metrics file, try to read from accuracy curve
    if ($accuracy == 50 && file_exists($curve_file)) {
        $curve_data = json_decode(file_get_contents($curve_file), true);
        if ($curve_data && !empty($curve_data['train_accuracy'])) {
            // Get final accuracy from curve
            $final_train_acc = end($curve_data['train_accuracy']);
            $final_val_acc = end($curve_data['val_accuracy']);
            $accuracy = round((($final_train_acc + $final_val_acc) / 2) * 100, 2);
        }
    }
    
    echo json_encode([
        "success" => true,
        "metrics" => [
            "accuracy" => round($accuracy, 2),
            "silhouette_score" => $silhouette,
            "davies_bouldin_index" => $davies_bouldin,
            "calinski_harabasz_index" => $calinski_harabasz
        ],
        "distribution" => [
            "cluster_0" => $row['cluster_0'] ?? 0,
            "cluster_0_pct" => $cluster_0_pct,
            "cluster_1" => $row['cluster_1'] ?? 0,
            "cluster_1_pct" => $cluster_1_pct,
            "cluster_2" => $row['cluster_2'] ?? 0,
            "cluster_2_pct" => $cluster_2_pct,
            "total" => $total
        ],
        "risk_stats" => [
            "min" => round($row['min_risk'] ?? 0, 2),
            "max" => round($row['max_risk'] ?? 0, 2),
            "avg" => round($row['avg_risk'] ?? 0, 2)
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
