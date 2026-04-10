<?php
set_time_limit(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Login Behavior Analysis</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/ai_global.css">

<body>

<div class="ai-wrapper">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
        <a href="ai_index.php"><i class="fa-solid fa-brain"></i> AI Module</a> / 
        <i class="fa-solid fa-play"></i> Control Panel
    </div>

    <div class="ai-panel">

        <h1 class="glow-title">
            <i class="fa-solid fa-robot"></i> AI Control Panel
        </h1>

        <p class="subtitle">
            K-Means Clustering for Login Behavior Classification
        </p>

        <div class="ai-info">
            <div class="info-box">
                <i class="fa-solid fa-brain"></i>
                <span>Algorithm</span>
                <strong>K-Means</strong>
            </div>

            <div class="info-box">
                <i class="fa-solid fa-database"></i>
                <span>Data Source</span>
                <strong>login_logs</strong>
            </div>

            <div class="info-box">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Purpose</span>
                <strong>Anomaly Detection</strong>
            </div>
        </div>

        <div class="run-status">
            <i class="fa-solid fa-info-circle"></i>
            Use "Train Model" to run K-Means clustering and calculate risk percentages
        </div>

        <div class="btn-group">
            <a href="train_ai.php" class="btn glow">
                <i class="fa-solid fa-dumbbell"></i> TRAIN MODEL
            </a>

            <a href="ai_dashboard.php" class="btn glow">
                <i class="fa-solid fa-chart-pie"></i> VIEW DASHBOARD
            </a>

            <a href="test_risk_levels.php" class="btn glow">
                <i class="fa-solid fa-flask"></i> TEST DATA
            </a>

            <a href="ai_index.php" class="btn back">
                <i class="fa-solid fa-arrow-left"></i> BACK
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="ai-footer-wrapper">
        <a href="../system.php" class="footer-link">
            <i class="fa-solid fa-home"></i> System
        </a>
        <a href="ai_index.php" class="footer-link">
            <i class="fa-solid fa-brain"></i> AI Module
        </a>
        <a href="../logs/view_logs.php" class="footer-link">
            <i class="fa-solid fa-history"></i> Login History
        </a>
    </div>

</div>

</body>
</html>
