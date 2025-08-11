<?php
require_once 'cors_headers.php';
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Lấy parameters từ request
    $dateRange = $_GET['dateRange'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $roomType = $_GET['roomType'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $bookingId = $_GET['bookingId'] ?? '';

    // Base query
    $query = "
        SELECT 
            b.BookingID,
            b.CheckinDate,
            b.CheckoutDate,
            b.TotalAmount,
            b.Status,
            b.CreatedAt,
            b.UpdatedAt,
            u.Fullname as GuestName,
            u.Email as GuestEmail,
            u.Phonenumber as GuestPhone,
            r.RoomNumber,
            r.Price as RoomPrice,
            rt.TypeName as RoomType,
            rt.Description as RoomTypeDescription
        FROM Booking b
        JOIN Users u ON b.UserID = u.UserID
        JOIN Room r ON b.RoomID = r.RoomID
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE 1=1
    ";

    $params = [];

    // Single booking query - takes priority
    if (!empty($bookingId)) {
        $query .= " AND b.BookingID = :bookingId";
        $params[':bookingId'] = $bookingId;
    }

    // Filter by date range
    if ($dateRange !== 'all') {
        switch ($dateRange) {
            case 'today':
                $query .= " AND DATE(b.CreatedAt) = CURDATE()";
                break;
            case 'week':
                $query .= " AND b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $query .= " AND b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $query .= " AND b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
    }

    // Filter by status
    if ($status !== 'all') {
        $query .= " AND b.Status = :status";
        $params[':status'] = $status;
    }

    // Filter by room type
    if ($roomType !== 'all') {
        $query .= " AND rt.TypeName = :roomType";
        $params[':roomType'] = $roomType;
    }

    // Search functionality
    if (!empty($search)) {
        $query .= " AND (
            u.Fullname LIKE :search OR 
            u.Email LIKE :search OR 
            r.RoomNumber LIKE :search OR 
            b.BookingID LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    // Order by creation date (newest first)
    $query .= " ORDER BY b.CreatedAt DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
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
    error_log("Database error in admin_get_bookings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>