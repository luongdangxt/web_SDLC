<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Get featured rooms (available rooms with images)
    $query = "
        SELECT r.RoomID, r.RoomNumber, r.Price, r.Capacity, r.Status,
               rt.TypeName, rt.Description,
               (SELECT ImageURL FROM RoomImage WHERE RoomID = r.RoomID LIMIT 1) AS PrimaryImage
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE r.Status = 'available'
        ORDER BY RAND()
        LIMIT 6
    ";
    
    $stmt = $pdo->query($query);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    foreach ($rooms as &$room) {
        $room['Price'] = floatval($room['Price']);
        $room['Capacity'] = intval($room['Capacity']);
        
        // Add default image if no image exists
        if (!$room['PrimaryImage']) {
            $room['PrimaryImage'] = 'assets/images/room-placeholder.jpg';
        }
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>