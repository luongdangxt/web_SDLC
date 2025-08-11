<?php
require_once 'db.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Bắt đầu output buffering để bắt mọi output không mong muốn
ob_start();

header('Content-Type: application/json');

// Kiểm tra phương thức request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['PUT', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Xử lý input từ FormData hoặc JSON
$input = [];
if (isset($_POST['roomData'])) {
    // Nếu dữ liệu được gửi qua FormData
    $input = json_decode($_POST['roomData'], true);
} else {
    // Nếu dữ liệu được gửi dưới dạng JSON
    $input = json_decode(file_get_contents('php://input'), true);
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Validate input
$roomID = $input['roomID'] ?? 0;
$roomNumber = trim($input['roomNumber'] ?? '');
$roomTypeID = $input['roomTypeID'] ?? null;
$price = $input['price'] ?? 0;
$capacity = $input['capacity'] ?? 1;
$status = $input['status'] ?? 'available';
$images = $input['images'] ?? [];

$errors = [];

if (empty($roomID)) {
    $errors[] = 'Room ID is required';
}

if (empty($roomNumber)) {
    $errors[] = 'Room number is required';
}

if (!is_numeric($roomTypeID) || $roomTypeID <= 0) {
    $errors[] = 'Invalid room type';
}

if (!is_numeric($price) || $price < 0) {
    $errors[] = 'Invalid price';
}

if (!is_numeric($capacity) || $capacity < 1 || $capacity > 10) {
    $errors[] = 'Capacity must be between 1 and 10';
}

if (!in_array($status, ['available', 'occupied', 'maintenance'])) {
    $errors[] = 'Invalid status';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

try {
    // Check room exists
    $stmt = $pdo->prepare('SELECT * FROM Room WHERE RoomID = ?');
    $stmt->execute([$roomID]);
    $room = $stmt->fetch();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room does not exist']);
        exit;
    }
    
    // Check duplicate room number (if changed)
    if ($room['RoomNumber'] !== $roomNumber) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Room WHERE RoomNumber = ? AND RoomID != ?');
        $stmt->execute([$roomNumber, $roomID]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Room number already exists']);
            exit;
        }
    }
    
    // Check room type exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM RoomType WHERE RoomTypeID = ?');
    $stmt->execute([$roomTypeID]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room type does not exist']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Update room
    $stmt = $pdo->prepare("UPDATE Room SET RoomNumber = ?, RoomTypeID = ?, Price = ?, Capacity = ?, Status = ? WHERE RoomID = ?");
    $stmt->execute([$roomNumber, $roomTypeID, $price, $capacity, $status, $roomID]);
    
    // Handle images - first delete existing ones
    $stmt = $pdo->prepare('DELETE FROM RoomImage WHERE RoomID = ?');
    $stmt->execute([$roomID]);
    
    // Insert new images
    if (!empty($images)) {
        $stmt = $pdo->prepare('INSERT INTO RoomImage (RoomID, ImageURL, Caption) VALUES (?, ?, ?)');
        foreach ($images as $image) {
            if (!empty($image['url'])) {
                $stmt->execute([$roomID, $image['url'], $image['caption'] ?? '']);
            }
        }
    }
    
    // Xử lý upload ảnh mới nếu có
    if (isset($_FILES['images'])) {
        $uploadedImages = [];
        
        // Xử lý nhiều file upload
        $fileCount = count($_FILES['images']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                // Kiểm tra loại file
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowedTypes)) {
                    continue; // Bỏ qua file không hợp lệ
                }
                
                // Kiểm tra kích thước file (giới hạn 5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    continue; // Bỏ qua file quá lớn
                }
                
                // Tạo tên file duy nhất và ngắn gọn
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $uploadPath = 'uploads/room_images/' . $filename;
                
                // Di chuyển file đã upload
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Lưu vào database
                    $stmt = $pdo->prepare("INSERT INTO RoomImage (RoomID, ImageURL, Caption) VALUES (?, ?, ?)");
                    $stmt->execute([$roomID, $uploadPath, '']);
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Room updated successfully'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'error_info' => $e->errorInfo ?? null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Xóa bất kỳ output không mong muốn nào
ob_end_flush();
?>