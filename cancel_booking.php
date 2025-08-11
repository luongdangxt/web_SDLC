<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['bookingID'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit;
}

try {
    // Kiểm tra booking thuộc về user này
    $stmt = $pdo->prepare("SELECT UserID FROM booking WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $data['bookingID']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    if ($booking['UserID'] != $_SESSION['user']['id']) {
        echo json_encode(['success' => false, 'message' => 'You can only cancel your own bookings']);
        exit;
    }
    
    // Cập nhật trạng thái booking
    $stmt = $pdo->prepare("
        UPDATE booking 
        SET Status = 'cancelled', UpdatedAt = NOW() 
        WHERE BookingID = :bookingID
    ");
    $stmt->execute([':bookingID' => $data['bookingID']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>