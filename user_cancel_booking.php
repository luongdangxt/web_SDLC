<?php
require_once 'cors_headers.php';
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
    $userId = $_SESSION['user']['id'];
    $bookingId = $data['bookingID'];
    
    // Kiểm tra booking thuộc về user này
    $stmt = $pdo->prepare("SELECT UserID, RoomID, Status FROM Booking WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    if ($booking['UserID'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'You can only cancel your own bookings']);
        exit;
    }
    
    // Kiểm tra trạng thái booking
    if ($booking['Status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Booking is already cancelled']);
        exit;
    }
    
    if ($booking['Status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel completed booking']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Cập nhật trạng thái booking
    $stmt = $pdo->prepare("UPDATE Booking SET Status = 'cancelled', UpdatedAt = NOW() WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $bookingId]);
    
    // Cập nhật trạng thái phòng về available
    $stmt = $pdo->prepare("UPDATE Room SET Status = 'available' WHERE RoomID = :roomID");
    $stmt->execute([':roomID' => $booking['RoomID']]);
    
    // Ghi log
    $stmt = $pdo->prepare("INSERT INTO BookingLogs (BookingID, Action, Description, CreatedAt) VALUES (?, 'cancelled', 'Booking cancelled by user', NOW())");
    $stmt->execute([$bookingId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>