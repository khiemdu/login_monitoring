<?php
/**
 * API to get accuracy curve data
 */
header('Content-Type: application/json');

try {
    $curve_file = __DIR__ . '/accuracy_curve.json';
    
    if (file_exists($curve_file)) {
        $data = json_decode(file_get_contents($curve_file), true);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No accuracy curve data available',
            'data' => null
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
