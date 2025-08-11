<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra xem có file được upload không
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];
$caption = $_POST['caption'] ?? '';

// Kiểm tra loại file
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Kiểm tra kích thước file (giới hạn 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB']);
    exit;
}

// Tạo tên file duy nhất và ngắn gọn
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$uploadPath = 'uploads/room_images/' . $filename;

// Kiểm tra và tạo thư mục nếu chưa tồn tại
$uploadDir = 'uploads/room_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Di chuyển file đã upload
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Tạo URL cho ảnh
    $imageUrl = $uploadPath;
    
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'imageUrl' => $imageUrl,
        'filename' => $filename,
        'caption' => $caption
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
}
?> 