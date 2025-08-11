<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userID = $_SESSION['user']['id'];

try {
    $query = "SELECT 
                b.BookingID, 
                b.RoomID,
                r.RoomNumber,
                rt.TypeName AS RoomType,
                b.CheckinDate,
                b.CheckoutDate,
                b.TotalAmount,
                b.Status,
                b.CreatedAt
              FROM booking b
              JOIN room r ON b.RoomID = r.RoomID
              JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
              WHERE b.UserID = :userID
              ORDER BY b.CreatedAt DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':userID' => $userID]);
    $bookings = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>