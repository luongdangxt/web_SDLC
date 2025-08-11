<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

// Get search parameters
$checkIn = $_GET['checkIn'] ?? '';
$checkOut = $_GET['checkOut'] ?? '';
$guests = $_GET['guests'] ?? 1;
$roomType = $_GET['roomType'] ?? 'all';
$priceMin = $_GET['priceMin'] ?? 0;
$priceMax = $_GET['priceMax'] ?? 999999;

try {
    // Base query
    $query = "
        SELECT r.RoomID, r.RoomNumber, r.Price, r.Capacity, r.Status,
               rt.TypeName, rt.Description,
               (SELECT ImageURL FROM RoomImage WHERE RoomID = r.RoomID LIMIT 1) AS PrimaryImage
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE r.Status = 'available'
    ";
    
    $params = [];
    
    // Add capacity filter
    if ($guests > 0) {
        $query .= " AND r.Capacity >= ?";
        $params[] = $guests;
    }
    
    // Add room type filter
    if ($roomType !== 'all') {
        $query .= " AND rt.TypeName = ?";
        $params[] = $roomType;
    }
    
    // Add price filter
    if ($priceMin > 0) {
        $query .= " AND r.Price >= ?";
        $params[] = $priceMin;
    }
    
    if ($priceMax < 999999) {
        $query .= " AND r.Price <= ?";
        $params[] = $priceMax;
    }
    
    // Check availability for date range if provided
    if (!empty($checkIn) && !empty($checkOut)) {
        $query .= " AND r.RoomID NOT IN (
            SELECT DISTINCT b.RoomID 
            FROM Booking b 
            WHERE b.Status IN ('confirmed', 'checked-in')
            AND (
                (b.CheckinDate <= ? AND b.CheckoutDate > ?) OR
                (b.CheckinDate < ? AND b.CheckoutDate >= ?) OR
                (b.CheckinDate >= ? AND b.CheckoutDate <= ?)
            )
        )";
        $params = array_merge($params, [$checkIn, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
    }
    
    $query .= " ORDER BY r.Price ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    foreach ($rooms as &$room) {
        $room['Price'] = floatval($room['Price']);
        $room['Capacity'] = intval($room['Capacity']);
        
        // Add default image if no image exists
        if (!$room['PrimaryImage']) {
            $room['PrimaryImage'] = 'assets/images/room-placeholder.jpg';
        }
        
        // Calculate total price for the stay if dates are provided
        if (!empty($checkIn) && !empty($checkOut)) {
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $nights = $checkOutDate->diff($checkInDate)->days;
            $room['TotalPrice'] = $room['Price'] * $nights;
            $room['Nights'] = $nights;
        }
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'total' => count($rooms),
        'filters' => [
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'guests' => $guests,
            'roomType' => $roomType,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 