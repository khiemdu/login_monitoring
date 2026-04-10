<?php
/**
 * API to reset AI data
 */

header('Content-Type: application/json');

try {
    include "../db.php";
    
    // Reset AI data
    $result = mysqli_query($conn, "
        UPDATE login_logs 
        SET ai_cluster = NULL, risk_percentage = 0.00
    ");
    
    if ($result) {
        echo json_encode([
            "success" => true,
            "message" => "Data reset successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);
    }
    
    mysqli_close($conn);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
