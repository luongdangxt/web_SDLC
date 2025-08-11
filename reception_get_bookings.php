<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền Reception
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reception') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Thêm vào sau phần kiểm tra quyền
$statusFilter = $_GET['status'] ?? '';
$bookingIdFilter = $_GET['bookingId'] ?? '';
$guestNameFilter = $_GET['guestName'] ?? '';

$validStatuses = ['pending', 'confirmed', 'checked-in', 'completed', 'cancelled'];

$whereClause = '';
$params = [];

if ($statusFilter && in_array($statusFilter, $validStatuses)) {
    $whereClause = "WHERE b.Status = :status";
    $params[':status'] = $statusFilter;
}

// If no status filter specified, show pending and confirmed for check-in management
if (!$statusFilter) {
    $whereClause = "WHERE b.Status IN ('pending', 'confirmed')";
    $params = [];
}

// Add search filters
if ($bookingIdFilter) {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'b.BookingID LIKE :bookingId';
    $params[':bookingId'] = '%' . $bookingIdFilter . '%';
}

if ($guestNameFilter) {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . 'u.Fullname LIKE :guestName';
    $params[':guestName'] = '%' . $guestNameFilter . '%';
}

$query = "SELECT 
            b.BookingID, 
            b.UserID, 
            u.Fullname AS GuestName,
            u.Email AS GuestEmail,
            u.Phonenumber AS GuestPhone,
            b.RoomID,
            r.RoomNumber,
            rt.TypeName AS RoomType,
            b.CheckinDate,
            b.CheckoutDate,
            b.TotalAmount,
            b.Status,
            b.CreatedAt,
            b.UpdatedAt
          FROM booking b
          JOIN users u ON b.UserID = u.UserID
          JOIN room r ON b.RoomID = r.RoomID
          JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
          $whereClause
          ORDER BY 
            CASE b.Status 
                WHEN 'pending' THEN 1 
                WHEN 'confirmed' THEN 2 
                ELSE 3 
            END,
            b.CheckinDate ASC"; // Sắp xếp theo trạng thái và ngày check-in

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates and add useful info
    foreach ($bookings as &$booking) {
        $checkIn = new DateTime($booking['CheckinDate']);
        $checkOut = new DateTime($booking['CheckoutDate']);
        $created = new DateTime($booking['CreatedAt']);
        
        $booking['CheckinDate_formatted'] = $checkIn->format('M d, Y H:i');
        $booking['CheckoutDate_formatted'] = $checkOut->format('M d, Y H:i');
        $booking['CreatedAt_formatted'] = $created->format('M d, Y H:i');
        $booking['Nights'] = $checkOut->diff($checkIn)->days;
        
        // Add status classes
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

        // Check if booking is ready for action
        $today = new DateTime();
        $booking['IsCheckInReady'] = ($booking['Status'] === 'confirmed' && $checkIn->format('Y-m-d') <= $today->format('Y-m-d'));
        $booking['IsCheckOutReady'] = ($booking['Status'] === 'checked-in' && $checkOut->format('Y-m-d') <= $today->format('Y-m-d'));
        $booking['CanConfirm'] = ($booking['Status'] === 'pending');
        $booking['CanCancel'] = in_array($booking['Status'], ['pending', 'confirmed']);
    }

    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'total' => count($bookings)
    ]);

} catch (PDOException $e) {
    error_log("Reception get bookings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>