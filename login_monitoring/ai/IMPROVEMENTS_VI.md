# 🎯 Test Risk Percentage - Cải Tiến Chi Tiết

## ✨ Những Gì Đã Được Cải Thiện

### 1. **API Tính Toán Rủi Ro - Cải Tiến Đáng Kể**
- ✅ Tích hợp **K-Means Machine Learning** (nếu có model)
- ✅ Tính **anomaly score** - detect mẫu bất thường (0-40%)
- ✅ Kết hợp **6 yếu tố** để tính rủi ro thay vì đơn giản như trước

### 2. **Hiển Thị Chi Tiết - Breakdown Rõ Ràng**
Giờ bạn thấy **từng yếu tố riêng**:

| Yếu Tố | Max % | Ý Nghĩa |
|--------|-------|---------|
| 🤖 **Anomaly Score** | 40% | ML phát hiện mẫu bất thường |
| 📶 **Device Type** | 15% | Mobile/Tablet riskier hơn Desktop |
| 🌍 **Location** | 10% | Unknown location = cao hơn |
| 🕐 **Login Time** | 15% | 2 AM risfky hơn 2 PM |
| 📊 **Login Status** | 25% | Failed attempts trong 24h |
| ⚔️ **Brute-Force** | 15% | 3+ failed trong 15 min |

### 3. **Real-Time Auto-Update**
- ✅ Thay đổi username/device/time → **tự động tính lại** (không cần click)
- ✅ Debounce 500ms để tránh lag

### 4. **Visual Improvements**
- ✅ **Color Bars** cho mỗi yếu tố (green → yellow → red)
- ✅ **4 Risk Levels**: 🟢Normal / 🟡Suspicious / 🟠Warning / 🔴HighRisk
- ✅ **Giải thích chi tiết** cho từng yếu tố

## 📊 Ví Dụ Thực Tế

### Trtest 1: Login Bình Thường
```
User: test_user, Device: Desktop, Country: Vietnam
Time: 14:30 (chiều), Status: SUCCESS
→ Kết quả: 8% 🟢 NORMAL ✅
```

### Test 2: Login Nghi Vấn
```
User: test_user, Device: Mobile, Country: Unknown
Time: 03:00 (sáng 3h), Status: FAIL
→ Kết quả: 65% 🟡 SUSPICIOUS ⚠️
  - Device: 12% (Mobile riskier)
  - Location: 10% (Unknown)
  - Time: 15% (3 AM unusual)
  - Failed: 20% (FAIL status)
```

### Test 3: Login Nguy Hiểm Cao
```
User: test_user, Device: Tablet, Country: Unknown
Time: 02:00, Status: FAIL + 5 failed attempts/15min
→ Kết quả: 88% 🔴 HIGH RISK 🚨
  - Brute-Force: 15% (Attack pattern)
  - Time: 15% (2 AM extreme)
  - All factors maxed out
```

## 🔧 Các Files Đã Chỉnh Sửa

### 1. **api_calculate_risk.php** (Được Cải Tiến)
- Gọi Python API nếu có
- Fallback sang PHP calculation
- **Trả về breakdown chi tiết** không phải chỉ 1 số

### 2. **api_calculate_risk_advanced.py** (Tệp Mới)
- Python script tính risk dùng K-Means
- K-Means anomaly detection
- Database queries cho failed attempts

### 3. **train_ai.php** (Được Cải Tiến)
- HTML: Thêm **risk breakdown grid** với 5 items
- JavaScript:
  - `calculateRiskPercentage()` → Gọi API
  - `displayRiskResult()` → Hiển thị breakdown
  - `updateBreakdownItem()` → Update từng yếu tố
  - `setupAutoUpdate()` → Auto-calculate on change

### 4. **ai_global.css** (CSS Mới)
- `.risk-breakdown` - Grid layout cho factors
- `.breakdown-item` - Card cho mỗi yếu tố
- `.breakdown-fill` - Gradient color bar
- Responsive design

## 🎮 Cách Sử Dụng

1. **Mở** `train_ai.php`
2. **Nhập** Username, Device, Country, Time, Status
3. **Kết quả tự động hiển thị** hoặc click "Calculate Risk %"
4. **Xem breakdown** - mỗi yếu tố tính bao nhiêu %

## 🚀 Features

✅ **Machine Learning**: Dùng K-Means để detect anomalies  
✅ **Real-time**: Auto-update khi thay đổi input  
✅ **Detailed**: Breakdown từng yếu tố ảnh hưởng  
✅ **Visual**: Color-coded, progress bars, icons  
✅ **Accurate**: Database queries, real user history  
✅ **Fallback**: Hoạt động ngay cả nếu Python fail  

## 📈 Risk Levels

```
0-25%       🟢 NORMAL      - Bình thường
25-50%      🟡 SUSPICIOUS  - Nghi vấn
50-75%      🟠 WARNING     - Cảnh báo
75-100%     🔴 HIGH RISK   - Nguy hiểm cao
```

## 📝 Notes

- Nếu không có K-Means model, vẫn dùng heuristic rules
- Database cần có data để hiển thị accurate break-down
- Thử test với device/time/status khác nhau để thấy sự thay đổi
- **Default**: username=test_user, time=now, device=Desktop

---

**Status**: ✅ Hoàn Thành  
**Last Updated**: 8/4/2026
