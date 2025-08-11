<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Get search parameters
    $checkInDate = $_GET['check_in'] ?? '';
    $checkOutDate = $_GET['check_out'] ?? '';
    $guests = $_GET['guests'] ?? '';
    $roomType = $_GET['room_type'] ?? 'all';
    $priceMin = $_GET['price_min'] ?? '';
    $priceMax = $_GET['price_max'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'price-low';

    // Check if this is a valid search request
    // At least one search parameter must be provided
    $hasSearchCriteria = false;
    
    if (!empty($checkInDate) && !empty($checkOutDate)) {
        $hasSearchCriteria = true;
    }
    if (!empty($guests) && $guests > 0) {
        $hasSearchCriteria = true;
    }
    if ($roomType !== 'all') {
        $hasSearchCriteria = true;
    }
    if (!empty($priceMin) || !empty($priceMax)) {
        $hasSearchCriteria = true;
    }
    
    // If no search criteria provided, return empty results
    if (!$hasSearchCriteria) {
        echo json_encode([
            'success' => true,
            'rooms' => [],
            'total' => 0,
            'message' => 'Please provide search criteria to find rooms',
            'filters' => [
                'check_in' => $checkInDate,
                'check_out' => $checkOutDate,
                'guests' => $guests,
                'room_type' => $roomType,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'sort_by' => $sortBy
            ]
        ]);
        exit;
    }

    // Convert guests to integer for processing
    $guests = !empty($guests) ? (int)$guests : 0;

    // Build the base query
    $query = "SELECT DISTINCT
                r.RoomID, 
                r.RoomNumber, 
                rt.TypeName, 
                rt.Description,
                r.Price,
                r.Status,
                r.Capacity,
                (SELECT ImageURL FROM roomimage WHERE RoomID = r.RoomID LIMIT 1) AS PrimaryImage
              FROM room r
              JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
              WHERE r.Status = 'available'";

    $params = [];

    // Add room type filter
    if ($roomType !== 'all') {
        $query .= " AND r.RoomTypeID = ?";
        $params[] = $roomType;
    }

    // Add capacity filter - ensure room can accommodate the requested number of guests
    if ($guests > 0) {
        // Basic capacity check - room must be able to accommodate the requested guests
        $query .= " AND r.Capacity >= ?";
        $params[] = $guests;
        
        // For smaller groups, don't show rooms that are too large (waste of space)
        if ($guests <= 3) {
            $query .= " AND r.Capacity <= ?";
            $params[] = $guests + 1; // Allow 1 extra person max for small groups
        } elseif ($guests <= 5) {
            $query .= " AND r.Capacity <= ?";
            $params[] = $guests + 2; // Allow 2 extra people for medium groups
        }
        // For larger groups (6+), show any room that can accommodate them
    }

    // Debug: Log the query and parameters (for development only)
    error_log("Search query: " . $query);
    error_log("Search parameters: " . json_encode($params));
    error_log("Guests requested: " . $guests);

    // Add price filters
    if (!empty($priceMin)) {
        $query .= " AND r.Price >= ?";
        $params[] = $priceMin;
    }
    if (!empty($priceMax)) {
        $query .= " AND r.Price <= ?";
        $params[] = $priceMax;
    }

    // Add date availability check (exclude rooms with conflicting bookings)
    if (!empty($checkInDate) && !empty($checkOutDate)) {
        $query .= " AND r.RoomID NOT IN (
            SELECT DISTINCT b.RoomID 
            FROM booking b 
            WHERE b.Status IN ('confirmed', 'checked_in')
            AND (
                (b.CheckInDate <= ? AND b.CheckOutDate > ?) OR
                (b.CheckInDate < ? AND b.CheckOutDate >= ?) OR
                (b.CheckInDate >= ? AND b.CheckOutDate <= ?)
            )
        )";
        $params[] = $checkOutDate;
        $params[] = $checkInDate;
        $params[] = $checkOutDate;
        $params[] = $checkInDate;
        $params[] = $checkInDate;
        $params[] = $checkOutDate;
    }

    // Add sorting
    switch ($sortBy) {
        case 'price-high':
            $query .= " ORDER BY r.Price DESC";
            break;
        case 'price-low':
            $query .= " ORDER BY r.Price ASC";
            break;
        case 'name':
            $query .= " ORDER BY r.RoomNumber ASC";
            break;
        case 'type':
            $query .= " ORDER BY rt.TypeName ASC";
            break;
        default:
            $query .= " ORDER BY r.Price ASC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log search results count
    error_log("Found " . count($rooms) . " rooms matching search criteria");

    // Add empty amenities array for compatibility with frontend
    foreach ($rooms as &$room) {
        $room['Amenities'] = []; // Empty array since we don't have amenities
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'total' => count($rooms),
        'debug' => [
            'query' => $query,
            'params' => $params,
            'guests_requested' => $guests,
            'filtered_rooms' => array_map(function($room) {
                return [
                    'room_number' => $room['RoomNumber'],
                    'capacity' => $room['Capacity'],
                    'type' => $room['TypeName']
                ];
            }, $rooms)
        ],
        'filters' => [
            'check_in' => $checkInDate,
            'check_out' => $checkOutDate,
            'guests' => $guests,
            'room_type' => $roomType,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'sort_by' => $sortBy
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 