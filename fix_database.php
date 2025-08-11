<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Kiểm tra cấu trúc bảng roomimage
    $stmt = $pdo->query("DESCRIBE roomimage");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $imageUrlColumn = null;
    $captionColumn = null;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'ImageURL') {
            $imageUrlColumn = $column;
        }
        if ($column['Field'] === 'Caption') {
            $captionColumn = $column;
        }
    }
    
    $result = [
        'success' => true,
        'message' => 'Database structure checked',
        'current_structure' => [
            'ImageURL' => $imageUrlColumn,
            'Caption' => $captionColumn
        ],
        'recommendations' => []
    ];
    
    // Kiểm tra độ dài trường ImageURL
    if ($imageUrlColumn) {
        preg_match('/varchar\((\d+)\)/i', $imageUrlColumn['Type'], $matches);
        $currentLength = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if ($currentLength < 500) {
            $result['recommendations'][] = "ImageURL field length is {$currentLength}, recommended: 500";
            
            // Tự động sửa nếu được yêu cầu
            if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
                $pdo->exec("ALTER TABLE roomimage MODIFY COLUMN ImageURL VARCHAR(500)");
                $result['message'] .= ' - Database structure updated';
                $result['fixed'] = true;
            }
        } else {
            $result['message'] .= ' - ImageURL field length is sufficient';
        }
    }
    
    // Kiểm tra độ dài trường Caption
    if ($captionColumn) {
        preg_match('/varchar\((\d+)\)/i', $captionColumn['Type'], $matches);
        $currentLength = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if ($currentLength < 500) {
            $result['recommendations'][] = "Caption field length is {$currentLength}, recommended: 500";
            
            // Tự động sửa nếu được yêu cầu
            if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
                $pdo->exec("ALTER TABLE roomimage MODIFY COLUMN Caption VARCHAR(500)");
                $result['message'] .= ' - Caption field updated';
            }
        }
    }
    
    // Kiểm tra dữ liệu hiện tại
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM roomimage");
    $totalImages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT ImageURL, LENGTH(ImageURL) as url_length FROM roomimage ORDER BY url_length DESC LIMIT 5");
    $longestUrls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['data_info'] = [
        'total_images' => $totalImages,
        'longest_urls' => $longestUrls
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
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