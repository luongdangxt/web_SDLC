<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['avatar'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed']);
    exit;
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
    exit;
}

try {
    // Read file and convert to base64
    $fileContent = file_get_contents($file['tmp_name']);
    $base64Data = base64_encode($fileContent);
    
    // Ensure correct MIME type mapping
    $mimeType = $file['type'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Map common extensions to correct MIME types
    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (isset($mimeMap[$extension])) {
        $mimeType = $mimeMap[$extension];
    }
    
    // Validate base64 data
    if (empty($base64Data)) {
        echo json_encode(['success' => false, 'message' => 'Failed to encode image data']);
        exit;
    }
    
    $dataUrl = "data:{$mimeType};base64,{$base64Data}";
    
    // Debug: Log file info
    error_log("Avatar upload - File: {$file['name']}, Size: {$file['size']}, Type: {$mimeType}, Extension: {$extension}");
    error_log("Avatar upload - Base64 length: " . strlen($base64Data));
    
    // Update database with base64 data
    $stmt = $pdo->prepare("UPDATE users SET Avatar = ?, UpdatedAt = NOW() WHERE UserID = ?");
    $stmt->execute([$dataUrl, $userId]);
    
    // Update session data
    $_SESSION['user']['avatar'] = $dataUrl;
    
    echo json_encode([
        'success' => true,
        'message' => 'Avatar uploaded successfully',
        'avatar_url' => $dataUrl
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 