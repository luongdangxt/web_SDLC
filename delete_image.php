<?php
require_once 'db.php';

header('Content-Type: application/json');

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Đọc input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['imageUrl'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image URL is required']);
    exit;
}

$imageUrl = $input['imageUrl'];

try {
    // Xóa ảnh khỏi database
    $stmt = $pdo->prepare('DELETE FROM roomimage WHERE ImageURL = ?');
    $stmt->execute([$imageUrl]);
    
    // Xóa file ảnh khỏi server nếu tồn tại
    if (file_exists($imageUrl)) {
        unlink($imageUrl);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 