#!/usr/bin/env python3
"""
Advanced Risk Calculation API using K-Means Model
Integrates ML anomaly detection with heuristic rules
"""

import sys
import json
import pymysql
import pickle
import numpy as np
from datetime import datetime, timedelta
from sklearn.preprocessing import StandardScaler
import warnings
warnings.filterwarnings('ignore')

# ===== DATABASE CONNECTION =====
def get_db_connection():
    try:
        conn = pymysql.connect(
            host="localhost",
            user="root",
            password="",
            database="login_monitoring"
        )
        return conn
    except Exception as e:
        return None

# ===== LOAD K-MEANS MODEL =====
def load_kmeans_model():
    try:
        with open('kmeans_model.pkl', 'rb') as f:
            model_data = pickle.load(f)
        return model_data
    except:
        return None

# ===== FEATURE ENCODING =====
def encode_features(device_type, country, login_hour, status):
    """Encode categorical features for K-Means"""
    
    # Device encoding
    device_map = {
        'Desktop': 0,
        'Mobile': 1,
        'Tablet': 2,
        'Laptop': 3
    }
    device_encoded = device_map.get(device_type, 0)
    
    # Country encoding (simplified - in production use actual mapping)
    country_map = {
        'Vietnam': 0,
        'Thailand': 1,
        'Singapore': 2,
        'Unknown': 3
    }
    country_encoded = country_map.get(country, 3)
    
    # Status encoding
    status_encoded = 1 if status.upper() == 'FAIL' else 0
    
    # Login day (0 = Monday, 6 = Sunday)
    login_date = datetime.fromisoformat(datetime.now().isoformat().split('.')[0])
    login_day = login_date.weekday()
    
    return np.array([
        login_hour,
        status_encoded,
        device_encoded,
        country_encoded,
        login_day
    ])

# ===== CALCULATE ANOMALY SCORE =====
def calculate_anomaly_score(features_normalized, kmeans_model):
    """Calculate anomaly score based on distance to nearest cluster"""
    
    try:
        kmeans = kmeans_model['model']
        scaler = kmeans_model['scaler']
        
        # Find distance to nearest cluster
        distances = kmeans.transform([features_normalized])
        min_distance = np.min(distances[0])
        max_distance = np.max(distances[0])
        
        # Normalize to 0-40% (anomaly score)
        # This represents how far from known patterns
        anomaly_pct = min(40, (min_distance / (max_distance + 0.001)) * 100)
        
        return anomaly_pct
    except:
        return 0

# ===== CALCULATE RISK PERCENTAGE =====
def calculate_risk_percentage(data):
    """
    Calculate comprehensive risk percentage (0-100%)
    
    Factors:
    - Anomaly Score: 0-40% (ML-based distance from normal clusters)
    - Login Status: 0-20% (5% success, 25% fail, considers history)
    - Device Type: 0-15% (Mobile/Tablet riskier)
    - Country: 0-10% (Unknown location = higher risk)
    - Login Time: 0-10% (Off-hours = higher risk)
    - User Behavior: 0-15% (Failed attempts pattern)
    """
    
    conn = get_db_connection()
    if not conn:
        return None
    
    username = data.get('username', 'test_user')
    device_type = data.get('device_type', 'Desktop')
    country = data.get('country', 'Unknown')
    login_time = data.get('login_time', datetime.now().isoformat())
    status = data.get('status', 'SUCCESS')
    
    try:
        # Parse login time
        login_dt = datetime.fromisoformat(login_time)
        login_hour = login_dt.hour
        
        risk_score = 0
        breakdown = {}
        
        # ===== 1. ANOMALY SCORE (0-40%) - ML-Based =====
        features = encode_features(device_type, country, login_hour, status)
        kmeans_model = load_kmeans_model()
        
        if kmeans_model:
            # Normalize features using stored scaler
            scaler = kmeans_model.get('scaler')
            if scaler:
                features_normalized = scaler.transform([features])[0]
                anomaly = calculate_anomaly_score(features_normalized, kmeans_model)
            else:
                anomaly = 0
        else:
            anomaly = 0
        
        risk_score += anomaly
        breakdown['anomaly_score'] = round(anomaly, 2)
        breakdown['anomaly_reason'] = 'Unusual pattern detected' if anomaly > 20 else 'Normal pattern'
        
        # ===== 2. LOGIN STATUS (0-25%) =====
        # Get database connection again for queries
        cursor = conn.cursor()
        esc_user = username.replace("'", "\\'")
        
        try:
            # Check failed attempts in last 24h
            cursor.execute(
                f"SELECT COUNT(*) as cnt FROM login_logs WHERE username='{esc_user}' AND status='FAIL' AND login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )
            result = cursor.fetchone()
            fail_count_24h = result[0] if result else 0
        except:
            fail_count_24h = 0
        
        if status.upper() == 'FAIL':
            status_risk = 20 + (fail_count_24h * 1.5)  # Base 20% + cumulative
            status_risk = min(status_risk, 25)  # Cap at 25%
            status_reason = f'{fail_count_24h} failed attempts in 24h'
        else:
            status_risk = 2 + (fail_count_24h * 0.5)  # Lower risk if success
            status_risk = min(status_risk, 10)
            status_reason = 'Successful login'
        
        risk_score += status_risk
        breakdown['status_risk'] = round(status_risk, 2)
        breakdown['status_reason'] = status_reason
        breakdown['failed_24h'] = fail_count_24h
        
        # ===== 3. DEVICE TYPE (0-15%) =====
        device_risk = 0
        device_reason = 'Desktop (normal)'
        
        if device_type in ['Mobile', 'Tablet']:
            device_risk = 12
            device_reason = f'{device_type} (higher risk)'
        elif device_type == 'Laptop':
            device_risk = 5
            device_reason = 'Laptop (moderate)'
        
        risk_score += device_risk
        breakdown['device_risk'] = round(device_risk, 2)
        breakdown['device_reason'] = device_reason
        
        # ===== 4. COUNTRY (0-10%) =====
        country_risk = 0
        country_reason = f'{country} (known location)'
        
        if country == 'Unknown':
            country_risk = 10
            country_reason = 'Unknown location (high risk)'
        elif country not in ['Vietnam', 'Thailand', 'Singapore']:
            country_risk = 5
            country_reason = f'{country} (unusual for this user)'
        
        risk_score += country_risk
        breakdown['country_risk'] = round(country_risk, 2)
        breakdown['country_reason'] = country_reason
        
        # ===== 5. LOGIN TIME (0-15%) =====
        time_risk = 0
        time_reason = f'{login_hour}:00 (normal hours)'
        
        # Check for unusual hours
        if login_hour >= 0 and login_hour < 6:
            time_risk = 12
            time_reason = f'{login_hour}:00 (midnight-6am - very unusual)'
        elif login_hour >= 22 or login_hour >= 6 and login_hour < 8:
            time_risk = 8
            time_reason = f'{login_hour}:00 (early morning/late night)'
        elif login_hour >= 9 and login_hour <= 17:
            time_risk = 2
            time_reason = f'{login_hour}:00 (business hours)'
        else:
            time_risk = 5
            time_reason = f'{login_hour}:00 (evening)'
        
        risk_score += time_risk
        breakdown['time_risk'] = round(time_risk, 2)
        breakdown['time_reason'] = time_reason
        breakdown['login_hour'] = login_hour
        
        # ===== 6. BRUTE-FORCE PATTERN (0-15%) =====
        try:
            cursor.execute(
                f"SELECT COUNT(*) as cnt FROM login_logs WHERE username='{esc_user}' AND status='FAIL' AND login_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
            )
            result = cursor.fetchone()
            recent_fails = result[0] if result else 0
        except:
            recent_fails = 0
        
        brute_risk = 0
        brute_reason = 'No brute-force pattern'
        
        if recent_fails >= 5:
            brute_risk = 15
            brute_reason = f'{recent_fails} failed attempts in 15 min - ATTACK PATTERN'
        elif recent_fails >= 3:
            brute_risk = 10
            brute_reason = f'{recent_fails} failed attempts in 15 min'
        elif recent_fails >= 1:
            brute_risk = 5
            brute_reason = f'{recent_fails} failed attempt(s) recently'
        
        risk_score += brute_risk
        breakdown['brute_force_risk'] = round(brute_risk, 2)
        breakdown['brute_force_reason'] = brute_reason
        breakdown['recent_fails'] = recent_fails
        
        # ===== FINAL SCORE =====
        final_risk = min(max(risk_score, 0), 100)
        
        # Determine risk level
        if final_risk <= 25:
            risk_level = '🟢 NORMAL'
            color = 'green'
        elif final_risk <= 50:
            risk_level = '🟡 SUSPICIOUS'
            color = 'yellow'
        elif final_risk <= 75:
            risk_level = '🟠 WARNING'
            color = 'orange'
        else:
            risk_level = '🔴 HIGH RISK'
            color = 'red'
        
        cursor.close()
        conn.close()
        
        return {
            'success': True,
            'risk_percentage': round(final_risk, 1),
            'risk_level': risk_level,
            'risk_color': color,
            'explanation': 'Multiple factors detected affecting login security',
            'breakdown': breakdown,
            'details': {
                'username': username,
                'device': device_type,
                'country': country,
                'login_time': login_time,
                'status': status
            }
        }
        
    except Exception as e:
        conn.close()
        return {
            'success': False,
            'message': f'Error: {str(e)}'
        }

# ===== MAIN EXECUTION =====
if __name__ == '__main__':
    try:
        # Read JSON from stdin
        input_data = json.loads(sys.stdin.read())
        result = calculate_risk_percentage(input_data)
        print(json.dumps(result, indent=2))
    except Exception as e:
        print(json.dumps({
            'success': False,
            'message': f'Error: {str(e)}'
        }))
