<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

$roomId = $_GET['id'] ?? 0;

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    // Get room details
    $stmt = $pdo->prepare("
        SELECT r.RoomID, r.RoomNumber, r.Price, r.Capacity, r.Status,
               rt.TypeName, rt.Description
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE r.RoomID = :roomId
    ");
    $stmt->execute([':roomId' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    
    // Get room images
    $stmt = $pdo->prepare("SELECT ImageURL FROM RoomImage WHERE RoomID = :roomId");
    $stmt->execute([':roomId' => $roomId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $room['Images'] = array_column($images, 'ImageURL');
    
    echo json_encode([
        'success' => true,
        'room' => $room
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>