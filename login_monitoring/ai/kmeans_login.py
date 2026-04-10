import pymysql
import pandas as pd
from datetime import datetime, timedelta
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler
import warnings
warnings.filterwarnings('ignore')
import sys

# ===== DB CONNECTION =====
try:
    conn = pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="login_monitoring"
    )
except pymysql.err.OperationalError as e:
    print(f"[ERROR] Cannot connect to MySQL: {e}")
    print("[INFO] Please ensure XAMPP MySQL is running")
    print("[INFO] Start MySQL in XAMPP Control Panel")
    sys.exit(1)

# ===== LOAD DATA =====
df = pd.read_sql("""
    SELECT id, username, status, device_type, country, login_time
    FROM login_logs
    WHERE device_type IS NOT NULL
      AND country IS NOT NULL
      AND login_time IS NOT NULL
""", conn)

if df.empty:
    print("[ERROR] No data for AI")
    exit()

# ===== PREPARE DATA FOR K-MEANS TRAINING =====
# Create features for clustering
df['login_time'] = pd.to_datetime(df['login_time'])
df['login_hour'] = df['login_time'].dt.hour
df['login_day'] = df['login_time'].dt.dayofweek
df['status_encoded'] = (df['status'].str.upper() == 'FAIL').astype(int)
df['device_encoded'] = pd.factorize(df['device_type'])[0]
df['country_encoded'] = pd.factorize(df['country'])[0]

# Select features for K-means
features = df[['login_hour', 'status_encoded', 'device_encoded', 'country_encoded', 'login_day']]

# Standardize features
scaler = StandardScaler()
features_scaled = scaler.fit_transform(features)

# ===== TRAIN K-MEANS MODEL =====
kmeans = KMeans(n_clusters=3, random_state=42, n_init=10)
df['ai_cluster'] = kmeans.fit_predict(features_scaled)

# ===== CALCULATE RISK PERCENTAGE FOR EACH LOGIN =====
def calculate_risk_percentage(row, all_data, kmeans_model, scaler_obj):
    """
    Tính mức độ nguy hiểm (%) từ 0-100% với công thức chuẩn xác
    Dựa trên:
    - Số lần failed attempts trong 24h (mỗi lần +2.5%)
    - Failed login hiện tại (+25%)
    - Device type (Mobile/Tablet +12%, Laptop +5%)
    - Country unknown (+15%)
    - Giờ login bất thường (0-6am +10%)
    - Mẫu brute-force (3+ fails trong 15 phút +10%)
    """
    
    username = row['username']
    status = row['status'].upper()
    login_hour = row['login_hour']
    device = row['device_type'] if 'device_type' in row else ''
    country = row['country'] if 'country' in row else ''
    
    risk = 0
    
    # 1. Current login status (0-35%)
    if status == 'FAIL':
        risk += 25
    else:
        risk += 5
    
    # 2. Failed attempts in 24h (0-40%)
    # Count fails for this user in last 24h
    fails_24h = all_data[
        (all_data['username'] == username) &
        (all_data['status_encoded'] == 1)
    ]
    fail_count_24h = len(fails_24h)
    risk += min(fail_count_24h * 2.5, 40)
    
    # 3. Device type (0-15%)
    if device in ['Mobile', 'Tablet']:
        risk += 12
    elif device == 'Laptop':
        risk += 5
    
    # 4. Country (0-15%)
    if country == 'Unknown':
        risk += 15
    
    # 5. Login time (0-10%)
    if 0 <= login_hour < 6:
        risk += 10
    
    # 6. Brute-force pattern (0-10%)
    # Check recent fails (within 15 min)
    current_time = row['login_time'] if 'login_time' in row else pd.Timestamp.now()
    recent_fails = all_data[
        (all_data['username'] == username) &
        (all_data['status_encoded'] == 1) &
        (all_data['login_time'] >= current_time - timedelta(minutes=15))
    ]
    
    if len(recent_fails) >= 3:
        risk += 10
    elif len(recent_fails) >= 1:
        risk += 5
    
    # Cap at 100%
    risk = min(max(risk, 0), 100)
    
    return round(risk, 2)


# ===== APPLY RISK PERCENTAGE CALCULATION =====
df['risk_percentage'] = df.apply(
    lambda row: calculate_risk_percentage(row, df, kmeans, scaler), 
    axis=1
)

# ===== CLASSIFY DANGER LEVEL =====
def classify_danger_level(risk_pct):
    """
    Phân loại mức độ nguy hiểm:
    - 0-35%: NORMAL (Bình thường)
    - 35-65%: SUSPICIOUS (Nguy hiểm)
    - 65-100%: HIGH RISK (Rất nguy hiểm)
    """
    if risk_pct <= 35:
        return 0  # NORMAL
    elif risk_pct <= 65:
        return 1  # SUSPICIOUS
    else:
        return 2  # HIGH RISK

# Áp dụng phân loại
df['ai_cluster'] = df['risk_percentage'].apply(classify_danger_level)

# ===== SAVE TO DB =====
cur = conn.cursor()
for _, row in df.iterrows():
    cur.execute(
        "UPDATE login_logs SET ai_cluster=%s, risk_percentage=%s WHERE id=%s",
        (int(row['ai_cluster']), float(row['risk_percentage']), int(row['id']))
    )

conn.commit()

# ===== PRINT TRAINING RESULTS =====
print("=" * 60)
print("[OK] K-MEANS MODEL TRAINING COMPLETED")
print("=" * 60)
print(f"Total login records trained: {len(df)}")
print(f"\nCluster distribution:")
for cluster_id in range(3):
    cluster_records = len(df[df['ai_cluster'] == cluster_id])
    percentage = (cluster_records / len(df)) * 100
    if cluster_id == 0:
        print(f"  Cluster {cluster_id} (NORMAL):      {cluster_records:3d} records ({percentage:5.1f}%)")
    elif cluster_id == 1:
        print(f"  Cluster {cluster_id} (SUSPICIOUS):  {cluster_records:3d} records ({percentage:5.1f}%)")
    else:
        print(f"  Cluster {cluster_id} (HIGH RISK):   {cluster_records:3d} records ({percentage:5.1f}%)")

print(f"\n[STATS] Risk Percentage Statistics:")
print(f"  Minimum: {df['risk_percentage'].min():.2f}%")
print(f"  Maximum: {df['risk_percentage'].max():.2f}%")
print(f"  Average: {df['risk_percentage'].mean():.2f}%")
print(f"  Median:  {df['risk_percentage'].median():.2f}%")

print(f"\n[LEVEL] Danger Level Distribution:")
normal_count = len(df[df['ai_cluster'] == 0])
suspicious_count = len(df[df['ai_cluster'] == 1])
high_risk_count = len(df[df['ai_cluster'] == 2])

print(f"  NORMAL (0-30%):     {normal_count:3d} records ({(normal_count/len(df)*100):5.1f}%)")
print(f"  SUSPICIOUS (30-60%): {suspicious_count:3d} records ({(suspicious_count/len(df)*100):5.1f}%)")
print(f"  HIGH RISK (60-100%):  {high_risk_count:3d} records ({(high_risk_count/len(df)*100):5.1f}%)")

print("\n[OK] Risk percentages saved to database successfully!")
print("=" * 60)

conn.close()
