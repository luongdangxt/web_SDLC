<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền Reception
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reception') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get all rooms with their current status and booking information
    $query = "
        SELECT 
            r.RoomID,
            r.RoomNumber,
            r.Price,
            r.Status as RoomStatus,
            rt.TypeName as RoomType,
            rt.Description as RoomDescription,
            -- Current booking info if any
            b.BookingID,
            b.UserID,
            u.Fullname as GuestName,
            u.Email as GuestEmail,
            u.Phonenumber as GuestPhone,
            b.CheckinDate,
            b.CheckoutDate,
            b.Status as BookingStatus,
            -- Cleanup request info if any
            cr.CleanupRequestID,
            cr.RequestTime,
            cr.Status as CleanupStatus
        FROM room r
        JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
        LEFT JOIN booking b ON r.RoomID = b.RoomID 
            AND b.Status IN ('confirmed', 'checked-in')
            AND CURDATE() BETWEEN DATE(b.CheckinDate) AND DATE(b.CheckoutDate)
        LEFT JOIN users u ON b.UserID = u.UserID
        LEFT JOIN cleanuprequest cr ON r.RoomID = cr.RoomID 
            AND cr.Status = 'pending'
        ORDER BY 
            CAST(SUBSTRING(r.RoomNumber, 1, 1) AS UNSIGNED),
            CAST(SUBSTRING(r.RoomNumber, 2) AS UNSIGNED)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data and determine display status
    foreach ($rooms as &$room) {
        // Format dates if booking exists
        if ($room['CheckinDate']) {
            $checkIn = new DateTime($room['CheckinDate']);
            $checkOut = new DateTime($room['CheckoutDate']);
            $room['CheckinDate_formatted'] = $checkIn->format('M d, H:i');
            $room['CheckoutDate_formatted'] = $checkOut->format('M d, H:i');
        }

        // Format cleanup request time
        if ($room['RequestTime']) {
            $requestTime = new DateTime($room['RequestTime']);
            $room['RequestTime_formatted'] = $requestTime->format('M d, H:i');
        }

        // Determine actual room status for display
        if ($room['BookingStatus'] === 'checked-in') {
            $room['DisplayStatus'] = 'occupied';
            $room['StatusClass'] = 'bg-blue-100 text-blue-800';
            $room['StatusIcon'] = 'fas fa-user';
        } elseif ($room['BookingStatus'] === 'confirmed') {
            $room['DisplayStatus'] = 'reserved';
            $room['StatusClass'] = 'bg-orange-100 text-orange-800';
            $room['StatusIcon'] = 'fas fa-calendar-check';
        } elseif ($room['CleanupStatus'] === 'pending') {
            $room['DisplayStatus'] = 'cleaning';
            $room['StatusClass'] = 'bg-yellow-100 text-yellow-800';
            $room['StatusIcon'] = 'fas fa-broom';
        } elseif ($room['RoomStatus'] === 'maintenance') {
            $room['DisplayStatus'] = 'maintenance';
            $room['StatusClass'] = 'bg-red-100 text-red-800';
            $room['StatusIcon'] = 'fas fa-tools';
        } elseif ($room['RoomStatus'] === 'available') {
            $room['DisplayStatus'] = 'available';
            $room['StatusClass'] = 'bg-green-100 text-green-800';
            $room['StatusIcon'] = 'fas fa-check';
        } else {
            $room['DisplayStatus'] = 'unknown';
            $room['StatusClass'] = 'bg-gray-100 text-gray-800';
            $room['StatusIcon'] = 'fas fa-question';
        }
    }

    // Calculate summary statistics
    $summary = [
        'total' => count($rooms),
        'available' => 0,
        'occupied' => 0,
        'reserved' => 0,
        'cleaning' => 0,
        'maintenance' => 0
    ];

    foreach ($rooms as $room) {
        switch ($room['DisplayStatus']) {
            case 'available':
                $summary['available']++;
                break;
            case 'occupied':
                $summary['occupied']++;
                break;
            case 'reserved':
                $summary['reserved']++;
                break;
            case 'cleaning':
                $summary['cleaning']++;
                break;
            case 'maintenance':
                $summary['maintenance']++;
                break;
        }
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'summary' => $summary
    ]);

} catch (PDOException $e) {
    error_log("Reception get rooms error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>