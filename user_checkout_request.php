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
    $requestedTime = $data['requestedTime'] ?? null;
    
    // Kiểm tra booking thuộc về user này
    $stmt = $pdo->prepare("SELECT UserID, Status FROM Booking WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    if ($booking['UserID'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'You can only request checkout for your own bookings']);
        exit;
    }
    
    // Kiểm tra trạng thái booking
    if ($booking['Status'] !== 'checked-in') {
        echo json_encode(['success' => false, 'message' => 'Only checked-in bookings can request checkout']);
        exit;
    }
    
    // Kiểm tra xem đã có request checkout chưa
    $stmt = $pdo->prepare("SELECT RequestID FROM CheckoutRequests WHERE BookingID = :bookingID AND Status = 'pending'");
    $stmt->execute([':bookingID' => $bookingId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Checkout request already exists for this booking']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Tạo checkout request
    $stmt = $pdo->prepare("
        INSERT INTO CheckoutRequests (BookingID, UserID, RequestedCheckoutTime, Status, CreatedAt)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$bookingId, $userId, $requestedTime]);
    
    $requestId = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Checkout request submitted successfully',
        'requestID' => $requestId
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>