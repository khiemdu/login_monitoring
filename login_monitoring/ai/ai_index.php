<?php include "../db.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Login Analysis Module</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/ai_global.css">

</head>

<body>

<div class="ai-module">

    <!-- ===== HEADER ===== -->
    <div class="ai-header">
        <h1><i class="fa-solid fa-brain"></i> AI Login Analysis Module</h1>
        <p>Machine Learning-based Login Behavior Detection</p>
        <small>K-Means Clustering | Risk Percentage Classification | Real-time Detection</small>
    </div>

    <!-- ===== BREADCRUMB ===== -->
    <div class="breadcrumb">
        <a href="../system.php"><i class="fa-solid fa-home"></i> System</a>
        <span>/</span>
        <i class="fa-solid fa-brain"></i> AI Module
    </div>

    <!-- ===== STATS ===== -->
    <?php
    // Get statistics
    $stats = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN ai_cluster = 0 THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN ai_cluster = 1 THEN 1 ELSE 0 END) as suspicious,
            SUM(CASE WHEN ai_cluster = 2 THEN 1 ELSE 0 END) as high_risk
        FROM login_logs
    ");
    $stat_data = mysqli_fetch_assoc($stats);
    ?>

    <div class="ai-stats">
        <h3><i class="fa-solid fa-chart-line"></i> Current Status</h3>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Records</div>
                <div class="stat-value"><?= $stat_data['total_records'] ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Normal Logins</div>
                <div class="stat-value stat-value-normal"><?= $stat_data['normal'] ?? 0 ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Suspicious</div>
                <div class="stat-value stat-value-suspicious"><?= $stat_data['suspicious'] ?? 0 ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">High Risk</div>
                <div class="stat-value stat-value-high-risk"><?= $stat_data['high_risk'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- ===== MAIN CARDS ===== -->
    <div class="ai-cards">

        <!-- TRAIN MODEL -->
        <a href="train_ai.php" class="ai-card">
            <div class="ai-card-icon">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <h3>Train Model</h3>
            <p>
                Train the K-Means model with your current login data.
                Calculate risk percentages and classify danger levels.
            </p>
            <div class="ai-card-btn">
                🚀 Train Now
            </div>
        </a>

        <!-- VIEW DASHBOARD -->
        <a href="ai_dashboard.php" class="ai-card">
            <div class="ai-card-icon">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3>View Dashboard</h3>
            <p>
                Analyze AI clustering results with interactive charts.
                View risk percentages and danger levels for each login.
            </p>
            <div class="ai-card-btn">
                📊 View Now
            </div>
        </a>

        <!-- TEST DATA -->
        <a href="test_risk_levels.php" class="ai-card">
            <div class="ai-card-icon">
                <i class="fa-solid fa-flask"></i>
            </div>
            <h3>Generate Test Data</h3>
            <p>
                Insert test login scenarios with different risk levels
                (Normal, Suspicious, High Risk) for model testing.
            </p>
            <div class="ai-card-btn">
                🧪 Generate
            </div>
        </a>

    </div>

    <!-- ===== INFO SECTION ===== -->
    <div class="ai-stats">
        <h3><i class="fa-solid fa-info-circle"></i> How It Works</h3>
        <div class="instruction-section">
            <p class="instruction-paragraph">
                <strong class="instruction-title">1. Generate Test Data</strong><br>
                Create sample login records with different scenarios (normal, suspicious, high-risk)
            </p>
            <p class="instruction-paragraph">
                <strong class="instruction-title">2. Train Model</strong><br>
                Run K-Means clustering to analyze patterns and calculate risk percentages (0-100%)
            </p>
            <p class="instruction-paragraph">
                <strong class="instruction-title">3. View Results</strong><br>
                Check the dashboard to see classified logins with explainable AI reasoning
            </p>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="ai-footer">
        <p>
            <a href="../system.php">← Back to System</a> | 
            <a href="../logs/view_logs.php">View Logs</a> | 
            <a href="run_ai.php">AI Control Panel</a>
        </p>
    </div>

</div>

<?php mysqli_close($conn); ?>

</body>
</html>
