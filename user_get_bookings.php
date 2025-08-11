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

try {
    $userId = $_SESSION['user']['id'];
    
    // Lấy tất cả booking của user
    $stmt = $pdo->prepare("
        SELECT b.BookingID, b.CheckinDate, b.CheckoutDate, b.TotalAmount, b.Status, b.CreatedAt,
               r.RoomNumber, r.Price as RoomPrice,
               rt.TypeName as RoomType
        FROM Booking b
        JOIN Room r ON b.RoomID = r.RoomID
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.UserID = ?
        ORDER BY b.CreatedAt DESC
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and calculate additional info
    foreach ($bookings as &$booking) {
        $checkIn = new DateTime($booking['CheckinDate']);
        $checkOut = new DateTime($booking['CheckoutDate']);
        $created = new DateTime($booking['CreatedAt']);
        
        $booking['CheckinDate_formatted'] = $checkIn->format('M d, Y');
        $booking['CheckoutDate_formatted'] = $checkOut->format('M d, Y');
        $booking['CreatedAt_formatted'] = $created->format('M d, Y H:i');
        $booking['Nights'] = $checkOut->diff($checkIn)->days;
        
        // Status badge class
        switch ($booking['Status']) {
            case 'pending':
                $booking['StatusClass'] = 'bg-yellow-100 text-yellow-800';
                break;
            case 'confirmed':
                $booking['StatusClass'] = 'bg-blue-100 text-blue-800';
                break;
            case 'checked-in':
                $booking['StatusClass'] = 'bg-green-100 text-green-800';
                break;
            case 'completed':
                $booking['StatusClass'] = 'bg-gray-100 text-gray-800';
                break;
            case 'cancelled':
                $booking['StatusClass'] = 'bg-red-100 text-red-800';
                break;
            default:
                $booking['StatusClass'] = 'bg-gray-100 text-gray-800';
        }
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'total' => count($bookings)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>