<?php
require_once 'db.php';

// Test hiển thị ảnh từ database
echo "<h2>Test Hiển thị Ảnh</h2>";

try {
    // Lấy tất cả ảnh từ database
    $stmt = $pdo->query("SELECT * FROM roomimage ORDER BY ImageID DESC LIMIT 10");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tổng số ảnh trong database: " . count($images) . "</h3>";
    
    if (empty($images)) {
        echo "<p style='color: red;'>Không có ảnh nào trong database!</p>";
    } else {
        echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
        foreach ($images as $image) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
            echo "<h4>ImageID: " . $image['ImageID'] . "</h4>";
            echo "<p><strong>URL:</strong> " . $image['ImageURL'] . "</p>";
            echo "<p><strong>Độ dài URL:</strong> " . strlen($image['ImageURL']) . " ký tự</p>";
            echo "<p><strong>File tồn tại:</strong> " . (file_exists($image['ImageURL']) ? '✅ Có' : '❌ Không') . "</p>";
            
            // Hiển thị ảnh
            if (file_exists($image['ImageURL'])) {
                echo "<img src='" . $image['ImageURL'] . "' style='max-width: 200px; max-height: 200px; border: 1px solid #ccc;' alt='Room Image'>";
            } else {
                echo "<div style='width: 200px; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc;'>";
                echo "<p style='color: red;'>File không tồn tại</p>";
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Lỗi database: " . $e->getMessage() . "</p>";
}

// Test upload một ảnh mẫu
echo "<hr>";
echo "<h2>Test Upload Ảnh</h2>";

// Tạo một ảnh test đơn giản
$testImagePath = 'uploads/room_images/test_image.png';
$testImageData = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
$testImageBinary = base64_decode($testImageData);

// Tạo thư mục nếu chưa tồn tại
if (!is_dir('uploads/room_images/')) {
    mkdir('uploads/room_images/', 0755, true);
}

// Lưu ảnh test
if (file_put_contents($testImagePath, $testImageBinary)) {
    echo "<p style='color: green;'>✅ Đã tạo ảnh test: " . $testImagePath . "</p>";
    echo "<img src='" . $testImagePath . "' style='max-width: 100px; max-height: 100px; border: 1px solid #ccc;' alt='Test Image'>";
} else {
    echo "<p style='color: red;'>❌ Không thể tạo ảnh test</p>";
}

// Kiểm tra quyền thư mục
echo "<hr>";
echo "<h2>Kiểm tra Quyền Thư mục</h2>";
echo "<p>Thư mục uploads/room_images/ tồn tại: " . (is_dir('uploads/room_images/') ? '✅ Có' : '❌ Không') . "</p>";
echo "<p>Thư mục có thể ghi: " . (is_writable('uploads/room_images/') ? '✅ Có' : '❌ Không') . "</p>";

// Hiển thị thông tin PHP
echo "<hr>";
echo "<h2>Thông tin PHP</h2>";
echo "<p>Upload max filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>Post max size: " . ini_get('post_max_size') . "</p>";
echo "<p>Max file uploads: " . ini_get('max_file_uploads') . "</p>";
?> 