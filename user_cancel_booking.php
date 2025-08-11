<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'You need to login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['bookingID'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$userID = $_SESSION['user']['id'];
$bookingID = $data['bookingID'];
$reason = $data['reason'] ?? '';

try {
    $pdo->beginTransaction();
    
    // Kiểm tra booking có thuộc về user này không và trạng thái có thể hủy không
    $stmt = $pdo->prepare("
        SELECT BookingID, Status, RoomID, CheckinDate, TotalAmount 
        FROM booking 
        WHERE BookingID = :bookingID AND UserID = :userID
    ");
    $stmt->execute([
        ':bookingID' => $bookingID,
        ':userID' => $userID
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found or you do not have permission to cancel this booking');
    }
    
    // Kiểm tra trạng thái có thể hủy không
    $cancellableStatuses = ['pending', 'confirmed'];
    if (!in_array($booking['Status'], $cancellableStatuses)) {
        throw new Exception('This booking cannot be cancelled. Current status: ' . $booking['Status']);
    }
    
    // Kiểm tra thời gian - không cho hủy nếu còn ít hơn 24h đến checkin (optional rule)
    $checkinDate = new DateTime($booking['CheckinDate']);
    $now = new DateTime();
    $hoursDiff = ($checkinDate->getTimestamp() - $now->getTimestamp()) / 3600;
    
    if ($hoursDiff < 24 && $hoursDiff > 0) {
        // Có thể điều chỉnh rule này theo policy của hotel
        // throw new Exception('Cannot cancel booking less than 24 hours before check-in');
    }
    
    // Cập nhật trạng thái booking
    $stmt = $pdo->prepare("
        UPDATE booking 
        SET Status = 'cancelled', UpdatedAt = NOW() 
        WHERE BookingID = :bookingID
    ");
    $stmt->execute([':bookingID' => $bookingID]);
    
    // Nếu room đang occupied, set lại thành available
    if ($booking['Status'] === 'confirmed') {
        $stmt = $pdo->prepare("UPDATE room SET Status = 'available' WHERE RoomID = :roomID");
        $stmt->execute([':roomID' => $booking['RoomID']]);
    }
    
    // Log cancellation reason nếu cần
    if (!empty($reason)) {
        $stmt = $pdo->prepare("
            INSERT INTO booking_logs (BookingID, Action, Description, CreatedAt) 
            VALUES (:bookingID, 'cancelled', :reason, NOW())
        ");
        // Chỉ insert nếu table booking_logs tồn tại
        try {
            $stmt->execute([
                ':bookingID' => $bookingID,
                ':reason' => 'User cancellation: ' . $reason
            ]);
        } catch (PDOException $e) {
            // Ignore if table doesn't exist
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully',
        'bookingID' => $bookingID
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("User cancel booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("User cancel booking DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>