<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Get all available rooms for homepage display
    $query = "SELECT 
                r.RoomID, 
                r.RoomNumber, 
                rt.TypeName, 
                rt.Description,
                r.Price,
                r.Status,
                r.Capacity,
                (SELECT ImageURL FROM roomimage WHERE RoomID = r.RoomID LIMIT 1) AS PrimaryImage
              FROM room r
              JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
              WHERE r.Status = 'available'
              ORDER BY r.Price ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add empty amenities array for compatibility with frontend
    foreach ($rooms as &$room) {
        $room['Amenities'] = []; // Empty array since we don't have amenities
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'total' => count($rooms)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>