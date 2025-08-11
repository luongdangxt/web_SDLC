# Hướng dẫn Upload Ảnh Phòng

## Tổng quan
Hệ thống đã được cập nhật để hỗ trợ upload và quản lý ảnh phòng một cách hoàn chỉnh. Ảnh sẽ được lưu trữ trong thư mục `uploads/room_images/` và đường dẫn sẽ được lưu trong database.

## Các file đã được tạo/cập nhật

### Backend Files
1. **upload_image.php** - Xử lý upload ảnh đơn lẻ
2. **delete_image.php** - Xử lý xóa ảnh
3. **admin_add_room.php** - Cập nhật để hỗ trợ upload ảnh
4. **admin_update_room.php** - Cập nhật để hỗ trợ upload ảnh

### Frontend Files
1. **admin_room_functions.js** - JavaScript xử lý upload ảnh
2. **admin_room_form_example.html** - Form mẫu để test

## Cách sử dụng

### 1. Upload ảnh khi thêm phòng mới
```html
<form id="addRoomForm" enctype="multipart/form-data">
    <!-- Các trường thông tin phòng -->
    <div class="form-group">
        <label for="images">Ảnh phòng:</label>
        <input type="file" name="images" accept="image/*" multiple>
        <div class="image-preview"></div>
    </div>
    <button type="submit">Thêm phòng</button>
</form>
```

### 2. Upload ảnh khi sửa phòng
```html
<form id="updateRoomForm" enctype="multipart/form-data">
    <input type="hidden" name="roomID">
    <!-- Các trường thông tin phòng -->
    
    <!-- Hiển thị ảnh hiện tại -->
    <div class="form-group">
        <label>Ảnh hiện tại:</label>
        <div class="existing-images"></div>
    </div>
    
    <!-- Thêm ảnh mới -->
    <div class="form-group">
        <label for="editImages">Thêm ảnh mới:</label>
        <input type="file" name="images" accept="image/*" multiple>
        <div class="image-preview"></div>
    </div>
    
    <button type="submit">Cập nhật phòng</button>
</form>
```

### 3. JavaScript xử lý
```javascript
// Khởi tạo AdminRoomManager
const adminManager = new AdminRoomManager();

// Load dữ liệu phòng để sửa
adminManager.loadRoomForEdit(roomId);

// Xóa ảnh
adminManager.deleteImage(imageUrl);
```

## Tính năng

### ✅ Đã hoàn thành
- Upload nhiều ảnh cùng lúc
- Preview ảnh trước khi upload
- Xóa ảnh từ database và server
- Hỗ trợ các định dạng: JPG, PNG, GIF, WebP
- Giới hạn kích thước file: 5MB
- Tạo tên file duy nhất để tránh conflict
- Validation loại file và kích thước

### 🔧 Cấu hình
- Thư mục lưu ảnh: `uploads/room_images/`
- Kích thước tối đa: 5MB
- Định dạng hỗ trợ: JPG, PNG, GIF, WebP
- Tên file: `[unique_id].[extension]` (đã tối ưu để ngắn gọn)
- Độ dài tối đa đường dẫn: ~50 ký tự (thay vì ~80 ký tự trước đây)

## Cấu trúc Database

### Bảng RoomImage
```sql
CREATE TABLE roomimage (
    ImageID INT PRIMARY KEY AUTO_INCREMENT,
    RoomID INT,
    ImageURL VARCHAR(500),  -- Đã tăng từ 255 lên 500
    Caption VARCHAR(500),   -- Đã tăng từ 255 lên 500
    FOREIGN KEY (RoomID) REFERENCES Room(RoomID)
);
```

**Lưu ý:** Nếu database hiện tại có trường ImageURL VARCHAR(255), hãy chạy script sau để mở rộng:
```sql
ALTER TABLE roomimage MODIFY COLUMN ImageURL VARCHAR(500);
ALTER TABLE roomimage MODIFY COLUMN Caption VARCHAR(500);
```

## API Endpoints

### 1. Upload ảnh đơn lẻ
```
POST /upload_image.php
Content-Type: multipart/form-data

Parameters:
- image: File (required)
- caption: String (optional)
```

### 2. Xóa ảnh
```
DELETE /delete_image.php
Content-Type: application/json

Body: {"imageUrl": "path/to/image.jpg"}
```

### 3. Thêm phòng với ảnh
```
POST /admin_add_room.php
Content-Type: multipart/form-data

Parameters:
- roomData: JSON string (required)
- images[]: File array (optional)
```

### 4. Sửa phòng với ảnh
```
POST /admin_update_room.php
Content-Type: multipart/form-data

Parameters:
- roomData: JSON string (required)
- images[]: File array (optional)
```

## Lưu ý quan trọng

1. **Quyền thư mục**: Đảm bảo thư mục `uploads/room_images/` có quyền ghi
2. **Bảo mật**: Chỉ cho phép upload file ảnh, kiểm tra MIME type
3. **Performance**: Nên resize ảnh trước khi lưu để tiết kiệm dung lượng
4. **Backup**: Nên backup thư mục ảnh thường xuyên

## Troubleshooting

### Lỗi thường gặp

1. **Không upload được ảnh**
   - Kiểm tra quyền thư mục `uploads/room_images/`
   - Kiểm tra `php.ini` settings: `upload_max_filesize`, `post_max_size`

2. **Ảnh không hiển thị**
   - Kiểm tra đường dẫn ảnh trong database
   - Kiểm tra file có tồn tại trong thư mục không

3. **Lỗi database**
   - Kiểm tra kết nối database
   - Kiểm tra cấu trúc bảng `roomimage`

4. **Đường dẫn ảnh bị cắt ngắn**
   - Mở file `database_checker.html` để kiểm tra cấu trúc database
   - Chạy script SQL: `update_database.sql`
   - Hoặc sử dụng nút "Sửa Database" trong `database_checker.html`

### Debug
```php
// Thêm vào đầu file để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Test

1. Mở file `admin_room_form_example.html` trong browser
2. Thử upload ảnh và thêm phòng
3. Kiểm tra ảnh đã được lưu trong thư mục `uploads/room_images/`
4. Kiểm tra dữ liệu trong database 