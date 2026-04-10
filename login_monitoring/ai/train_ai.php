<?php
set_time_limit(0);

// Handle training request via ?run=1
if (isset($_GET['run'])) {
    include "../db.php";
    
    // Ensure columns exist
    $res = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'ai_cluster'");
    if (mysqli_num_rows($res) == 0) {
        mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN ai_cluster TINYINT NULL");
    }
    $res2 = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'risk_percentage'");
    if (mysqli_num_rows($res2) == 0) {
        mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN risk_percentage DECIMAL(5,2) DEFAULT 0.00");
    }
    
    // Try Python first - use optimized training script
    $script = realpath(__DIR__ . '/train_kmeans_optimal.py');
    $output = null;
    
    if ($script && file_exists($script)) {
        $candidates = ["python", "py -3", "python3"];
        foreach ($candidates as $bin) {
            $cmd = $bin . ' ' . escapeshellarg($script) . ' 2>&1';
            $out = @shell_exec($cmd);
            if ($out !== null && trim($out) !== '') {
                $output = $out;
                break;
            }
        }
    }
    
    // If Python failed, use PHP fallback
    if ($output === null || stripos($output, 'Error') !== false || stripos($output, 'Traceback') !== false) {
        include_once __DIR__ . '/apply_simple_ai.php';
        if (function_exists('apply_simple_ai_fallback')) {
            $output = apply_simple_ai_fallback();
        } else {
            $output = "Fallback script not available";
        }
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    echo $output;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Train AI Model - AI Module</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/ai_global.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

</head>

<body>

<div class="train-container">

    <!-- ===== BREADCRUMB ===== -->
    <div class="breadcrumb">
        <a href="../system.php"><i class="fa-solid fa-home"></i> System</a> / 
        <a href="ai_index.php"><i class="fa-solid fa-brain"></i> AI Module</a> / 
        <i class="fa-solid fa-dumbbell"></i> Train Model
    </div>

    <!-- ===== HEADER ===== -->
    <div class="train-header">
        <h1><i class="fa-solid fa-dumbbell"></i> Train K-Means Model</h1>
        <p>Analyze login behavior and calculate risk percentages</p>
    </div>

    <!-- ===== INFO BOXES ===== -->
    <div class="train-info">
        <div class="info-box">
            <i class="fa-solid fa-database"></i>
            <span>Data Source</span>
            <strong>login_logs</strong>
        </div>

        <div class="info-box">
            <i class="fa-solid fa-cube"></i>
            <span>Algorithm</span>
            <strong>K-Means (3 clusters)</strong>
        </div>

        <div class="info-box">
            <i class="fa-solid fa-chart-line"></i>
            <span>Output</span>
            <strong>Risk % (0-100)</strong>
        </div>
    </div>

    <!-- ===== TEST RISK FORM ===== -->
    <div class="risk-test-box">
        <h3><i class="fa-solid fa-vial"></i> Test Risk Percentage</h3>
        <p class="risk-test-desc">Enter login details to calculate risk percentage</p>
        
        <div class="risk-form">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="testUsername" placeholder="e.g., user123" value="test_user">
            </div>
            
            <div class="form-group">
                <label>Device Type</label>
                <select id="testDevice">
                    <option value="Desktop">Desktop</option>
                    <option value="Mobile">Mobile</option>
                    <option value="Tablet">Tablet</option>
                    <option value="Laptop">Laptop</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Country</label>
                <select id="testCountry">
                    <option value="Vietnam">Vietnam</option>
                    <option value="Thailand">Thailand</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Malaysia">Malaysia</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Philippines">Philippines</option>
                    <option value="Cambodia">Cambodia</option>
                    <option value="Laos">Laos</option>
                    <option value="Myanmar">Myanmar</option>
                    <option value="Brunei">Brunei</option>
                    <option value="Japan">Japan</option>
                    <option value="South Korea">South Korea</option>
                    <option value="China">China</option>
                    <option value="Taiwan">Taiwan</option>
                    <option value="Hong Kong">Hong Kong</option>
                    <option value="India">India</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Bangladesh">Bangladesh</option>
                    <option value="Sri Lanka">Sri Lanka</option>
                    <option value="Nepal">Nepal</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="France">France</option>
                    <option value="Germany">Germany</option>
                    <option value="Italy">Italy</option>
                    <option value="Spain">Spain</option>
                    <option value="Netherlands">Netherlands</option>
                    <option value="Poland">Poland</option>
                    <option value="Russia">Russia</option>
                    <option value="United States">United States</option>
                    <option value="Canada">Canada</option>
                    <option value="Mexico">Mexico</option>
                    <option value="Brazil">Brazil</option>
                    <option value="Argentina">Argentina</option>
                    <option value="Egypt">Egypt</option>
                    <option value="South Africa">South Africa</option>
                    <option value="United Arab Emirates">United Arab Emirates</option>
                    <option value="Saudi Arabia">Saudi Arabia</option>
                    <option value="Australia">Australia</option>
                    <option value="New Zealand">New Zealand</option>
                    <option value="Unknown">Unknown</option>
                </select>
            </div>
            
            <div class="form-group form-group-datetime">
                <label>Login Time</label>
                <div class="datetime-input-wrapper">
                    <input type="datetime-local" id="testLoginTime">
                    <button type="button" class="btn-icon-calendar" onclick="openDatePicker()" title="Open calendar">
                        <i class="fa-solid fa-calendar"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select id="testStatus">
                    <option value="SUCCESS">SUCCESS</option>
                    <option value="FAIL">FAIL</option>
                </select>
            </div>
            
            <div class="form-group form-group-button">
                <label>&nbsp;</label>
                <button class="btn btn-secondary btn-calculate" onclick="calculateRiskPercentage()">
                    <i class="fa-solid fa-calculator"></i> Calculate Risk %
                </button>
            </div>
        </div>
        
        <!-- Risk Result Display -->
        <div class="risk-result" id="riskResult" style="display:none;">
            <div class="risk-percentage">
                <div class="risk-circle" id="riskCircle">
                    <span id="riskPercent">0</span>%
                </div>
            </div>
            <div class="risk-level">
                <p>Risk Level: <strong id="riskLevelText">-</strong></p>
                <p class="risk-explanation" id="riskExplanation"></p>
            </div>
            
            <!-- RISK BREAKDOWN - DETAILED FACTORS -->
            <div class="risk-breakdown">
                <h4><i class="fa-solid fa-list-check"></i> Risk Breakdown</h4>
                <div class="breakdown-grid">
                    <!-- Device Risk -->
                    <div class="breakdown-item">
                        <div class="breakdown-header">
                            <strong>Device Type</strong>
                            <span class="breakdown-score" id="deviceScore">-</span>%
                        </div>
                        <p class="breakdown-reason" id="deviceReason">-</p>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" id="deviceFill" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Country Risk -->
                    <div class="breakdown-item">
                        <div class="breakdown-header">
                            <strong>Location</strong>
                            <span class="breakdown-score" id="countryScore">-</span>%
                        </div>
                        <p class="breakdown-reason" id="countryReason">-</p>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" id="countryFill" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Login Time Risk -->
                    <div class="breakdown-item">
                        <div class="breakdown-header">
                            <strong>Login Time</strong>
                            <span class="breakdown-score" id="timeScore">-</span>%
                        </div>
                        <p class="breakdown-reason" id="timeReason">-</p>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" id="timeFill" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Status Risk -->
                    <div class="breakdown-item">
                        <div class="breakdown-header">
                            <strong>Login Status</strong>
                            <span class="breakdown-score" id="statusScore">-</span>%
                        </div>
                        <p class="breakdown-reason" id="statusReason">-</p>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" id="statusFill" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Brute Force Risk -->
                    <div class="breakdown-item">
                        <div class="breakdown-header">
                            <strong>Brute-Force Pattern</strong>
                            <span class="breakdown-score" id="bruteScore">-</span>%
                        </div>
                        <p class="breakdown-reason" id="bruteReason">-</p>
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" id="bruteFill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BUTTONS ===== -->
    <div class="train-buttons">
        <button class="btn btn-primary" id="trainBtn" onclick="startTraining()">
            <i class="fa-solid fa-play"></i> START TRAINING
        </button>
        <button class="btn btn-danger" id="resetBtn" onclick="resetData()">
            <i class="fa-solid fa-trash"></i> RESET DATA
        </button>
    </div>

    <!-- ===== PROGRESS BAR ===== -->
    <div class="progress-bar" id="progressBar">
        <div class="progress-fill" id="progressFill">0%</div>
    </div>

    <!-- ===== STATUS ===== -->
    <div class="status" id="status"></div>

    <!-- ===== OUTPUT ===== -->
    <div class="train-output" id="output">
        <pre id="outputText"></pre>
    </div>

    <!-- ===== ACCURACY CHARTS ===== -->
    <div class="train-charts" id="chartsSection" style="display:none; margin-top: 30px;">
        <h2><i class="fa-solid fa-chart-line"></i> Training Results & Metrics</h2>
        
        <!-- Accuracy Curve Chart (Full Width) -->
        <div class="chart-container" style="margin-bottom: 20px;">
            <h3>Accuracy Curve</h3>
            <canvas id="accuracyCurveChart" height="80"></canvas>
        </div>
        
        <div class="charts-grid">
            <!-- Accuracy Metrics -->
            <div class="chart-container">
                <h3>Accuracy Metrics</h3>
                <table class="metrics-table">
                    <tr>
                        <td>Model Accuracy</td>
                        <td><strong id="metricAccuracy">-</strong>%</td>
                    </tr>
                    <tr>
                        <td>Silhouette Score</td>
                        <td><strong id="metricSilhouette">-</strong></td>
                    </tr>
                    <tr>
                        <td>Davies-Bouldin Index</td>
                        <td><strong id="metricDavies">-</strong></td>
                    </tr>
                    <tr>
                        <td>Calinski-Harabasz Index</td>
                        <td><strong id="metricCalinski">-</strong></td>
                    </tr>
                </table>
            </div>

            <!-- Cluster Distribution -->
            <div class="chart-container">
                <h3>Cluster Distribution</h3>
                <canvas id="clusterChart"></canvas>
            </div>

            <!-- Risk Distribution -->
            <div class="chart-container">
                <h3>Risk Statistics</h3>
                <table class="metrics-table">
                    <tr>
                        <td>Minimum Risk</td>
                        <td><strong id="riskMin">-</strong>%</td>
                    </tr>
                    <tr>
                        <td>Maximum Risk</td>
                        <td><strong id="riskMax">-</strong>%</td>
                    </tr>
                    <tr>
                        <td>Average Risk</td>
                        <td><strong id="riskAvg">-</strong>%</td>
                    </tr>
                    <tr>
                        <td>Total Records</td>
                        <td><strong id="totalRecords">-</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ===== FOOTER ===== -->
<div style="max-width: 900px; margin: 20px auto; padding-bottom: 20px;">
    <div class="train-footer">
        <a href="ai_index.php"><i class="fa-solid fa-arrow-left"></i> Back to AI Module</a>
        <a href="ai_dashboard.php"><i class="fa-solid fa-chart-pie"></i> View Dashboard</a>
        <a href="../system.php"><i class="fa-solid fa-home"></i> System</a>
    </div>
</div>

<script>

function updateProgress(percent) {
    document.getElementById('progressFill').style.width = percent + '%';
    document.getElementById('progressFill').textContent = percent + '%';
}

function addOutput(text) {
    const output = document.getElementById('outputText');
    output.textContent += text + '\n';
    document.getElementById('output').scrollTop = document.getElementById('output').scrollHeight;
}

function startTraining() {
    const btn = document.getElementById('trainBtn');
    const output = document.getElementById('output');
    const status = document.getElementById('status');
    
    // Reset UI
    output.classList.add('active');
    document.getElementById('outputText').textContent = '';
    status.classList.remove('active', 'success', 'error');
    updateProgress(0);
    btn.disabled = true;
    
    addOutput('🤖 STARTING AI TRAINING...\n');
    addOutput('=' .repeat(60) + '\n');
    
    // Call train_ai.php?run=1 to execute training and get output
    fetch('train_ai.php?run=1')
        .then(response => response.text())
        .then(text => {
            // Display output
            const lines = text.split('\n');
            lines.forEach(line => {
                if (line.trim()) addOutput(line);
            });
            
            // Check if successful
            if (text.toLowerCase().includes('updated') || 
                text.toLowerCase().includes('successfully') ||
                text.toLowerCase().includes('training completed')) {
                addOutput('\n✅ Training completed successfully!\n');
                status.classList.add('active', 'success');
                status.innerHTML = '✅ Training completed successfully!';
                updateProgress(100);
                
                // Load and display metrics
                loadAndDisplayMetrics();
            } else {
                addOutput('\n⚠️ Training finished with output above.\n');
                status.classList.add('active', 'success');
                status.innerHTML = '✅ Completed!';
                updateProgress(100);
                
                // Load and display metrics
                loadAndDisplayMetrics();
            }
            btn.disabled = false;
        })
        .catch(err => {
            addOutput('\n❌ Error: ' + err.message + '\n');
            status.classList.add('active', 'error');
            status.innerHTML = '❌ Training failed!';
            btn.disabled = false;
        });
    
    // Simulate progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 25;
        if (progress > 85) progress = 85;
        updateProgress(Math.floor(progress));
    }, 500);
    
    setTimeout(() => clearInterval(interval), 10000);
}

function resetData() {
    if (confirm('🚨 This will delete all AI cluster data. Continue?')) {
        fetch('api_reset.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Data reset successfully!');
                    location.reload();
                } else {
                    alert('❌ Reset failed: ' + data.message);
                }
            });
    }
}

// Open Date/Time Picker
function openDatePicker() {
    const input = document.getElementById('testLoginTime');
    if (input.showPicker && typeof input.showPicker === 'function') {
        input.showPicker();
    } else {
        input.click();
    }
    input.focus();
}

// Auto-load output on page load
window.addEventListener('load', function() {
    // Check if training was just done
    if (sessionStorage.getItem('trainingDone')) {
        document.getElementById('output').classList.add('active');
        sessionStorage.removeItem('trainingDone');
    }
    
    // Set current datetime for test
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('testLoginTime').value = now.toISOString().slice(0, 16);
});

// Calculate Risk Percentage
function calculateRiskPercentage() {
    const username = document.getElementById('testUsername').value || 'test_user';
    const device = document.getElementById('testDevice').value;
    const country = document.getElementById('testCountry').value;
    const loginTime = document.getElementById('testLoginTime').value;
    const status = document.getElementById('testStatus').value;
    
    // Validation
    if (!username.trim()) {
        alert('❌ Please enter a username');
        return;
    }
    
    if (!loginTime) {
        alert('❌ Please select login time');
        return;
    }
    
    const data = {
        username: username.trim(),
        device_type: device,
        country: country,
        login_time: loginTime,
        status: status
    };
    
    // Show loading state
    const resultDiv = document.getElementById('riskResult');
    resultDiv.style.display = 'grid';
    document.getElementById('riskPercent').textContent = '...';
    document.getElementById('riskLevelText').textContent = 'Analyzing...';
    
    fetch('api_calculate_risk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            displayRiskResult(result);
        } else {
            alert('❌ Error: ' + (result.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Connection error: ' + error.message);
    });
}

function displayRiskResult(result) {
    const percentage = result.risk_percentage;
    const level = result.risk_level;
    const breakdown = result.breakdown || {};
    
    // Update main display
    document.getElementById('riskPercent').textContent = Math.round(percentage);
    document.getElementById('riskLevelText').textContent = level;
    document.getElementById('riskExplanation').textContent = result.explanation || 'Risk analysis complete';
    
    // Update circle color based on risk level
    const riskCircle = document.getElementById('riskCircle');
    riskCircle.classList.remove('normal', 'suspicious', 'warning', 'danger');
    
    if (percentage <= 25) {
        riskCircle.classList.add('normal');
    } else if (percentage <= 50) {
        riskCircle.classList.add('suspicious');
    } else if (percentage <= 75) {
        riskCircle.classList.add('warning');
    } else {
        riskCircle.classList.add('danger');
    }
    
    // Update breakdown details
    updateBreakdownItem('device', breakdown.device_risk || 0, breakdown.device_reason || '-', 15);
    updateBreakdownItem('country', breakdown.country_risk || 0, breakdown.country_reason || '-', 10);
    updateBreakdownItem('time', breakdown.time_risk || 0, breakdown.time_reason || '-', 15);
    updateBreakdownItem('status', breakdown.status_risk || 0, breakdown.status_reason || '-', 25);
    updateBreakdownItem('brute', breakdown.brute_force_risk || 0, breakdown.brute_force_reason || '-', 15);
    
    document.getElementById('riskResult').style.display = 'grid';
}

function updateBreakdownItem(itemType, score, reason, maxScore) {
    const scorePercent = (score / maxScore) * 100;
    
    document.getElementById(itemType + 'Score').textContent = Math.round(score);
    document.getElementById(itemType + 'Reason').textContent = reason;
    document.getElementById(itemType + 'Fill').style.width = Math.min(scorePercent, 100) + '%';
    
    // Color fill based on score
    const fill = document.getElementById(itemType + 'Fill');
    if (scorePercent <= 33) {
        fill.style.backgroundColor = '#22c55e';  // Green
    } else if (scorePercent <= 66) {
        fill.style.backgroundColor = '#eab308';  // Yellow
    } else {
        fill.style.backgroundColor = '#ef4444';  // Red
    }
}

// Auto-update disabled - risk percentage is only calculated when clicking the button
let updateTimeout;

// Load and display training metrics
function loadAndDisplayMetrics() {
    fetch('api_get_metrics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMetrics(data);
            }
        })
        .catch(error => console.error('Error loading metrics:', error));
    
    // Also load accuracy curve
    loadAccuracyCurve();
}

function displayMetrics(data) {
    // Display metrics table
    document.getElementById('metricAccuracy').textContent = data.metrics.accuracy;
    document.getElementById('metricSilhouette').textContent = data.metrics.silhouette_score;
    document.getElementById('metricDavies').textContent = data.metrics.davies_bouldin_index;
    document.getElementById('metricCalinski').textContent = data.metrics.calinski_harabasz_index;
    
    // Display risk stats
    document.getElementById('riskMin').textContent = data.risk_stats.min;
    document.getElementById('riskMax').textContent = data.risk_stats.max;
    document.getElementById('riskAvg').textContent = data.risk_stats.avg;
    document.getElementById('totalRecords').textContent = data.distribution.total;
    
    // Show charts section
    document.getElementById('chartsSection').style.display = 'block';
    
    // Draw cluster distribution chart
    drawClusterChart(data.distribution);
}

let clusterChartInstance = null;

function drawClusterChart(distribution) {
    const ctx = document.getElementById('clusterChart');
    
    // Destroy previous chart if exists
    if (clusterChartInstance) {
        clusterChartInstance.destroy();
    }
    
    clusterChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                `Normal (${distribution.cluster_0})`,
                `Suspicious (${distribution.cluster_1})`,
                `High Risk (${distribution.cluster_2})`
            ],
            datasets: [{
                data: [
                    distribution.cluster_0_pct,
                    distribution.cluster_1_pct,
                    distribution.cluster_2_pct
                ],
                backgroundColor: [
                    '#00ff99',  // Green
                    '#ffff00',  // Yellow
                    '#ff5555'   // Red
                ],
                borderColor: ['#00cc77', '#cccc00', '#ff3333'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#ccc',
                        font: { size: 12 }
                    }
                }
            }
        }
    });
}

let accuracyCurveChartInstance = null;

// Load accuracy curve data
function loadAccuracyCurve() {
    fetch('api_get_curve.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                drawAccuracyCurveChart(data.data);
            }
        })
        .catch(error => console.error('Error loading accuracy curve:', error));
}

// Draw accuracy curve chart
function drawAccuracyCurveChart(curveData) {
    const ctx = document.getElementById('accuracyCurveChart');
    
    if (!ctx) return;
    
    // Destroy previous chart if exists
    if (accuracyCurveChartInstance) {
        accuracyCurveChartInstance.destroy();
    }
    
    accuracyCurveChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: curveData.train_samples,
            datasets: [
                {
                    label: 'Train Accuracy',
                    data: curveData.train_accuracy,
                    borderColor: '#2563eb',  // Blue
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Validation Accuracy',
                    data: curveData.val_accuracy,
                    borderColor: '#ff9500',  // Orange
                    backgroundColor: 'rgba(255, 149, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#ff9500',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#e0e0e0',
                        font: { size: 13, weight: 'bold' },
                        padding: 15
                    }
                },
                title: {
                    display: false
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Training Samples',
                        color: '#00ff99',
                        font: { size: 12, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(0, 255, 153, 0.1)',
                        drawBorder: true,
                        borderColor: 'rgba(0, 255, 153, 0.3)'
                    },
                    ticks: {
                        color: '#e0e0e0',
                        font: { size: 11 }
                    }
                },
                y: {
                    min: 0,
                    max: 1,
                    title: {
                        display: true,
                        text: 'Accuracy',
                        color: '#00ff99',
                        font: { size: 12, weight: 'bold' }
                    },
                    grid: {
                        color: 'rgba(0, 255, 153, 0.1)',
                        drawBorder: true,
                        borderColor: 'rgba(0, 255, 153, 0.3)'
                    },
                    ticks: {
                        color: '#e0e0e0',
                        font: { size: 11 },
                        callback: function(value) {
                            return (value * 100).toFixed(0) + '%';
                        }
                    }
                }
            }
        }
    });
}

</script>

</body>
</html>
