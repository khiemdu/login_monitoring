#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
LOGIN_MONITORING - Advanced KMeans Clustering for Login Behavior Analysis

Features:
- Generate realistic dataset (300+ rows) with behavioral patterns
- Categorical to numeric conversion
- Feature scaling
- K-Means model training (k=3) 
- Intelligent cluster mapping to risk levels (Normal/Suspicious/High Risk)
- Risk prediction function for PHP integration
- JSON output support
- Comprehensive logging and validation

Author: AI Security System
Date: 2026
"""

import pandas as pd
import numpy as np
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler, LabelEncoder
import joblib
import os
from datetime import datetime
import warnings
import json
warnings.filterwarnings('ignore')

# ===== CONFIGURATION =====
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DATASET_FILE = os.path.join(SCRIPT_DIR, 'login_dataset.xlsx')
MODEL_FILE = os.path.join(SCRIPT_DIR, 'kmeans_model.pkl')
SCALER_FILE = os.path.join(SCRIPT_DIR, 'scaler_model.pkl')
ENCODERS_FILE = os.path.join(SCRIPT_DIR, 'label_encoders.pkl')
CLUSTER_MAP_FILE = os.path.join(SCRIPT_DIR, 'cluster_mapping.pkl')
LOG_FILE = os.path.join(SCRIPT_DIR, 'training_log.txt')

# Dataset parameters
DATASET_SIZE = 350
KMEANS_CLUSTERS = 3
RANDOM_STATE = 42

# Risk mapping constants
RISK_LEVELS = {
    'normal': 'Normal',
    'suspicious': 'Suspicious', 
    'high_risk': 'High Risk'
}

# Global variables for prediction
_kmeans_model = None
_scaler_model = None
_encoders = None
_cluster_mapping = None

# ===== LOGGING SETUP =====
def log_message(msg, level="INFO"):
    """Log message to both console and file"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_entry = f"[{timestamp}] [{level}] {msg}"
    print(log_entry)
    with open(LOG_FILE, 'a', encoding='utf-8') as f:
        f.write(log_entry + "\n")

# ===== REALISTIC DATASET GENERATION =====
def generate_realistic_dataset(size=350):
    """
    Generate realistic login behavior dataset with three distinct behavioral patterns:
    - Normal behavior: daytime login, low login_count, same location, few failures
    - Suspicious behavior: night login, medium login_count, some failures
    - High Risk behavior: late night (1-4 AM), high login_count, many failures, foreign location
    
    Parameters:
    -----------
    size : int
        Number of rows to generate (default: 350)
    
    Returns:
    --------
    pd.DataFrame
        Dataset with behavioral patterns
    """
    np.random.seed(RANDOM_STATE)
    
    print("\n" + "="*80)
    print("[SETUP] GENERATING REALISTIC DATASET WITH BEHAVIORAL PATTERNS")
    print("="*80)
    
    log_message("Starting realistic dataset generation...", "INFO")
    
    device_types = ['Mobile', 'PC', 'Tablet']
    locations = ['Vietnam', 'USA', 'Japan', 'Korea', 'UK']
    
    data_list = []
    
    # Distribute data into three behavioral categories
    normal_count = int(size * 0.50)      # 50% Normal behavior
    suspicious_count = int(size * 0.35)  # 35% Suspicious behavior
    high_risk_count = size - normal_count - suspicious_count  # 15% High Risk
    
    # ===== NORMAL BEHAVIOR =====
    for _ in range(normal_count):
        data_list.append({
            'device_type': np.random.choice(device_types),
            'location': np.random.choice(['Vietnam', 'Korea'], p=[0.7, 0.3]),  # Mostly local
            'login_hour': np.random.choice(list(range(6, 22))),  # Daytime (6 AM - 10 PM)
            'login_count': np.random.randint(1, 4),  # Low login count
            'failed_attempts': np.random.choice([0, 0, 0, 1], p=[0.7, 0.2, 0.05, 0.05])  # Few failures
        })
    
    # ===== SUSPICIOUS BEHAVIOR =====
    for _ in range(suspicious_count):
        data_list.append({
            'device_type': np.random.choice(device_types),
            'location': np.random.choice(locations),  # Mixed locations
            'login_hour': np.random.choice(list(range(20, 24)) + list(range(0, 6))),  # Night (8 PM - 6 AM)
            'login_count': np.random.randint(4, 8),  # Medium login count
            'failed_attempts': np.random.randint(1, 4)  # Some failures
        })
    
    # ===== HIGH RISK BEHAVIOR =====
    for _ in range(high_risk_count):
        data_list.append({
            'device_type': np.random.choice(device_types),
            'location': np.random.choice(['USA', 'Japan', 'UK'], p=[0.5, 0.3, 0.2]),  # Foreign locations
            'login_hour': np.random.choice([1, 2, 3, 4]),  # Late night (1-4 AM)
            'login_count': np.random.randint(8, 11),  # High login count
            'failed_attempts': np.random.randint(3, 6)  # Many failures
        })
    
    df = pd.DataFrame(data_list)
    df = df.sample(frac=1, random_state=RANDOM_STATE).reset_index(drop=True)  # Shuffle
    
    print(f"\n[OK] Dataset generated: {size} rows")
    print(f"   * Normal behavior: {normal_count} rows ({(normal_count/size*100):.1f}%)")
    print(f"   * Suspicious behavior: {suspicious_count} rows ({(suspicious_count/size*100):.1f}%)")
    print(f"   * High Risk behavior: {high_risk_count} rows ({(high_risk_count/size*100):.1f}%)")
    print(f"\n   Columns: {', '.join(df.columns.tolist())}")
    print(f"   * Device types: {', '.join(device_types)}")
    print(f"   * Locations: {', '.join(locations)}")
    
    log_message(f"Realistic dataset generated: {size} rows", "INFO")
    
    return df

# ===== DATA ENCODING =====
def encode_categorical_data(df):
    """
    Convert categorical variables to numeric values
    
    Parameters:
    -----------
    df : pd.DataFrame
        Dataset with categorical columns
    
    Returns:
    --------
    pd.DataFrame
        Dataset with encoded columns
    dict
        Dictionary of label encoders for future use
    """
    print("\n" + "="*80)
    print("[ENCODE] ENCODING CATEGORICAL DATA")
    print("="*80)
    
    log_message("Starting data encoding...", "INFO")
    
    df_encoded = df.copy()
    encoders = {}
    
    categorical_columns = ['device_type', 'location']
    
    for col in categorical_columns:
        le = LabelEncoder()
        df_encoded[col] = le.fit_transform(df[col])
        encoders[col] = le
        
        print(f"\n[OK] Encoded '{col}':")
        for i, label in enumerate(le.classes_):
            print(f"   • {label} -> {i}")
        
        log_message(f"Encoded column '{col}': {dict(zip(le.classes_, range(len(le.classes_))))}", "INFO")
    
    return df_encoded, encoders

# ===== FEATURE SCALING =====
def scale_features(df_encoded):
    """
    Scale numeric features using StandardScaler
    Ensures all features contribute equally to clustering
    
    Parameters:
    -----------
    df_encoded : pd.DataFrame
        Encoded dataset
    
    Returns:
    --------
    np.ndarray
        Scaled feature matrix
    StandardScaler
        Fitted scaler object
    """
    print("\n" + "="*80)
    print("[SCALE] SCALING FEATURES")
    print("="*80)
    
    log_message("Starting feature scaling...", "INFO")
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(df_encoded)
    
    print(f"\n[OK] Features scaled using StandardScaler")
    print(f"   * Scaled features shape: {X_scaled.shape}")
    print(f"   * Feature means (should be ~0): {scaler.mean_.round(3)}")
    print(f"   * Feature stds (should be ~1): {scaler.scale_.round(3)}")
    
    log_message("Feature scaling completed", "INFO")
    
    return X_scaled, scaler

# ===== INTELLIGENT CLUSTER MAPPING =====
def map_clusters_to_risk(kmeans, X_scaled, df_encoded):
    """
    Intelligently map clusters to risk levels based on cluster characteristics.
    Analysis is based on average feature values per cluster:
    - High login_count + high failed_attempts + late night = High Risk
    - Medium values, night hours = Suspicious
    - Low values, daytime = Normal
    
    Parameters:
    -----------
    kmeans : KMeans
        Trained KMeans model
    X_scaled : np.ndarray
        Scaled feature matrix
    df_encoded : pd.DataFrame
        Encoded dataset with original values visible
    
    Returns:
    --------
    dict
        Mapping of cluster IDs to risk levels
    """
    print("\n" + "="*80)
    print("[CLUSTER] INTELLIGENT CLUSTER MAPPING")
    print("="*80)
    
    log_message("Starting intelligent cluster mapping...", "INFO")
    
    clusters = kmeans.predict(X_scaled)
    
    # Analyze cluster characteristics
    cluster_analysis = {}
    
    for cluster_id in range(KMEANS_CLUSTERS):
        mask = clusters == cluster_id
        
        avg_login_count = df_encoded.loc[mask, 'login_count'].mean()
        avg_failed_attempts = df_encoded.loc[mask, 'failed_attempts'].mean()
        avg_login_hour = df_encoded.loc[mask, 'login_hour'].mean()
        
        cluster_analysis[cluster_id] = {
            'avg_login_count': avg_login_count,
            'avg_failed_attempts': avg_failed_attempts,
            'avg_login_hour': avg_login_hour,
            'size': mask.sum()
        }
        
        print(f"\n   Cluster {cluster_id}:")
        print(f"   * Size: {mask.sum()}")
        print(f"   * Avg login_count: {avg_login_count:.2f}")
        print(f"   * Avg failed_attempts: {avg_failed_attempts:.2f}")
        print(f"   * Avg login_hour: {avg_login_hour:.2f}")
    
    # Create mapping based on characteristics
    cluster_risk_map = {}
    
    # Sort clusters by risk score
    risk_scores = {}
    for cluster_id, stats in cluster_analysis.items():
        # Higher score = higher risk
        # Risk = login_count + failed_attempts + (night hours penalty)
        night_penalty = 1.0 if (stats['avg_login_hour'] < 6 or stats['avg_login_hour'] > 22) else 0.0
        risk_score = (stats['avg_login_count'] * 0.4 + 
                     stats['avg_failed_attempts'] * 0.4 + 
                     night_penalty * 0.2)
        risk_scores[cluster_id] = risk_score
    
    # Map clusters: lowest score = Normal, middle = Suspicious, highest = High Risk
    sorted_clusters = sorted(risk_scores.items(), key=lambda x: x[1])
    
    cluster_risk_map[sorted_clusters[0][0]] = RISK_LEVELS['normal']
    cluster_risk_map[sorted_clusters[1][0]] = RISK_LEVELS['suspicious']
    cluster_risk_map[sorted_clusters[2][0]] = RISK_LEVELS['high_risk']
    
    print(f"\n[OK] Cluster mapping completed:")
    for cluster_id, risk_level in cluster_risk_map.items():
        print(f"   * Cluster {cluster_id} -> {risk_level}")
    
    log_message(f"Cluster mapping: {cluster_risk_map}", "INFO")
    
    return cluster_risk_map

# ===== KMEANS TRAINING =====
def train_kmeans_model(X_scaled, n_clusters=3):
    """
    Train K-Means clustering model
    
    Parameters:
    -----------
    X_scaled : np.ndarray
        Scaled feature matrix
    n_clusters : int
        Number of clusters (default: 3)
    
    Returns:
    --------
    KMeans
        Trained KMeans model
    """
    print("\n" + "="*80)
    print(f"[TRAIN] TRAINING KMEANS MODEL (k={n_clusters})")
    print("="*80)
    
    log_message(f"Starting KMeans training with k={n_clusters}...", "INFO")
    
    kmeans = KMeans(
        n_clusters=n_clusters,
        random_state=RANDOM_STATE,
        n_init=10,
        max_iter=300
    )
    
    kmeans.fit(X_scaled)
    
    print(f"\n[OK] KMeans model trained")
    print(f"   * Number of clusters: {n_clusters}")
    print(f"   * Inertia (within-cluster sum of squares): {kmeans.inertia_:.2f}")
    print(f"   * Number of iterations: {kmeans.n_iter_}")
    
    # Cluster distribution
    unique, counts = np.unique(kmeans.labels_, return_counts=True)
    print(f"\n   Cluster distribution:")
    for cluster_id, count in zip(unique, counts):
        print(f"   * Cluster {cluster_id}: {count} samples ({(count/len(kmeans.labels_)*100):.1f}%)")
    
    log_message(f"KMeans model trained successfully. Inertia: {kmeans.inertia_:.2f}", "INFO")
    
    return kmeans

# ===== MODEL PERSISTENCE =====
def save_artifacts(kmeans, scaler, encoders, cluster_mapping, df_dataset):
    """
    Save trained model, scaler, encoders, cluster mapping, and dataset
    
    Parameters:
    -----------
    kmeans : KMeans
        Trained KMeans model
    scaler : StandardScaler
        Fitted scaler
    encoders : dict
        Label encoders for categorical variables
    cluster_mapping : dict
        Mapping of cluster IDs to risk levels
    df_dataset : pd.DataFrame
        Final dataset with predictions
    """
    print("\n" + "="*80)
    print("[SAVE] SAVING ARTIFACTS")
    print("="*80)
    
    # Save model
    joblib.dump(kmeans, MODEL_FILE)
    print(f"\n[OK] Model saved: {MODEL_FILE}")
    log_message(f"Model saved to {MODEL_FILE}", "INFO")
    
    # Save scaler
    joblib.dump(scaler, SCALER_FILE)
    print(f"[OK] Scaler saved: {SCALER_FILE}")
    log_message(f"Scaler saved to {SCALER_FILE}", "INFO")
    
    # Save encoders
    joblib.dump(encoders, ENCODERS_FILE)
    print(f"[OK] Encoders saved: {ENCODERS_FILE}")
    log_message(f"Encoders saved to {ENCODERS_FILE}", "INFO")
    
    # Save cluster mapping
    joblib.dump(cluster_mapping, CLUSTER_MAP_FILE)
    print(f"[OK] Cluster mapping saved: {CLUSTER_MAP_FILE}")
    log_message(f"Cluster mapping saved to {CLUSTER_MAP_FILE}", "INFO")
    
    # Save dataset
    df_dataset.to_csv(DATASET_FILE, index=False)
    print(f"[OK] Dataset saved: {DATASET_FILE}")
    log_message(f"Dataset saved to {DATASET_FILE}", "INFO")

# ===== RESULTS DISPLAY =====
def display_results(df_final):
    """
    Display first 10 rows of final dataset
    
    Parameters:
    -----------
    df_final : pd.DataFrame
        Final dataset with predictions
    """
    print("\n" + "="*80)
    print("[DATA] FIRST 10 ROWS OF FINAL DATASET")
    print("="*80)
    
    print("\n", df_final.head(10).to_string(index=True))
    
    print("\n" + "="*80)
    print("[SUMMARY] DATASET SUMMARY")
    print("="*80)
    print(f"\nTotal rows: {len(df_final)}")
    print(f"Columns: {', '.join(df_final.columns.tolist())}")
    print(f"\nRisk level distribution:")
    risk_dist = df_final['risk_level'].value_counts()
    for risk, count in risk_dist.items():
        print(f"   * {risk}: {count} ({(count/len(df_final)*100):.1f}%)")
    
    log_message(f"Results displayed. Total processed: {len(df_final)} rows", "INFO")

# ===== PREDICTION FUNCTION FOR PHP INTEGRATION =====
def predict_risk(device_type, location, login_hour, login_count, failed_attempts):
    """
    Predict risk level for a login attempt
    Supports JSON output for PHP integration
    
    Parameters:
    -----------
    device_type : str
        Type of device (Mobile, PC, Tablet)
    location : str
        Location (Vietnam, USA, Japan, Korea, UK)
    login_hour : int
        Hour of login (0-23)
    login_count : int
        Number of login attempts (1-10)
    failed_attempts : int
        Number of failed attempts (0-5)
    
    Returns:
    --------
    dict
        Risk prediction with risk_level and confidence
    """
    global _kmeans_model, _scaler_model, _encoders, _cluster_mapping
    
    # Load models if not already loaded
    if _kmeans_model is None:
        _kmeans_model = joblib.load(MODEL_FILE)
        _scaler_model = joblib.load(SCALER_FILE)
        _encoders = joblib.load(ENCODERS_FILE)
        _cluster_mapping = joblib.load(CLUSTER_MAP_FILE)
    
    # Encode categorical variables
    device_encoded = _encoders['device_type'].transform([device_type])[0]
    location_encoded = _encoders['location'].transform([location])[0]
    
    # Create feature vector
    features = np.array([[device_encoded, location_encoded, login_hour, login_count, failed_attempts]])
    
    # Scale features
    features_scaled = _scaler_model.transform(features)
    
    # Predict cluster
    cluster = _kmeans_model.predict(features_scaled)[0]
    
    # Get risk level
    risk_level = _cluster_mapping.get(cluster, 'Unknown')
    
    # Calculate confidence based on distance to cluster center
    distances = np.linalg.norm(features_scaled - _kmeans_model.cluster_centers_, axis=1)
    confidence = 1.0 / (1.0 + distances[cluster])
    
    result = {
        'risk_level': risk_level,
        'cluster': int(cluster),
        'confidence': round(float(confidence), 3),
        'device_type': device_type,
        'location': location,
        'login_hour': login_hour,
        'login_count': login_count,
        'failed_attempts': failed_attempts
    }
    
    return result

def predict_risk_json(device_type, location, login_hour, login_count, failed_attempts):
    """
    Predict risk level and return as JSON for PHP
    
    Returns:
    --------
    str
        JSON formatted risk prediction
    """
    result = predict_risk(device_type, location, login_hour, login_count, failed_attempts)
    return json.dumps(result)

# ===== MAIN EXECUTION =====
def main():
    """Main execution pipeline"""
    try:
        print("\n\n")
        print("=" * 80)
        print("=" + " " * 78 + "=")
        print("=" + " " * 20 + "LOGIN MONITORING - KMEANS TRAINING" + " " * 24 + "=")
        print("=" + " " * 78 + "=")
        print("=" * 80)
        
        log_message("="*80, "INFO")
        log_message("Starting KMeans training pipeline", "INFO")
        log_message("="*80, "INFO")
        
        # Step 1: Generate dataset
        df = generate_realistic_dataset(DATASET_SIZE)
        
        # Step 2: Encode categorical data
        df_encoded, encoders = encode_categorical_data(df)
        
        # Step 3: Scale features
        X_scaled, scaler = scale_features(df_encoded)
        
        # Step 4: Train KMeans model
        kmeans = train_kmeans_model(X_scaled, KMEANS_CLUSTERS)
        
        # Step 5: Map clusters to risk levels
        cluster_mapping = map_clusters_to_risk(kmeans, X_scaled, df_encoded)
        
        # Step 6: Get cluster predictions
        clusters = kmeans.predict(X_scaled)
        
        # Step 7: Map to risk levels
        risk_levels = np.array([cluster_mapping[c] for c in clusters])
        
        # Step 8: Create final dataset
        df_final = df.copy()
        df_final['cluster'] = clusters
        df_final['risk_level'] = risk_levels
        
        # Step 9: Save artifacts
        save_artifacts(kmeans, scaler, encoders, cluster_mapping, df_final)
        
        # Step 10: Display results
        display_results(df_final)
        
        # Step 11: Test predict_risk function
        print("\n" + "="*80)
        print("[TEST] TESTING PREDICT_RISK FUNCTION (FOR PHP INTEGRATION)")
        print("="*80)
        
        # Test cases
        test_cases = [
            ("Mobile", "Vietnam", 10, 1, 0),          # Normal - daytime, low counts
            ("PC", "USA", 23, 10, 5),                  # High Risk - late night, high counts
            ("Tablet", "Korea", 3, 7, 3),             # High Risk - very late night, high counts
            ("Mobile", "Korea", 14, 2, 0),            # Normal - afternoon, low counts
            ("PC", "Japan", 22, 6, 2),                # Suspicious - night, medium counts
        ]
        
        print("\n   Sample predictions for PHP integration:")
        for i, (dev, loc, hour, cnt, fail) in enumerate(test_cases, 1):
            result = predict_risk(dev, loc, hour, cnt, fail)
            print(f"\n   Test {i}:")
            print(f"   Input: device={dev}, location={loc}, hour={hour}, count={cnt}, failed={fail}")
            print(f"   → Risk Level: {result['risk_level']} (confidence: {result['confidence']})")
        
        print("\n" + "="*80)
        print("[OK] PIPELINE COMPLETED SUCCESSFULLY")
        print("="*80)
        print(f"\n[FILES] Saved files:")
        print(f"   * Dataset: {DATASET_FILE}")
        print(f"   * Model: {MODEL_FILE}")
        print(f"   * Scaler: {SCALER_FILE}")
        print(f"   * Encoders: {ENCODERS_FILE}")
        print(f"   * Mapping: {CLUSTER_MAP_FILE}")
        print(f"   * Logs: {LOG_FILE}\n")
        
        log_message("Pipeline completed successfully", "INFO")
        log_message("="*80, "INFO")
        
    except Exception as e:
        error_msg = f"ERROR: {str(e)}"
        print(f"\n[ERROR] {error_msg}\n")
        log_message(error_msg, "ERROR")
        raise

if __name__ == "__main__":
    main()
