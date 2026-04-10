<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Dashboard</title>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="assets/css/system.css">
</head>

<body>

<header class="top-header">
    <h1>
        <i class="fa-solid fa-shield-halved"></i>
        Login Monitoring System
    </h1>
    <p>Analyze login behavior with monitoring & AI</p>
</header>

<main class="dashboard">

    <!-- MODE A -->
    <div class="card mode-a">
        <div class="card-icon">
            <i class="fa-solid fa-right-to-bracket"></i>
        </div>

        <h2>Mode A</h2>
        <h4>No Monitoring</h4>

        <ul>
            <li>Basic login system</li>
            <li>No alert detection</li>
            <li>Used for comparison</li>
            <li>Includes login history</li>
        </ul>

        <a href="modeA/login.php" class="card-btn">
            Enter Mode A
        </a>
    </div>

    <!-- MODE B -->
    <div class="card mode-b">
        <div class="card-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h2>Mode B</h2>
        <h4>With Monitoring & Alerts</h4>

        <ul>
            <li>Track failed attempts</li>
            <li>Red / Yellow / Green alerts</li>
            <li>Suspicious behavior detection</li>
            <li>Login history & warnings</li>
        </ul>

        <a href="modeB/login.php" class="card-btn">
            Enter Mode B
        </a>
    </div>

</main>

<!-- AI SECTION -->
<section class="ai-section">
    <div class="ai-card">
        <div class="ai-icon">
            <i class="fa-solid fa-brain"></i>
        </div>

        <h2>AI Login Analysis</h2>

        <p>
            This module applies Machine Learning (K-Means clustering)
            to analyze login behavior based on device, IP, time and location
            to detect suspicious patterns and calculate risk percentages.
        </p>

        <a href="ai/ai_index.php" class="ai-btn">
            Open AI Module
        </a>
    </div>
</section>

<footer class="footer">
    Computing Research Project – Login Behavior & AI Monitoring
</footer>

</body>
</html>
