<?php
require_once 'db.php';
header('Content-Type: application/json');

error_log("Received request for room details");

if (!isset($_GET['id'])) {
    error_log("Room ID is missing");
    echo json_encode(['error' => 'Room ID is required']);
    exit;
}

$roomID = $_GET['id'];
error_log("Fetching details for room ID: " . $roomID);

try {
    // Lấy thông tin cơ bản của phòng
    $query = "SELECT 
                r.RoomID, 
                r.RoomNumber, 
                rt.TypeName, 
                rt.Description AS TypeDescription,
                r.Price,
                r.Status
              FROM room r
              JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
              WHERE r.RoomID = :roomID";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':roomID' => $roomID]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        error_log("Room not found for ID: " . $roomID);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    // Lấy danh sách ảnh của phòng
    $query = "SELECT ImageURL FROM roomimage WHERE RoomID = :roomID";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':roomID' => $roomID]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $room['images'] = $images;

    error_log("Returning room data: " . json_encode($room));
    echo json_encode($room);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}