-- Script để sửa database cho ảnh base64
-- Chạy script này để lưu ảnh base64 dài

-- Thay đổi kiểu dữ liệu ImageURL từ VARCHAR thành TEXT
-- TEXT có thể chứa tới 65,535 ký tự
ALTER TABLE roomimage MODIFY COLUMN ImageURL TEXT;

-- Hoặc nếu cần chứa ảnh rất lớn, dùng LONGTEXT (4GB)
-- ALTER TABLE roomimage MODIFY COLUMN ImageURL LONGTEXT;

-- Thay đổi Caption cũng thành TEXT để đảm bảo
ALTER TABLE roomimage MODIFY COLUMN Caption TEXT;

-- Kiểm tra cấu trúc bảng
DESCRIBE roomimage;

-- Kiểm tra dữ liệu hiện tại
SELECT ImageID, RoomID, LEFT(ImageURL, 50) as ImageURL_Preview, LENGTH(ImageURL) as URL_Length 
FROM roomimage 
ORDER BY URL_Length DESC 
LIMIT 10;