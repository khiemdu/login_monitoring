<?php
// NOTE: Do not auto-run the Python training on dashboard load. Use the Train page to run training.
?>
<?php include "../db.php"; ?>
<?php
$clusterCount = [
    "normal" => 0,
    "suspicious" => 0,
    "high" => 0
];


$q = mysqli_query($conn, "
    SELECT ai_cluster, COUNT(*) as total 
    FROM login_logs 
    WHERE ai_cluster IS NOT NULL
    GROUP BY ai_cluster
");

while ($c = mysqli_fetch_assoc($q)) {
    if ($c['ai_cluster'] == 0) $clusterCount['normal'] = $c['total'];
    if ($c['ai_cluster'] == 1) $clusterCount['suspicious'] = $c['total'];
    if ($c['ai_cluster'] == 2) $clusterCount['high'] = $c['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Login Behavior Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/ai_global.css">
<link rel="stylesheet" href="../assets/css/ai_dashboard.css">
</head>

<body>

<div class="ai-wrapper">

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
    <a href="ai_index.php"><i class="fa-solid fa-brain"></i> AI Module</a> / 
    <span><i class="fa-solid fa-chart-pie"></i> Dashboard</span>
</div>

<h1 class="ai-title">
    <i class="fa-solid fa-brain"></i>
    AI Login Behavior Analysis
</h1>

<div class="ai-back-wrap">
    <a href="run_ai.php" class="ai-back-btn">
        <i class="fa-solid fa-rotate-left"></i>
        Back to AI Control Panel
    </a>
</div>

<div class="ai-actions" style="margin:12px 0;">
    <a href="train_ai.php" class="ai-btn" style="margin-right:8px;">
        <i class="fa-solid fa-dumbbell"></i> Run Training
    </a>
    <button class="ai-btn" id="fallbackBtn" style="margin-right:8px;">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Apply PHP AI (fallback)
    </button>
    <button class="ai-btn" id="syncBtn">
        <i class="fa-solid fa-sync-alt"></i> Sync Clusters
    </button>
    <span id="actionStatus" style="margin-left:12px;color:#9fdbe8"></span>
</div>

<p class="ai-desc">
    This dashboard visualizes AI-based login behavior classification
    with Explainable AI reasoning.
</p>

<!-- ===== AI CHARTS ===== -->
<div class="ai-charts">
    <div class="chart-box">
        <h3>AI Login Behavior Distribution</h3>
        <canvas id="pieChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>Login Attempts by Risk Level</h3>
        <canvas id="barChart"></canvas>
    </div>
</div>

<div class="table-container">
<table class="ai-table">
<tr>
    <th>User</th>
    <th>Device</th>
    <th>Country</th>
    <th>Login Time</th>
    <th>Status</th>
    <th>AI Cluster</th>
    <th>Risk %</th>
    <th>Risk Level</th>
    <th>AI Explanation</th>
</tr>

<?php
$r = mysqli_query($conn, "SELECT id, username, device_type, country, login_time, status, alert_level, ai_cluster, risk_percentage FROM login_logs ORDER BY id DESC LIMIT 500");
while ($row = mysqli_fetch_assoc($r)) {

    /* ===== STATUS ===== */
    if ($row['status'] === "FAIL") {
        $statusText = "FAILED";
        $statusClass = "status status-failed";
        $statusIcon = "fa-circle-xmark";
    } else {
        if ($row['alert_level'] === "GREEN") {
            $statusText = "SUCCESS";
            $statusClass = "status status-success";
            $statusIcon = "fa-circle-check";
        } elseif ($row['alert_level'] === "YELLOW") {
            $statusText = "WARNING";
            $statusClass = "status status-warning";
            $statusIcon = "fa-triangle-exclamation";
        } else {
            $statusText = "SUSPICIOUS";
            $statusClass = "status status-suspicious";
            $statusIcon = "fa-shield-halved";
        }
    }

    /* ===== AI CLUSTER + EXPLAINABLE AI ===== */
    $riskPct = isset($row['risk_percentage']) ? floatval($row['risk_percentage']) : null;
    $cluster = $row['ai_cluster'];
    
    // Determine risk class for styling
    $riskClass = '';
    if ($cluster === NULL || $cluster === '' || $riskPct === null) {
        $clusterValue = "-";
        $clusterClass = "";
        $risk = "Not Analyzed";
        $riskIcon = "fa-robot";
        $explain = "This record has not been processed by the AI model yet.";
        $riskClass = "risk-not-analyzed";
    }
    elseif ($cluster == 0 || $riskPct <= 35) {
        $clusterValue = "0 (Normal)";
        $clusterClass = "cluster0";
        $risk = "Normal";
        $riskIcon = "fa-check-circle";
        $explain = "Login behavior matches the user's usual device, location, and time pattern.";
        $riskClass = "risk-normal";
    }
    elseif ($cluster == 1 || ($riskPct > 35 && $riskPct <= 65)) {
        $clusterValue = "1 (Suspicious)";
        $clusterClass = "cluster1";
        $risk = "Suspicious";
        $riskIcon = "fa-triangle-exclamation";
        $explain = "Login shows slight deviation such as unusual time or less common device.";
        $riskClass = "risk-suspicious";
    }
    else {
        $clusterValue = "2 (High Risk)";
        $clusterClass = "cluster2";
        $risk = "High Risk";
        $riskIcon = "fa-skull-crossbones";

        if ($row['status'] === "FAIL") {
            $explain = "Multiple failed login attempts detected, indicating possible brute-force or attack behavior.";
        } else {
            $explain = "Login originates from unfamiliar device or high-risk location identified by AI clustering.";
        }
        $riskClass = "risk-high";
    }
?>

<tr class="<?= $clusterClass ?>">
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= htmlspecialchars($row['device_type'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['country'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['login_time'] ?? '-') ?></td>

    <td>
        <span class="<?= $statusClass ?>">
            <i class="fa-solid <?= $statusIcon ?>"></i>
            <?= $statusText ?>
        </span>
    </td>

    <td><?= $clusterValue ?></td>

    <td class="<?= $riskClass ?>">
        <strong><?= isset($row['risk_percentage']) ? number_format($row['risk_percentage'], 1) . '%' : 'N/A' ?></strong>
    </td>

    <td>
        <i class="fa-solid <?= $riskIcon ?>"></i>
        <?= $risk ?>
    </td>

    <td>
        <i class="fa-solid fa-lightbulb"></i>
        <?= $explain ?>
    </td>
</tr>

<?php } ?>
</table>
</div>

<div class="ai-note">
🤖 Explainable AI provides human-readable reasons for each AI decision,
improving transparency and trust in the system.
</div>

<!-- FOOTER -->
<div class="ai-footer">
    <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
    <a href="ai_index.php"><i class="fa-solid fa-brain"></i> AI Module</a> / 
    <a href="../logs/view_logs.php"><i class="fa-solid fa-history"></i> Login History</a>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.getElementById('fallbackBtn').addEventListener('click', function(){
    if (!confirm('Apply PHP fallback to compute AI clusters?')) return;
    const statusEl = document.getElementById('actionStatus');
    statusEl.textContent = 'Running...';
    fetch('apply_simple_ai.php?run_fallback=1')
        .then(r => r.text())
        .then(txt => {
            statusEl.textContent = 'Done';
            alert('Fallback output:\n' + txt);
            location.reload();
        })
        .catch(err => {
            statusEl.textContent = 'Error';
            alert('Error running fallback: ' + err.message);
        });
});

document.getElementById('syncBtn').addEventListener('click', function(){
    if (!confirm('Sync all AI clusters to match risk percentages?')) return;
    const statusEl = document.getElementById('actionStatus');
    statusEl.textContent = 'Syncing...';
    fetch('sync_cluster.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = 'Done! Updated: ' + data.records_updated;
                alert('✅ Sync completed!\n\nUpdated: ' + data.records_updated + ' records\n' +
                      'Normal: ' + data.distribution.normal + '\n' +
                      'Suspicious: ' + data.distribution.suspicious + '\n' +
                      'High Risk: ' + data.distribution.high_risk);
                setTimeout(() => location.reload(), 1000);
            } else {
                statusEl.textContent = 'Error';
                alert('❌ Sync failed: ' + data.message);
            }
        })
        .catch(err => {
            statusEl.textContent = 'Error';
            alert('❌ Error: ' + err.message);
        });
});
</script>

<script>
const normal = <?= $clusterCount['normal'] ?>;
const suspicious = <?= $clusterCount['suspicious'] ?>;
const high = <?= $clusterCount['high'] ?>;

new Chart(document.getElementById("pieChart"), {
    type: "pie",
    data: {
        labels: ["Normal", "Suspicious", "High Risk"],
        datasets: [{
            data: [normal, suspicious, high],
            backgroundColor: ["#2cff9d", "#ffd84d", "#ff4c4c"]
        }]
    }
});

new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: {
        labels: ["Normal", "Suspicious", "High Risk"],
        datasets: [{
            label: "Login Attempts",
            data: [normal, suspicious, high],
            backgroundColor: ["#2cff9d", "#ffd84d", "#ff4c4c"]
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>
