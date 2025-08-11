<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT RoomTypeID, TypeName FROM RoomType ORDER BY TypeName");
    $types = $stmt->fetchAll();
    
    if (empty($types)) {
        echo json_encode(['error' => 'No room types found']);
        exit;
    }
    
    echo json_encode($types);
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'sql_state' => $e->errorInfo[0] ?? '',
        'driver_code' => $e->errorInfo[1] ?? '',
        'driver_message' => $e->errorInfo[2] ?? ''
    ]);
}
?>