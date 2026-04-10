<?php
/**
 * Sync ai_cluster to match risk_percentage
 * Ensures ai_cluster classification is always consistent with risk_percentage values
 */

header('Content-Type: application/json; charset=utf-8');

try {
    include "../db.php";
    
    if (!$conn) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }

    // Get all records with risk_percentage
    $q = mysqli_query($conn, "
        SELECT id, risk_percentage 
        FROM login_logs 
        WHERE risk_percentage IS NOT NULL
    ");
    
    $updated = 0;
    $counts = [0=>0, 1=>0, 2=>0];
    
    while ($row = mysqli_fetch_assoc($q)) {
        $risk_pct = floatval($row['risk_percentage']);
        
        // Classify based on risk_percentage
        if ($risk_pct <= 35) {
            $cluster = 0;
        } elseif ($risk_pct <= 65) {
            $cluster = 1;
        } else {
            $cluster = 2;
        }
        
        // Update ai_cluster to match
        $update = mysqli_query($conn, "
            UPDATE login_logs 
            SET ai_cluster = " . (int)$cluster . " 
            WHERE id = " . (int)$row['id']
        );
        
        if ($update) {
            $updated++;
            $counts[$cluster]++;
        }
    }
    
    mysqli_close($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cluster sync completed',
        'records_updated' => $updated,
        'distribution' => [
            'normal' => $counts[0],
            'suspicious' => $counts[1],
            'high_risk' => $counts[2]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
