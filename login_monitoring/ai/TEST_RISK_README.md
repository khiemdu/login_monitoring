# Test Risk Percentage - Advanced AI Risk Calculator

## Overview
The **Test Risk Percentage** tool is an advanced risk assessment system that analyzes login attempts and calculates security risk scores based on multiple factors. It now combines machine learning anomaly detection with heuristic rules for comprehensive threat analysis.

## How It Works

### Risk Factors (Total: 0-100%)

| Factor | Max Score | Description |
|--------|-----------|-------------|
| **Anomaly Score** | 40% | ML-based detection using K-Means clustering - detects unusual patterns |
| **Login Status** | 25% | Failed attempts in 24h history (Failed = higher risk) |
| **Device Type** | 15% | Mobile/Tablet devices are riskier than Desktop |
| **Geographic Location** | 10% | Unknown location or unusual country = higher risk |
| **Login Time** | 15% | Unusual hours (midnight-6am) = higher risk |
| **Brute-Force Pattern** | 15% | Multiple failed attempts in short time (15 min) |

### Risk Levels

```
🟢 NORMAL          (0-25%)    - Normal login behavior
🟡 SUSPICIOUS      (25-50%)   - Some unusual characteristics
🟠 WARNING         (50-75%)   - Significant anomalies detected
🔴 HIGH RISK       (75-100%)  - Immediate concern
```

## Using the Tool

### Step 1: Enter Login Details
1. **Username**: Enter the username being tested
2. **Device Type**: Select from Desktop, Mobile, Tablet, or Laptop
3. **Country**: Select the login location
4. **Login Time**: Choose date and time of login attempt
5. **Status**: Select SUCCESS or FAIL

### Step 2: Calculate Risk
Click the **"Calculate Risk %"** button or wait for auto-calculation (500ms debounce)

### Step 3: Review Breakdown
The tool displays:
- **Overall Risk Score**: Circular gauge with color coding
- **Risk Level**: NORMAL / SUSPICIOUS / WARNING / HIGH RISK
- **Detailed Breakdown**: Shows contribution of each factor
  - Score bar showing percentage contribution
  - Reason/explanation for each factor
  - Historical data (failed attempts count, etc.)

## Examples

### Example 1: Normal Login
```
Username: john_user
Device: Desktop
Country: Vietnam
Time: 14:30 (2:30 PM)
Status: SUCCESS
→ Risk: 8% (🟢 NORMAL)
  - Device Risk: 0% (Desktop is safe)
  - Location Risk: 0% (Vietnam is normal)
  - Time Risk: 0% (Business hours)
  - Status Risk: 2% (Successful login)
  - Brute-Force: 0% (No failed attempts)
```

### Example 2: Suspicious Login
```
Username: john_user
Device: Mobile
Country: Unknown
Time: 03:45 (3:45 AM)
Status: FAIL
→ Risk: 58% (🟡 SUSPICIOUS)
  - Device Risk: 12% (Mobile device)
  - Location Risk: 10% (Unknown location)
  - Time Risk: 15% (Very unusual hour)
  - Status Risk: 18% (Failed + recent fails)
  - Brute-Force: 10% (Multiple attempts)
```

### Example 3: High Risk
```
Username: john_user
Device: Tablet
Country: Unknown
Time: 02:00 (2 AM)
Status: FAIL (with 5 failed attempts in 15 min)
→ Risk: 88% (🔴 HIGH RISK)
  - Anomaly Score: 40% (Very unusual pattern from ML)
  - Device Risk: 12% (Tablet)
  - Location Risk: 10% (Unknown)
  - Time Risk: 15% (Midnight hours)
  - Status Risk: 24% (Chain of failures)
  - Brute-Force: 15% (Attack pattern detected)
```

## Features

### ✨ Real-Time Analysis
- Auto-calculates when you change any field (500ms debounce)
- No need to click "Calculate" button after first use
- Instant visual feedback with color changes

### 📊 Detailed Breakdown
- Each risk factor shown with individual scores
- Color-coded progress bars (green → yellow → red)
- Explanatory text for each factor
- Database queries for accurate historical data

### 🤖 ML Integration
- Uses K-Means clustering model trained on historical logins
- Anomaly detection based on feature distances
- Fallback to heuristic rules if Python unavailable

### ✅ Input Validation
- Username validation
- Login time requirement
- Graceful error handling

## Technical Details

### Backend APIs

**PHP API**: `/ai/api_calculate_risk.php` (POST)
```json
Request:
{
  "username": "test_user",
  "device_type": "Desktop",
  "country": "Vietnam",
  "login_time": "2026-04-08T14:30",
  "status": "SUCCESS"
}

Response:
{
  "success": true,
  "risk_percentage": 15.5,
  "risk_level": "🟢 NORMAL",
  "explanation": "Risk analysis based on multiple security factors",
  "breakdown": {
    "device_risk": 0,
    "device_reason": "Desktop (normal)",
    "country_risk": 0,
    "country_reason": "Vietnam (known location)",
    ...
  },
  "details": { ... }
}
```

**Python API** (optional): `/ai/api_calculate_risk_advanced.py`
- Advanced ML-based risk calculation
- Used automatically if K-Means model available
- Calculates anomaly scores using trained clusters

### Database Queries
- Counts failed attempts in 24h for user
- Detects brute-force patterns (failed attempts in 15 min)
- Returns accurate risk scores based on real history

## Tips & Tricks

1. **Test Different Hours**: Try login at 2 AM vs 2 PM to see time impact
2. **Test Failed Attempts**: Change status to FAIL for higher risk
3. **Test Unknown Locations**: Select "Unknown" to see location impact
4. **Mobile vs Desktop**: Compare risk between device types
5. **Check Recent Database**: If test_user doesn't have failures, create test data first

## Troubleshooting

### "No data provided" error
- Check that all form fields are filled
- Ensure login time is selected

### Risk doesn't change on input
- Wait 500ms after change (debounce delay)
- Ensure JavaScript is enabled
- Check browser console for errors

### API returns error
- Verify database connection
- Check that `login_logs` table exists
- Review error message for details

## Integration with System

This tool integrates with the login monitoring system by:
1. Querying real user login history from `login_logs` table
2. Using trained K-Means model for anomaly detection
3. Providing accurate risk scores for security decisions
4. Supporting both real and test scenarios

---

**Last Updated**: April 8, 2026  
**Version**: 2.0 (Advanced ML Integration)
