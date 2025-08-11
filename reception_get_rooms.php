<?php
require_once 'cors_headers.php';
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền reception
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reception') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get all rooms with their current status and booking info
    $query = "
        SELECT r.RoomID, r.RoomNumber, r.Price, r.Capacity, r.Status,
               rt.TypeName as RoomType, rt.Description,
               b.BookingID, b.CheckinDate, b.CheckoutDate, b.Status as BookingStatus,
               u.Fullname as GuestName, u.Email as GuestEmail,
               cr.Status as CleanupStatus
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        LEFT JOIN Booking b ON r.RoomID = b.RoomID 
            AND b.Status IN ('confirmed', 'checked-in')
            AND CURDATE() BETWEEN DATE(b.CheckinDate) AND DATE(b.CheckoutDate)
        LEFT JOIN Users u ON b.UserID = u.UserID
        LEFT JOIN CleanupRequest cr ON r.RoomID = cr.RoomID 
            AND cr.Status IN ('pending', 'in-progress')
        ORDER BY r.RoomNumber ASC
    ";
    
    $stmt = $pdo->query($query);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $summary = [
        'total' => count($rooms),
        'available' => 0,
        'occupied' => 0,
        'maintenance' => 0,
        'booked' => 0
    ];
    
    // Format room data
    foreach ($rooms as &$room) {
        $room['Price'] = floatval($room['Price']);
        $room['Capacity'] = intval($room['Capacity']);
        
        // Count by status
        switch ($room['Status']) {
            case 'available':
                $summary['available']++;
                break;
            case 'occupied':
                $summary['occupied']++;
                break;
            case 'maintenance':
                $summary['maintenance']++;
                break;
            case 'booked':
                $summary['booked']++;
                break;
        }
        
        // Format dates if booking exists
        if ($room['CheckinDate']) {
            $checkIn = new DateTime($room['CheckinDate']);
            $checkOut = new DateTime($room['CheckoutDate']);
            $room['CheckinDate_formatted'] = $checkIn->format('M d, Y');
            $room['CheckoutDate_formatted'] = $checkOut->format('M d, Y');
            $room['Nights'] = $checkOut->diff($checkIn)->days;
        }
        
        // Status badge class
        switch ($room['Status']) {
            case 'available':
                $room['StatusClass'] = 'bg-green-100 text-green-800';
                break;
            case 'occupied':
                $room['StatusClass'] = 'bg-red-100 text-red-800';
                break;
            case 'maintenance':
                $room['StatusClass'] = 'bg-yellow-100 text-yellow-800';
                break;
            case 'booked':
                $room['StatusClass'] = 'bg-blue-100 text-blue-800';
                break;
            default:
                $room['StatusClass'] = 'bg-gray-100 text-gray-800';
        }
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'summary' => $summary
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>