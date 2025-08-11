<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Check for single room request by ID
    if (isset($_GET['id'])) {
        $roomId = (int)$_GET['id'];
        
        $query = "SELECT r.RoomID, r.RoomNumber, r.Price, r.Status, r.RoomTypeID, r.Capacity,
                  rt.TypeName, rt.Description AS TypeDescription
                  FROM Room r
                  LEFT JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
                  WHERE r.RoomID = :roomId";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':roomId' => $roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            echo json_encode(['error' => 'Room not found']);
            exit;
        }
        
        // Get room images
        $imageQuery = "SELECT ImageURL FROM RoomImage WHERE RoomID = :roomId";
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute([':roomId' => $roomId]);
        $room['images'] = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($room);
        exit;
    }
    
    // Build base query for multiple rooms
    $query = "SELECT r.RoomID, r.RoomNumber, r.Price, r.Status, 
          rt.TypeName, rt.Description AS TypeDescription,
          (SELECT COUNT(*) FROM RoomImage ri WHERE ri.RoomID = r.RoomID) AS ImageCount,
          (SELECT ri.ImageURL FROM RoomImage ri WHERE ri.RoomID = r.RoomID LIMIT 1) AS PrimaryImage
          FROM Room r
          LEFT JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID";
    
    // Check for featured rooms request
    if (isset($_GET['featured']) && $_GET['featured'] == 'true') {
        $query .= " WHERE r.Status = 'available' ORDER BY RAND() LIMIT 3";
    }
    // Check for search filters
    else if (isset($_GET['checkIn']) && isset($_GET['checkOut'])) {
        $checkIn = $_GET['checkIn'];
        $checkOut = $_GET['checkOut'];
        $guests = $_GET['guests'] ?? 1;
        $type = $_GET['type'] ?? 'all';
        
        // Basic filtering - in a real app, you'd need to check availability against bookings
        $query .= " WHERE r.Status = 'available' >= $guests";
        
        if ($type !== 'all') {
            $query .= " AND rt.TypeName = '$type'";
        }
        
        $query .= " ORDER BY r.Price ASC";
    }
    // Default query for admin
    else {
        $query .= " ORDER BY r.RoomID DESC";
    }
    
    $stmt = $pdo->query($query);
    $rooms = $stmt->fetchAll();
    
    echo json_encode($rooms);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>