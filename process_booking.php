


<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'You need to login to make a booking']);
    exit;
}

$userID = $_SESSION['user']['id'];
$data = json_decode(file_get_contents('php://input'), true);

// Log input for debugging
error_log("Booking request data: " . json_encode($data));

// Validate input
if (empty($data['roomID']) || empty($data['checkinDate']) || empty($data['checkoutDate']) || empty($data['totalAmount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Kiểm tra tính khả dụng của phòng
$roomID = $data['roomID'];
$checkinDate = $data['checkinDate'];
$checkoutDate = $data['checkoutDate'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM booking 
    WHERE RoomID = :roomID 
    AND Status NOT IN ('cancelled', 'completed')
    AND (
        (CheckinDate <= :checkinDate AND CheckoutDate > :checkinDate) OR
        (CheckinDate < :checkoutDate AND CheckoutDate >= :checkoutDate) OR
        (CheckinDate >= :checkinDate AND CheckoutDate <= :checkoutDate)
    )
");
$stmt->execute([
    ':roomID' => $roomID,
    ':checkinDate' => $checkinDate,
    ':checkoutDate' => $checkoutDate
]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Room is not available for the selected dates']);
    exit;
}

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();

    // Tạo booking
    $stmt = $pdo->prepare("
        INSERT INTO booking (UserID, RoomID, CheckinDate, CheckoutDate, TotalAmount, Status)
        VALUES (:userID, :roomID, :checkinDate, :checkoutDate, :totalAmount, 'pending')
    ");
    
    $stmt->execute([
        ':userID' => $userID,
        ':roomID' => $data['roomID'],
        ':checkinDate' => $data['checkinDate'],
        ':checkoutDate' => $data['checkoutDate'],
        ':totalAmount' => $data['totalAmount']
    ]);
    
    $bookingID = $pdo->lastInsertId();

    // Cập nhật trạng thái phòng nếu cần
    // $stmt = $pdo->prepare("UPDATE rooms SET Status = 'booked' WHERE RoomID = :roomID");
    // $stmt->execute([':roomID' => $data['roomID']]);

    $pdo->commit();

    // Trả về thông tin booking
    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'bookingID' => $bookingID,
        'booking' => [
            'BookingID' => $bookingID,
            'UserID' => $userID,
            'RoomID' => $data['roomID'],
            'CheckinDate' => $data['checkinDate'],
            'CheckoutDate' => $data['checkoutDate'],
            'TotalAmount' => $data['totalAmount'],
            'Status' => 'pending',
            'CreatedAt' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>