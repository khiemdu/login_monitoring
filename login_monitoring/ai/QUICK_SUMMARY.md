# 📊 Test Risk Percentage - Tóm Tắt

## Dùng Để Làm Cái Gì?

**Kiểm tra độ an toàn của một lần đăng nhập** - xem lần đó bình thường hay bị tấn công / bất thường

---

## Cách Làm (4 Bước)

### 1️⃣ Mở trang
```
http://localhost/login_monitoring/ai/train_ai.php
```

### 2️⃣ Nhập thông tin đăng nhập
```
👤 Username:   tên người dùng
📱 Device:     Desktop / Mobile / Tablet / Laptop
🌍 Country:    Vietnam / Thailand / Singapore / Unknown
🕐 Time:       ngày/giờ đăng nhập
❌ Status:     SUCCESS / FAIL
```

### 3️⃣ Bấm calculate (hoặc tự động)
```
📊 Nút "Calculate Risk %" 
(hoặc tự động cập nhật khi thay đổi)
```

### 4️⃣ Xem kết quả
```
🎯 Risk % + Level
📈 Breakdown chi tiết (phân tích từng yếu tố)
```

---

## Kết Quả Dạng Nào?

| Risk | Mức | Ý Nghĩa | Làm Gì |
|------|-----|---------|--------|
| 🟢 0-25% | NORMAL | Bình thường | Cho đăng nhập |
| 🟡 25-50% | SUSPICIOUS | Hơi lạ | Yêu cầu xác minh (SMS/Email) |
| 🟠 50-75% | WARNING | Nguy hiểm | Yêu cầu 2FA |
| 🔴 75-100% | HIGH RISK | Nguy hiểm cao | Từ chối đăng nhập |

---

## Phân Tích Những Yếu Tố Nào?

| Yếu Tố | Max % | Ý Nghĩa |
|--------|-------|---------|
| 🤖 Anomaly Score | 40% | Pattern bất thường (dùng AI) |
| 📱 Device Type | 15% | Mobile/Tablet risk cao hơn Desktop |
| 🌍 Location | 10% | Vị trí Unknown = risk cao |
| 🕐 Login Time | 15% | 2AM/3AM = bất thường |
| 📊 Failed 24h | 25% | Failed attempts trong 24h |
| ⚔️ Brute-Force | 15% | 5+ fail trong 15 phút = attack |

---

## Ví Dụ Nhanh

### 🟢 Normal
```
Username: khiem1, Device: Desktop, Country: Vietnam
Time: 14:30 (chiều), Status: SUCCESS
→ 8% 🟢 NORMAL ✅
```

### 🟡 Suspicious
```
Username: khiem1, Device: Mobile, Country: Unknown
Time: 14:30, Status: FAIL
→ 42% 🟡 SUSPICIOUS ⚠️
```

### 🟠 Warning
```
Username: khiem1, Device: Tablet, Country: Unknown
Time: 02:00 (sáng), Status: FAIL
→ 54% 🟠 WARNING ⚠️⚠️
```

---

## Điểm Hay (Features)

✅ **Real-time**: Thay đổi input → tự động tính  
✅ **Chi tiết**: Biết mỗi yếu tố tính bao nhiêu %  
✅ **AI**: Dùng K-Means phân tích pattern  
✅ **Visual**: Màu sắc, vòng tròn, thanh tiến trình  
✅ **Thông minh**: Dựa trên lịch sử đăng nhập thực tế  

---

## TL;DR (Giải Thích Siêu Ngắn)

**"Dùng để kiểm tra an toàn của một lần đăng nhập"**
- Nhập: username + device + location + time + status
- Kết quả: % risk (0-100%) + giải thích chi tiết
- Mục đích: Detect login bất thường / tấn công

---

**Nếu ai hỏi → Dùng TL;DR hoặc Ví Dụ Nhanh!**
