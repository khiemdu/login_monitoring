import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import silhouette_score, davies_bouldin_score, calinski_harabasz_score
import warnings
warnings.filterwarnings('ignore')

print("=" * 80)
print("[TRAIN] K-MEANS MODEL TRAINING & ACCURACY EVALUATION")
print("=" * 80)

# ===== DB CONNECTION =====
print("\n[1/5] Connecting to database...")
conn = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="login_monitoring"
)
print("[OK] Database connected")

# ===== CREATE COLUMNS IF NOT EXISTS =====
print("\n[1.5/5] Ensuring database columns exist...")

# Check and create ai_cluster column
try:
    cur = conn.cursor()
    cur.execute("SHOW COLUMNS FROM login_logs LIKE 'ai_cluster'")
    if not cur.fetchone():
        cur.execute("ALTER TABLE login_logs ADD COLUMN ai_cluster INT DEFAULT NULL")
        conn.commit()
        print("  - Created ai_cluster column")
    else:
        print("  - ai_cluster column exists")
    cur.close()
except Exception as e:
    print(f"  [WARN] ai_cluster check: {str(e)}")

# Check and create risk_percentage column  
try:
    cur = conn.cursor()
    cur.execute("SHOW COLUMNS FROM login_logs LIKE 'risk_percentage'")
    if not cur.fetchone():
        cur.execute("ALTER TABLE login_logs ADD COLUMN risk_percentage DECIMAL(5,2) DEFAULT 0")
        conn.commit()
        print("  - Created risk_percentage column")
    else:
        print("  - risk_percentage column exists")
    cur.close()
except Exception as e:
    print(f"  [WARN] risk_percentage check: {str(e)}")

print("[OK] Database columns verified")

# ===== LOAD DATA =====
print("\n[2/5] Loading login data...")
df = pd.read_sql("""
    SELECT id, username, status, device_type, country, login_time
    FROM login_logs
    WHERE device_type IS NOT NULL
      AND country IS NOT NULL
      AND login_time IS NOT NULL
""", conn)

if df.empty:
    print("[ERROR] No data for AI training")
    exit()

print(f"[OK] Loaded {len(df)} login records")

# ===== PREPARE DATA FOR K-MEANS =====
print("\n[3/5] Preparing features for training...")
df['login_time'] = pd.to_datetime(df['login_time'])
df['login_hour'] = df['login_time'].dt.hour
df['login_day'] = df['login_time'].dt.dayofweek
df['status_encoded'] = (df['status'].str.upper() == 'FAIL').astype(int)
df['device_encoded'] = pd.factorize(df['device_type'])[0]
df['country_encoded'] = pd.factorize(df['country'])[0]

# Select features
features = df[['login_hour', 'status_encoded', 'device_encoded', 'country_encoded', 'login_day']]
print(f"[OK] Features prepared: {features.shape[1]} features, {features.shape[0]} samples")

# Standardize features
scaler = StandardScaler()
features_scaled = scaler.fit_transform(features)
print("[OK] Features standardized")

# ===== TRAIN K-MEANS MODEL =====
print("\n[4/5] Training K-Means model (3 clusters)...")
kmeans = KMeans(n_clusters=3, random_state=42, n_init=10, max_iter=300)
df['ai_cluster'] = kmeans.fit_predict(features_scaled)
print("[OK] K-Means model trained")

# ===== CALCULATE ACCURACY METRICS =====
print("\n[5/5] Calculating accuracy metrics...")

# Silhouette Score (1.0 is perfect, -1.0 is worst)
silhouette = silhouette_score(features_scaled, df['ai_cluster'])

# Davies-Bouldin Index (lower is better, 0 is perfect)
davies_bouldin = davies_bouldin_score(features_scaled, df['ai_cluster'])

# Calinski-Harabasz Index (higher is better)
calinski_harabasz = calinski_harabasz_score(features_scaled, df['ai_cluster'])

print("[OK] Metrics calculated")

# ===== CALCULATE RISK PERCENTAGE =====
def calculate_risk_percentage(row, all_data, kmeans_model, scaler_obj):
    """Calculate risk percentage 0-100%"""
    
    username = row['username']
    status = row['status'].upper()
    login_hour = row['login_hour']
    cluster_id = row['ai_cluster']
    current_time = row['login_time']
    
    # Distance from cluster center
    row_features = np.array([
        row['login_hour'], 
        row['status_encoded'], 
        row['device_encoded'], 
        row['country_encoded'], 
        row['login_day']
    ]).reshape(1, -1)
    row_features_scaled = scaler_obj.transform(row_features)
    
    center = kmeans_model.cluster_centers_[cluster_id]
    distance = np.linalg.norm(row_features_scaled[0] - center)
    distance_percentage = min(distance * 20, 40)
    
    risk_percentage = distance_percentage
    
    # Failed attempts in last 15 minutes
    recent_fails = all_data[
        (all_data['username'] == username) &
        (all_data['status_encoded'] == 1) &
        (all_data['login_time'] >= current_time - timedelta(minutes=15))
    ]
    
    if status == 'FAIL':
        risk_percentage += 30
    
    if len(recent_fails) >= 2:
        risk_percentage += 20
    elif len(recent_fails) >= 1:
        risk_percentage += 10
    
    # Unusual time (0-5 AM)
    if login_hour >= 0 and login_hour <= 5:
        risk_percentage += 15
    elif login_hour >= 22 or login_hour <= 2:
        risk_percentage += 10
    
    # Unknown country
    if row['country'] == 'Unknown':
        risk_percentage += 10
    
    risk_percentage = min(max(risk_percentage, 0), 100)
    return round(risk_percentage, 2)

# Apply risk calculation
df['risk_percentage'] = df.apply(
    lambda row: calculate_risk_percentage(row, df, kmeans, scaler), 
    axis=1
)

# Keep ai_cluster from K-Means (DON'T OVERWRITE)

# ===== SAVE TO DATABASE =====
print("\n[5/5] Saving results to database...")
cur = conn.cursor()
try:
    # Verify id column exists in dataframe
    if 'id' not in df.columns:
        print("[ERROR] Error: 'id' column not found in data")
        exit()
    
    saved_count = 0
    error_count = 0
    
    for idx, row in df.iterrows():
        try:
            record_id = int(row['id'])
            cluster = int(row['ai_cluster'])
            risk = float(row['risk_percentage'])
            
            cur.execute(
                "UPDATE login_logs SET ai_cluster=%s, risk_percentage=%s WHERE id=%s",
                (cluster, risk, record_id)
            )
            saved_count += 1
        except Exception as e:
            error_count += 1
            if error_count <= 3:  # Only show first 3 errors
                print(f"  [WARN] Error updating row {row.get('id', 'unknown')}: {str(e)}")
    
    conn.commit()
    print(f"[OK] Saved {saved_count}/{len(df)} records successfully")
    if error_count > 0:
        print(f"   ({error_count} errors)")
except Exception as e:
    print(f"[ERROR] Error saving data: {str(e)}")
    conn.rollback()

cur.close()

# ===== PRINT DETAILED REPORT =====
print("\n" + "=" * 80)
print("📊 MODEL ACCURACY EVALUATION REPORT")
print("=" * 80)

print(f"\n🎯 CLUSTERING QUALITY METRICS:")
print(f"   Silhouette Score:      {silhouette:.4f}  (Range: -1 to 1, higher is better)")
print(f"   Davies-Bouldin Index:  {davies_bouldin:.4f}  (Lower is better, 0 is perfect)")
print(f"   Calinski-Harabasz Index: {calinski_harabasz:.2f}  (Higher is better)")

if silhouette > 0.5:
    quality = "⭐⭐⭐ EXCELLENT"
elif silhouette > 0.3:
    quality = "⭐⭐ GOOD"
elif silhouette > 0.1:
    quality = "⭐ ACCEPTABLE"
else:
    quality = "❌ POOR"

print(f"\n   Overall Quality: {quality}")

print(f"\n📈 DATA DISTRIBUTION:")
print(f"   Total records analyzed: {len(df)}")

normal = len(df[df['ai_cluster'] == 0])
suspicious = len(df[df['ai_cluster'] == 1])
high_risk = len(df[df['ai_cluster'] == 2])

print(f"   NORMAL (0-35%):       {normal:3d} records ({normal/len(df)*100:5.1f}%)")
print(f"   SUSPICIOUS (35-65%):  {suspicious:3d} records ({suspicious/len(df)*100:5.1f}%)")
print(f"   HIGH RISK (65-100%):  {high_risk:3d} records ({high_risk/len(df)*100:5.1f}%)")

print(f"\n📊 RISK PERCENTAGE STATISTICS:")
print(f"   Minimum:  {df['risk_percentage'].min():6.2f}%")
print(f"   Maximum:  {df['risk_percentage'].max():6.2f}%")
print(f"   Average:  {df['risk_percentage'].mean():6.2f}%")
print(f"   Median:   {df['risk_percentage'].median():6.2f}%")
print(f"   Std Dev:  {df['risk_percentage'].std():6.2f}%")

# Show sample predictions
print(f"\n🔍 SAMPLE PREDICTIONS:")
print("\n  NORMAL (0-35%):")
normal_samples = df[df['ai_cluster'] == 0].head(2)
for idx, row in normal_samples.iterrows():
    print(f"    - {row['username']:20s} | {row['device_type']:10s} | {row['country']:15s} | Risk: {row['risk_percentage']:6.2f}% [LOW]")

print("\n  SUSPICIOUS (35-65%):")
suspicious_samples = df[df['ai_cluster'] == 1].head(2)
if len(suspicious_samples) > 0:
    for idx, row in suspicious_samples.iterrows():
        print(f"    - {row['username']:20s} | {row['device_type']:10s} | {row['country']:15s} | Risk: {row['risk_percentage']:6.2f}% [HIGH]")
else:
    print("    (No suspicious records found)")

print("\n  HIGH RISK (65-100%):")
highrisk_samples = df[df['ai_cluster'] == 2].head(2)
if len(highrisk_samples) > 0:
    for idx, row in highrisk_samples.iterrows():
        print(f"    • {row['username']:20s} | {row['device_type']:10s} | {row['country']:15s} | Risk: {row['risk_percentage']:6.2f}% 🔴")
else:
    print("    (No high-risk records found)")

print("\n" + "=" * 80)
print("✅ MODEL TRAINING COMPLETED SUCCESSFULLY")
print("=" * 80)
print("\n💡 Next: View results in AI Dashboard")
print("   http://localhost/login_monitoring/ai/ai_dashboard.php\n")

conn.close()
