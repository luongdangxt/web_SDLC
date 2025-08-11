<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'check';
    
    switch ($action) {
        case 'check':
            checkDatabase();
            break;
        case 'fix':
            fixDatabase();
            break;
        case 'view':
            viewImages();
            break;
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function checkDatabase() {
    global $pdo;
    
    // Kiểm tra cấu trúc bảng
    $stmt = $pdo->query("DESCRIBE roomimage");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $imageUrlColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'ImageURL') {
            $imageUrlColumn = $column;
            break;
        }
    }
    
    // Kiểm tra dữ liệu
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM roomimage");
    $totalImages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("
        SELECT ImageID, RoomID, 
               LEFT(ImageURL, 50) as preview, 
               LENGTH(ImageURL) as length,
               CASE 
                   WHEN ImageURL LIKE 'data:image%' THEN 'base64'
                   WHEN ImageURL LIKE 'uploads/%' THEN 'file_path'
                   ELSE 'unknown'
               END as type
        FROM roomimage 
        ORDER BY length DESC 
        LIMIT 10
    ");
    $imageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đếm các loại ảnh
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN ImageURL LIKE 'data:image%' THEN 1 END) as base64_count,
            COUNT(CASE WHEN ImageURL LIKE 'uploads/%' THEN 1 END) as file_count,
            COUNT(CASE WHEN ImageURL NOT LIKE 'data:image%' AND ImageURL NOT LIKE 'uploads/%' THEN 1 END) as other_count
        FROM roomimage
    ");
    $typeCounts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'database_structure' => $imageUrlColumn,
        'total_images' => $totalImages,
        'image_types' => $typeCounts,
        'sample_data' => $imageData,
        'recommendations' => getRecommendations($imageUrlColumn, $typeCounts)
    ], JSON_PRETTY_PRINT);
}

function fixDatabase() {
    global $pdo;
    
    // Thay đổi cấu trúc database
    $pdo->exec("ALTER TABLE roomimage MODIFY COLUMN ImageURL TEXT");
    $pdo->exec("ALTER TABLE roomimage MODIFY COLUMN Caption TEXT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Database structure updated to support base64 images',
        'changes' => [
            'ImageURL column changed to TEXT',
            'Caption column changed to TEXT'
        ]
    ], JSON_PRETTY_PRINT);
}

function viewImages() {
    global $pdo;
    
    // Lấy tất cả ảnh
    $stmt = $pdo->query("
        SELECT ImageID, RoomID, ImageURL, Caption,
               LENGTH(ImageURL) as url_length,
               CASE 
                   WHEN ImageURL LIKE 'data:image%' THEN 'base64'
                   WHEN ImageURL LIKE 'uploads/%' THEN 'file_path'
                   ELSE 'unknown'
               END as type
        FROM roomimage 
        ORDER BY ImageID DESC
    ");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format cho HTML output
    header('Content-Type: text/html');
    echo generateImageViewHTML($images);
}

function getRecommendations($column, $typeCounts) {
    $recommendations = [];
    
    if ($column && strpos($column['Type'], 'varchar') !== false) {
        $recommendations[] = "ImageURL is VARCHAR - should be TEXT for base64 images";
    }
    
    if ($typeCounts['base64_count'] > 0) {
        $recommendations[] = "Found {$typeCounts['base64_count']} base64 images - ensure database supports long text";
    }
    
    if ($typeCounts['file_count'] > 0) {
        $recommendations[] = "Found {$typeCounts['file_count']} file path images - these should work normally";
    }
    
    return $recommendations;
}

function generateImageViewHTML($images) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Base64 Images Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .image-item { 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            padding: 15px; 
            border-radius: 8px;
            background: #f9f9f9;
        }
        .image-preview { 
            max-width: 200px; 
            max-height: 200px; 
            border: 1px solid #ccc; 
            margin: 10px 0;
        }
        .base64 { border-left: 4px solid #28a745; }
        .file_path { border-left: 4px solid #007bff; }
        .unknown { border-left: 4px solid #dc3545; }
        .info { font-family: monospace; font-size: 12px; color: #666; }
        .stats { background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Base64 Images Viewer</h1>
    
    <div class="stats">
        <h3>Statistics</h3>
        <p><strong>Total Images:</strong> ' . count($images) . '</p>
    </div>';
    
    foreach ($images as $image) {
        $html .= '<div class="image-item ' . $image['type'] . '">
            <h4>Image ID: ' . $image['ImageID'] . ' (Room: ' . $image['RoomID'] . ')</h4>
            <p><strong>Type:</strong> ' . $image['type'] . '</p>
            <p><strong>URL Length:</strong> ' . $image['url_length'] . ' characters</p>
            <p><strong>Caption:</strong> ' . htmlspecialchars($image['Caption'] ?? '') . '</p>';
            
        if ($image['type'] === 'base64') {
            $html .= '<img src="' . htmlspecialchars($image['ImageURL']) . '" class="image-preview" alt="Base64 Image" />';
        } else if ($image['type'] === 'file_path') {
            $html .= '<img src="' . htmlspecialchars($image['ImageURL']) . '" class="image-preview" alt="File Image" />';
        }
        
        $html .= '<div class="info">
            <strong>URL Preview:</strong> ' . htmlspecialchars(substr($image['ImageURL'], 0, 100)) . '...
        </div>
        </div>';
    }
    
    $html .= '</body></html>';
    return $html;
}
?>