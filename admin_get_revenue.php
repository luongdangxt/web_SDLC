<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $period = $_GET['period'] ?? 'month';
    $roomType = $_GET['roomType'] ?? 'all';

    // Calculate date range based on period
    $dateCondition = '';
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(b.CreatedAt) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $dateCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $dateCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $dateCondition = "1=1";
    }

    $roomTypeCondition = '';
    $params = [];
    if ($roomType !== 'all') {
        $roomTypeCondition = "AND rt.TypeName = :roomType";
        $params[':roomType'] = $roomType;
    }

    // 1. Total Revenue
    $revenueQuery = "
        SELECT 
            COALESCE(SUM(b.TotalAmount), 0) as totalRevenue,
            COUNT(b.BookingID) as totalBookings,
            AVG(b.TotalAmount) as avgBookingValue
        FROM booking b
        JOIN room r ON b.RoomID = r.RoomID
        JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.Status IN ('confirmed', 'checked-in', 'completed') 
        AND $dateCondition 
        $roomTypeCondition
    ";

    $stmt = $pdo->prepare($revenueQuery);
    $stmt->execute($params);
    $revenueData = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Revenue by Room Type
    $roomTypeRevenueQuery = "
        SELECT 
            rt.TypeName,
            COALESCE(SUM(b.TotalAmount), 0) as revenue,
            COUNT(b.BookingID) as bookings,
            AVG(b.TotalAmount) as avgRate
        FROM roomtype rt
        LEFT JOIN room r ON rt.RoomTypeID = r.RoomTypeID
        LEFT JOIN booking b ON r.RoomID = b.RoomID 
            AND b.Status IN ('confirmed', 'checked-in', 'completed')
            AND $dateCondition
        GROUP BY rt.RoomTypeID, rt.TypeName
        ORDER BY revenue DESC
    ";

    $stmt = $pdo->prepare($roomTypeRevenueQuery);
    $stmt->execute();
    $roomTypeRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate percentages for room type revenue
    $totalRoomTypeRevenue = array_sum(array_column($roomTypeRevenue, 'revenue'));
    foreach ($roomTypeRevenue as &$type) {
        $type['percentage'] = $totalRoomTypeRevenue > 0 ? 
            round(($type['revenue'] / $totalRoomTypeRevenue) * 100, 1) : 0;
    }

    // 3. Top Performing Rooms
    $topRoomsQuery = "
        SELECT 
            r.RoomNumber,
            rt.TypeName,
            COALESCE(SUM(b.TotalAmount), 0) as revenue,
            COUNT(b.BookingID) as bookings,
            CASE 
                WHEN COUNT(b.BookingID) > 0 THEN 
                    ROUND((COUNT(b.BookingID) * 100.0 / 
                        (SELECT COUNT(*) FROM booking WHERE Status IN ('confirmed', 'checked-in', 'completed') AND $dateCondition)), 1)
                ELSE 0 
            END as occupancyRate
        FROM room r
        JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
        LEFT JOIN booking b ON r.RoomID = b.RoomID 
            AND b.Status IN ('confirmed', 'checked-in', 'completed')
            AND $dateCondition
        GROUP BY r.RoomID, r.RoomNumber, rt.TypeName
        ORDER BY revenue DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($topRoomsQuery);
    $stmt->execute();
    $topRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Daily Revenue Trend (last 30 days)
    $trendQuery = "
        SELECT 
            DATE(b.CreatedAt) as date,
            COALESCE(SUM(b.TotalAmount), 0) as revenue,
            COUNT(b.BookingID) as bookings
        FROM booking b
        JOIN room r ON b.RoomID = r.RoomID
        JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.Status IN ('confirmed', 'checked-in', 'completed')
        AND b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        $roomTypeCondition
        GROUP BY DATE(b.CreatedAt)
        ORDER BY date ASC
    ";

    $stmt = $pdo->prepare($trendQuery);
    $stmt->execute($params);
    $dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Occupancy Rate
    $occupancyQuery = "
        SELECT 
            COUNT(DISTINCT r.RoomID) as totalRooms,
            COUNT(DISTINCT CASE WHEN b.Status IN ('confirmed', 'checked-in') 
                AND CURDATE() BETWEEN DATE(b.CheckinDate) AND DATE(b.CheckoutDate) 
                THEN b.RoomID END) as occupiedRooms
        FROM room r
        LEFT JOIN booking b ON r.RoomID = b.RoomID
    ";

    $stmt = $pdo->prepare($occupancyQuery);
    $stmt->execute();
    $occupancyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $occupancyRate = $occupancyData['totalRooms'] > 0 ? 
        round(($occupancyData['occupiedRooms'] / $occupancyData['totalRooms']) * 100, 1) : 0;

    // 6. Previous period comparison (for growth calculation)
    $prevPeriodCondition = '';
    switch ($period) {
        case 'today':
            $prevPeriodCondition = "DATE(b.CreatedAt) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $prevPeriodCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND b.CreatedAt < DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $prevPeriodCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND b.CreatedAt < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $prevPeriodCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND b.CreatedAt < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $prevPeriodCondition = "b.CreatedAt >= DATE_SUB(NOW(), INTERVAL 2 YEAR) AND b.CreatedAt < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }

    $prevRevenueQuery = "
        SELECT 
            COALESCE(SUM(b.TotalAmount), 0) as totalRevenue,
            COUNT(b.BookingID) as totalBookings
        FROM booking b
        JOIN room r ON b.RoomID = r.RoomID
        JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.Status IN ('confirmed', 'checked-in', 'completed') 
        AND $prevPeriodCondition 
        $roomTypeCondition
    ";

    $stmt = $pdo->prepare($prevRevenueQuery);
    $stmt->execute($params);
    $prevRevenueData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate growth percentages
    $revenueGrowth = $prevRevenueData['totalRevenue'] > 0 ? 
        round((($revenueData['totalRevenue'] - $prevRevenueData['totalRevenue']) / $prevRevenueData['totalRevenue']) * 100, 1) : 0;
    
    $bookingsGrowth = $prevRevenueData['totalBookings'] > 0 ? 
        round((($revenueData['totalBookings'] - $prevRevenueData['totalBookings']) / $prevRevenueData['totalBookings']) * 100, 1) : 0;

    // Format response
    $response = [
        'success' => true,
        'period' => $period,
        'summary' => [
            'totalRevenue' => floatval($revenueData['totalRevenue']),
            'totalBookings' => intval($revenueData['totalBookings']),
            'avgBookingValue' => floatval($revenueData['avgBookingValue']),
            'occupancyRate' => floatval($occupancyRate),
            'revenueGrowth' => floatval($revenueGrowth),
            'bookingsGrowth' => floatval($bookingsGrowth)
        ],
        'roomTypeRevenue' => $roomTypeRevenue,
        'topRooms' => $topRooms,
        'dailyTrend' => $dailyTrend,
        'occupancy' => [
            'totalRooms' => intval($occupancyData['totalRooms']),
            'occupiedRooms' => intval($occupancyData['occupiedRooms']),
            'rate' => floatval($occupancyRate)
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in admin_get_revenue.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>