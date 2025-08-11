<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    $query = "SELECT r.*, rt.TypeName, 
          (SELECT COUNT(*) FROM RoomImage ri WHERE ri.RoomID = r.RoomID) AS ImageCount,
          (SELECT ri.ImageURL FROM RoomImage ri WHERE ri.RoomID = r.RoomID LIMIT 1) AS PrimaryImage
          FROM Room r
          LEFT JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
          ORDER BY r.RoomID DESC";
    
    $stmt = $pdo->query($query);
    $rooms = $stmt->fetchAll();
    
    echo json_encode($rooms);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>