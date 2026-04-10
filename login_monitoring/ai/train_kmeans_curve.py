import pymysql
import pandas as pd
import numpy as np
import json
from datetime import datetime, timedelta
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler, RobustScaler
from sklearn.metrics import silhouette_score, davies_bouldin_score, calinski_harabasz_score
import warnings
warnings.filterwarnings('ignore')

print("=" * 80)
print("[TRAIN] K-MEANS WITH ACCURACY CURVE TRACKING")
print("=" * 80)

# ===== DB CONNECTION =====
print("\n[1/6] Connecting to database...")
conn = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="login_monitoring"
)
print("[OK] Database connected")

# ===== LOAD DATA =====
print("\n[2/6] Loading login data...")
df = pd.read_sql("""
    SELECT id, username, status, device_type, country, login_time
    FROM login_logs
    WHERE device_type IS NOT NULL
      AND country IS NOT NULL
      AND login_time IS NOT NULL
    ORDER BY RAND()
""", conn)

if df.empty:
    print("[ERROR] No data for AI training")
    exit()

print(f"[OK] Loaded {len(df)} login records")

# ===== PREPARE DATA =====
print("\n[3/6] Preparing features with advanced engineering...")
df['login_time'] = pd.to_datetime(df['login_time'])
df['login_hour'] = df['login_time'].dt.hour
df['login_day'] = df['login_time'].dt.dayofweek
df['login_month'] = df['login_time'].dt.month
df['status_encoded'] = (df['status'].str.upper() == 'FAIL').astype(int)
df['device_encoded'] = pd.factorize(df['device_type'])[0]
df['country_encoded'] = pd.factorize(df['country'])[0]

# Add frequency features (how many times this user logged in)
user_login_count = df.groupby('username').size().reset_index(name='user_login_freq')
df = df.merge(user_login_count, on='username', how='left')

# Add risk factors
df['is_night_login'] = ((df['login_hour'] >= 0) & (df['login_hour'] <= 6)).astype(int)
df['is_failed'] = df['status_encoded']

# Select features - expanded set
features_cols = ['login_hour', 'login_day', 'login_month', 'status_encoded', 
                 'device_encoded', 'country_encoded', 'user_login_freq',
                 'is_night_login', 'is_failed']
features = df[features_cols].fillna(0)

# Apply RobustScaler for better handling of outliers
scaler = RobustScaler()
features_scaled = scaler.fit_transform(features)
print(f"[OK] {features.shape[0]} samples with {features.shape[1]} features (advanced)")
print(f"[INFO] Features: {', '.join(features_cols)}")

# ===== SPLIT DATA 80/20 =====
print("\n[4/6] Splitting train/validation data...")
split_idx = int(len(features_scaled) * 0.8)
df_train = df[:split_idx].reset_index(drop=True)
df_val = df[split_idx:].reset_index(drop=True)
X_train = features_scaled[:split_idx]
X_val = features_scaled[split_idx:]
print(f"[OK] Train: {len(df_train)}, Validation: {len(df_val)}")

# ===== TRAIN K-MEANS WITH ACCURACY TRACKING =====
print("\n[5/6] Training K-Means model with accuracy tracking...")

accuracy_curve = {
    'train_samples': [],
    'train_accuracy': [],
    'val_accuracy': []
}

# Train model with optimized parameters
kmeans = KMeans(n_clusters=3, random_state=42, n_init=100, max_iter=5000, tol=1e-4)
kmeans.fit(X_train)

# Get cluster predictions
train_clusters = kmeans.predict(X_train)
val_clusters = kmeans.predict(X_val)

# Calculate accuracy by sampling different data sizes
sample_sizes = []
step = max(1, len(X_train) // 30)  # Create 30 checkpoints for smoother curve
for i in range(step, len(X_train) + 1, step):
    sample_sizes.append(i)

# Always include full dataset
if sample_sizes[-1] != len(X_train):
    sample_sizes.append(len(X_train))

print(f"[INFO] Evaluating at {len(sample_sizes)} checkpoints...\n")

# Function to calculate expected accuracy with multiple metrics
def calculate_accuracy_score(X_data, clusters, kmeans_model):
    """Calculate accuracy using multiple metrics"""
    if len(np.unique(clusters)) < 2:
        return 0.5
    
    try:
        # Method 1: Silhouette Score (normalized to 0-1)
        sil = silhouette_score(X_data, clusters)
        sil_acc = (sil + 1) / 2
        
        # Method 2: Davies-Bouldin Index (inverse normalized)
        db = davies_bouldin_score(X_data, clusters)
        db_acc = 1 / (1 + db)  # Convert to 0-1 scale
        
        # Method 3: Calinski-Harabasz Index (normalized)
        ch = calinski_harabasz_score(X_data, clusters)
        ch_acc = min(ch / 500, 1.0)  # Normalize to 0-1
        
        # Combined accuracy (weighted average)
        combined_acc = (sil_acc * 0.5 + db_acc * 0.3 + ch_acc * 0.2)
        combined_acc = min(max(combined_acc, 0), 1)
        
        return combined_acc
    except:
        return 0.5

for sample_size in sample_sizes:
    # Train on partial data
    X_partial = X_train[:sample_size]
    kmeans_partial = KMeans(n_clusters=3, random_state=42, n_init=100, max_iter=5000, tol=1e-4)
    train_pred_partial = kmeans_partial.fit_predict(X_partial)
    
    # Calculate combined accuracy metric
    if sample_size > 3:
        train_acc = calculate_accuracy_score(X_partial, train_pred_partial, kmeans_partial)
    else:
        train_acc = 0.5
    
    # Validation accuracy on full validation set with same model
    try:
        val_pred = kmeans_partial.predict(X_val)
        val_acc = calculate_accuracy_score(X_val, val_pred, kmeans_partial)
    except:
        val_acc = 0.5
    
    accuracy_curve['train_samples'].append(sample_size)
    accuracy_curve['train_accuracy'].append(round(train_acc, 3))
    accuracy_curve['val_accuracy'].append(round(val_acc, 3))
    
    print(f"  Sample {sample_size:5d}: Train={train_acc:.3f}, Val={val_acc:.3f}")

print("[OK] Accuracy curve computed")

# ===== CALCULATE FINAL METRICS =====
print("\n[6/6] Calculating final metrics...")

# Train and validation clusters
train_clusters = kmeans.predict(X_train)
val_clusters = kmeans.predict(X_val)

# Calculate individual metrics
silhouette_train = silhouette_score(X_train, train_clusters)
davies_bouldin = davies_bouldin_score(X_train, train_clusters)
calinski_harabasz = calinski_harabasz_score(X_train, train_clusters)

# Calculate final accuracy using combined metrics
final_accuracy = calculate_accuracy_score(X_train, train_clusters, kmeans)

print(f"  Final Accuracy: {final_accuracy*100:.2f}%")
print(f"  Silhouette: {silhouette_train:.4f}")
print(f"  Davies-Bouldin: {davies_bouldin:.4f}")
print(f"  Calinski-Harabasz: {calinski_harabasz:.4f}")

# ===== CALCULATE RISK & SAVE =====
print("\n[SAVE] Saving cluster assignments...")

def calculate_risk_percentage(row, kmeans_model, scaler_obj):
    """Calculate risk percentage based on cluster distance"""
    status = row['status'].upper()
    login_hour = row['login_hour']
    
    row_features = np.array([
        row['login_hour'], 
        row['status_encoded'], 
        row['device_encoded'], 
        row['country_encoded'], 
        row['login_day']
    ]).reshape(1, -1)
    row_features_scaled = scaler_obj.transform(row_features)
    
    cluster_id = kmeans.predict(row_features_scaled)[0]
    center = kmeans_model.cluster_centers_[cluster_id]
    distance = np.linalg.norm(row_features_scaled[0] - center)
    distance_percentage = min(distance * 20, 40)
    
    risk_percentage = distance_percentage
    
    if status == 'FAIL':
        risk_percentage += 30
    
    if login_hour >= 0 and login_hour <= 5:
        risk_percentage += 15
    
    risk_percentage = min(max(risk_percentage, 0), 100)
    return round(risk_percentage, 2)

# Apply risk calculation to all data
df['ai_cluster'] = kmeans.predict(features_scaled)
df['risk_percentage'] = df.apply(
    lambda row: calculate_risk_percentage(row, kmeans, scaler),
    axis=1
)

# Save to database
cur = conn.cursor()
saved_count = 0
for idx, row in df.iterrows():
    try:
        cur.execute(
            "UPDATE login_logs SET ai_cluster=%s, risk_percentage=%s WHERE id=%s",
            (int(row['ai_cluster']), float(row['risk_percentage']), int(row['id']))
        )
        saved_count += 1
    except:
        pass

conn.commit()
print(f"[OK] Updated {saved_count} records")

# ===== SMOOTH ACCURACY CURVE WITH MOVING AVERAGE =====
def smooth_curve(values, window=3):
    """Apply moving average smoothing"""
    if len(values) < window:
        return values
    smoothed = []
    for i in range(len(values)):
        start = max(0, i - window // 2)
        end = min(len(values), i + window // 2 + 1)
        smoothed.append(np.mean(values[start:end]))
    return smoothed

accuracy_curve['train_accuracy'] = [round(x, 3) for x in smooth_curve(accuracy_curve['train_accuracy'], window=3)]
accuracy_curve['val_accuracy'] = [round(x, 3) for x in smooth_curve(accuracy_curve['val_accuracy'], window=3)]
print("[OK] Accuracy curve smoothed with moving average")

# ===== SAVE ACCURACY CURVE TO FILE =====
curve_file = __file__.replace('train_kmeans_curve.py', '') + 'accuracy_curve.json'
with open(curve_file, 'w') as f:
    json.dump(accuracy_curve, f, indent=2)
print(f"[OK] Accuracy curve saved to {curve_file}")

# ===== SAVE TRAINING METRICS TO FILE =====
metrics_file = __file__.replace('train_kmeans_curve.py', '') + 'training_metrics.json'
final_train_acc = accuracy_curve['train_accuracy'][-1] if accuracy_curve['train_accuracy'] else 0.5
final_val_acc = accuracy_curve['val_accuracy'][-1] if accuracy_curve['val_accuracy'] else 0.5
combined_accuracy = (final_train_acc + final_val_acc) / 2

metrics_data = {
    'accuracy': round(combined_accuracy * 100, 2),
    'silhouette': round(silhouette_train, 4),
    'davies_bouldin': round(davies_bouldin, 4),
    'calinski_harabasz': round(calinski_harabasz, 2),
    'train_accuracy': round(final_train_acc * 100, 2),
    'val_accuracy': round(final_val_acc * 100, 2),
    'total_samples': len(df)
}

with open(metrics_file, 'w') as f:
    json.dump(metrics_data, f, indent=2)
print(f"[OK] Training metrics saved to {metrics_file}")

# ===== OUTPUT SUMMARY =====
print("\n" + "=" * 80)
print("[SUCCESS] TRAINING COMPLETED SUCCESSFULLY")
print("=" * 80)
print(f"Total records: {len(df)}")
print(f"Clusters: 3")
print(f"Advanced features: 9 (with user frequency and risk factors)")
print(f"Accuracy Curve points: {len(accuracy_curve['train_samples'])}")
print(f"\n[METRICS]")
print(f"Final Train Accuracy: {accuracy_curve['train_accuracy'][-1]*100:.2f}%")
print(f"Final Val Accuracy: {accuracy_curve['val_accuracy'][-1]*100:.2f}%")
print(f"Combined Accuracy: {combined_accuracy*100:.2f}%")
print(f"Silhouette Score: {silhouette_train:.4f}")
print(f"Davies-Bouldin Index: {davies_bouldin:.4f}")
print(f"Calinski-Harabasz Index: {calinski_harabasz:.2f}")
print("=" * 80)

conn.close()
