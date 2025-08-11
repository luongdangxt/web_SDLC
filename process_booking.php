


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

// Normalize keys from frontend (accept both checkInDate/checkinDate)
$roomID = $data['roomID'] ?? null;
$checkInDate = $data['checkInDate'] ?? ($data['checkinDate'] ?? null);
$checkOutDate = $data['checkOutDate'] ?? ($data['checkoutDate'] ?? null);

// Validate input
if (!$data || !$roomID || !$checkInDate || !$checkOutDate) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
$totalAmount = $data['totalAmount'] ?? 0;

try {
    // Kiểm tra phòng có tồn tại và available không
    $stmt = $pdo->prepare("SELECT * FROM Room WHERE RoomID = ? AND Status = 'available'");
    $stmt->execute([$roomID]);
    $room = $stmt->fetch();
    
    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not available']);
        exit;
    }
    
    // Kiểm tra xem phòng có bị book trong khoảng thời gian này không
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Booking 
        WHERE RoomID = ? AND Status IN ('confirmed', 'checked-in')
        AND (
            (CheckinDate <= ? AND CheckoutDate > ?) OR
            (CheckinDate < ? AND CheckoutDate >= ?) OR
            (CheckinDate >= ? AND CheckoutDate <= ?)
        )
    ");
    $stmt->execute([$roomID, $checkInDate, $checkInDate, $checkOutDate, $checkOutDate, $checkInDate, $checkOutDate]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Room is not available for selected dates']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Tạo booking
    $stmt = $pdo->prepare("
        INSERT INTO Booking (UserID, RoomID, CheckinDate, CheckoutDate, TotalAmount, Status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$_SESSION['user']['id'], $roomID, $checkInDate, $checkOutDate, $totalAmount]);
    
    $bookingID = $pdo->lastInsertId();
    
    // Cập nhật trạng thái phòng (tùy chọn)
    // $stmt = $pdo->prepare("UPDATE Room SET Status = 'booked' WHERE RoomID = :roomID");
    // $stmt->execute([':roomID' => $roomID]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'bookingID' => $bookingID
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>