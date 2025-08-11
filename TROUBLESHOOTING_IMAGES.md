# Hướng dẫn Khắc phục Lỗi Ảnh

## 🔍 **Các bước kiểm tra và sửa lỗi:**

### 1. **Kiểm tra Database**
```bash
# Cho ảnh file path thông thường:
database_checker.html

# Cho ảnh base64 (MỚI):
base64_image_manager.html
```

### 2. **Test Upload và Hiển thị Ảnh**
```bash
# Mở file này để test nhanh:
test_quick_upload.html

# Hoặc test chi tiết:
simple_image_test.html
```

### 3. **Kiểm tra cấu trúc thư mục**
```bash
# Đảm bảo thư mục tồn tại:
uploads/room_images/
```

### 4. **Test hiển thị ảnh từ database**
```bash
# Mở file này để xem ảnh trong database:
test_image_display.php
```

## 🚨 **Các lỗi thường gặp và cách sửa:**

### **Lỗi 1: Ảnh không hiển thị**
**Nguyên nhân:**
- Đường dẫn ảnh không đúng
- File ảnh không tồn tại
- Quyền thư mục không đủ

**Cách sửa:**
1. Mở `simple_image_test.html` để test
2. Kiểm tra console browser để xem lỗi
3. Đảm bảo thư mục `uploads/room_images/` có quyền ghi

### **Lỗi 2: Upload ảnh thất bại**
**Nguyên nhân:**
- Kích thước file quá lớn
- Loại file không được hỗ trợ
- Quyền thư mục không đủ

**Cách sửa:**
1. Kiểm tra `php.ini` settings:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   max_file_uploads = 20
   ```

2. Kiểm tra quyền thư mục:
   ```bash
   chmod 755 uploads/room_images/
   ```

### **Lỗi 3: Đường dẫn ảnh bị cắt ngắn**
**Nguyên nhân:**
- Trường ImageURL trong database quá ngắn (VARCHAR(255))

**Cách sửa:**
1. Mở `database_checker.html`
2. Nhấn "Sửa Database" để mở rộng trường lên VARCHAR(500)

### **Lỗi 6: Ảnh Base64 bị cắt ngắn**
**Nguyên nhân:**
- Lưu ảnh dưới dạng base64 nhưng trường database quá ngắn
- Base64 có thể dài hàng nghìn ký tự, vượt quá VARCHAR(500)

**Cách sửa:**
1. Mở `base64_image_manager.html` để kiểm tra và sửa
2. Hoặc chạy SQL: `ALTER TABLE roomimage MODIFY COLUMN ImageURL TEXT;`
3. TEXT có thể chứa tới 65,535 ký tự

### **Lỗi 4: Ảnh hiển thị bị lỗi**
**Nguyên nhân:**
- Đường dẫn tương đối/tuyệt đối không đúng
- Base URL không chính xác

**Cách sửa:**
1. File `fix_image_display.js` sẽ tự động sửa đường dẫn
2. Kiểm tra console để xem thông tin debug

### **Lỗi 5: JavaScript Console Errors**
**Nguyên nhân:**
- Element không tồn tại khi script chạy
- Function không được định nghĩa
- Event listener lỗi

**Cách sửa:**
1. File `fix_console_errors.js` sẽ tự động sửa các lỗi
2. Mở `test_console_fixes.html` để test
3. Kiểm tra console browser để xem lỗi đã được sửa

## 🛠️ **Các file quan trọng:**

### **Backend Files:**
- `upload_image.php` - Upload ảnh đơn lẻ
- `test_image_display.php` - Test hiển thị ảnh
- `fix_database.php` - Sửa cấu trúc database

### **Frontend Files:**
- `fix_image_display.js` - Sửa lỗi hiển thị ảnh
- `fix_console_errors.js` - Sửa lỗi JavaScript console
- `image_handler.js` - Xử lý ảnh toàn diện (NEW)
- `test_quick_upload.html` - Test upload nhanh (NEW)
- `base64_image_manager.html` - Quản lý ảnh base64 (NEW)
- `simple_image_test.html` - Test upload và hiển thị
- `database_checker.html` - Kiểm tra database
- `test_console_fixes.html` - Test sửa lỗi console

## 📋 **Checklist khắc phục:**

### ✅ **Bước 1: Kiểm tra Database**
- [ ] Mở `database_checker.html`
- [ ] Kiểm tra cấu trúc bảng roomimage
- [ ] Sửa độ dài trường ImageURL nếu cần

### ✅ **Bước 2: Test Upload**
- [ ] Mở `simple_image_test.html`
- [ ] Thử upload một ảnh
- [ ] Kiểm tra kết quả

### ✅ **Bước 3: Test Hiển thị**
- [ ] Kiểm tra ảnh đã upload có hiển thị không
- [ ] Xem console browser để debug
- [ ] Kiểm tra đường dẫn ảnh

### ✅ **Bước 4: Test Form chính**
- [ ] Mở `admin_room_form_example.html`
- [ ] Thử thêm phòng với ảnh
- [ ] Kiểm tra ảnh hiển thị trong form

## 🔧 **Debug Commands:**

### **Kiểm tra thư mục:**
```bash
# Windows
dir uploads\room_images\

# Linux/Mac
ls -la uploads/room_images/
```

### **Kiểm tra quyền:**
```bash
# Linux/Mac
ls -la uploads/
```

### **Test PHP:**
```bash
# Mở trong browser:
test_image_display.php
```

## 📞 **Nếu vẫn lỗi:**

1. **Kiểm tra Console Browser:**
   - Mở Developer Tools (F12)
   - Xem tab Console để tìm lỗi

2. **Kiểm tra Network:**
   - Tab Network trong Developer Tools
   - Xem request/response của ảnh

3. **Kiểm tra PHP Error Log:**
   - Xem file error log của PHP
   - Thường ở `/var/log/apache2/error.log` hoặc tương tự

4. **Test với ảnh đơn giản:**
   - Thử với ảnh nhỏ (< 1MB)
   - Thử với định dạng JPG/PNG

## 🎯 **Kết quả mong đợi:**

- ✅ Upload ảnh thành công
- ✅ Ảnh hiển thị đúng trong form
- ✅ Ảnh lưu đúng trong database
- ✅ Đường dẫn ảnh không bị cắt ngắn
- ✅ Có thể xóa ảnh thành công 