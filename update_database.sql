-- Script để mở rộng độ dài trường ImageURL trong bảng roomimage
-- Chạy script này để tránh lỗi cắt ngắn đường dẫn ảnh

-- Kiểm tra và cập nhật độ dài trường ImageURL
ALTER TABLE roomimage MODIFY COLUMN ImageURL VARCHAR(500);

-- Kiểm tra và cập nhật độ dài trường Caption nếu cần
ALTER TABLE roomimage MODIFY COLUMN Caption VARCHAR(500);

-- Hiển thị cấu trúc bảng sau khi cập nhật
DESCRIBE roomimage; 