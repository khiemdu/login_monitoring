<?php
include "db.php";

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM login_logs");
$row = mysqli_fetch_assoc($result);

echo "Total records: " . $row['total'] . "\n";

$result2 = mysqli_query($conn, "
    SELECT 
        COUNT(*) as with_device,
        COUNT(DISTINCT username) as users,
        COUNT(DISTINCT country) as countries
    FROM login_logs 
    WHERE device_type IS NOT NULL 
        AND country IS NOT NULL 
        AND login_time IS NOT NULL
");
$row2 = mysqli_fetch_assoc($result2);

echo "Records with full data: " . $row2['with_device'] . "\n";
echo "Unique users: " . $row2['users'] . "\n";
echo "Unique countries: " . $row2['countries'] . "\n";

mysqli_close($conn);
?>
