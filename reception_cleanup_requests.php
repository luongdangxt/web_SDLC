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

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            getCleanupRequests();
            break;
        case 'create':
            createCleanupRequest();
            break;
        case 'update':
            updateCleanupStatus();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getCleanupRequests() {
    global $pdo;
    
    $query = "
        SELECT cr.CleanupRequestID, cr.RoomID, cr.RequestTime, cr.Status, cr.CreatedAt,
               r.RoomNumber, r.Status as RoomStatus,
               rt.TypeName as RoomType,
               b.BookingID, b.CheckoutDate,
               u.Fullname as GuestName, u.Email as GuestEmail
        FROM CleanupRequest cr
        JOIN Room r ON cr.RoomID = r.RoomID
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        LEFT JOIN Booking b ON cr.RoomID = b.RoomID AND b.Status = 'checked-in'
        LEFT JOIN Users u ON b.UserID = u.UserID
        ORDER BY cr.CreatedAt DESC
    ";
    
    $stmt = $pdo->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($requests as &$request) {
        $request['RequestTime_formatted'] = date('M d, Y H:i', strtotime($request['RequestTime']));
        $request['CreatedAt_formatted'] = date('M d, Y H:i', strtotime($request['CreatedAt']));
        
        if ($request['CheckoutDate']) {
            $request['CheckoutDate_formatted'] = date('M d, Y', strtotime($request['CheckoutDate']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
}

function createCleanupRequest() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['roomID'])) {
        echo json_encode(['success' => false, 'message' => 'Room ID is required']);
        return;
    }
    
    $roomID = $data['roomID'];
    
    // Check if room exists
    $stmt = $pdo->prepare("SELECT RoomID FROM Room WHERE RoomID = ?");
    $stmt->execute([$roomID]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        return;
    }
    
    // Check if cleanup request already exists for this room
    $checkQuery = "SELECT CleanupRequestID FROM CleanupRequest WHERE RoomID = :roomID AND Status = 'pending'";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([':roomID' => $roomID]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cleanup request already exists for this room']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create cleanup request
        $insertQuery = "INSERT INTO CleanupRequest (RoomID, RequestTime, Status, CreatedAt) VALUES (:roomID, NOW(), 'pending', NOW())";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([':roomID' => $roomID]);
        
        // Update room status to maintenance
        $stmt = $pdo->prepare("UPDATE Room SET Status = 'maintenance' WHERE RoomID = :roomID");
        $stmt->execute([':roomID' => $roomID]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cleanup request created successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateCleanupStatus() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['requestID']) || empty($data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Request ID and status are required']);
        return;
    }
    
    $requestID = $data['requestID'];
    $status = $data['status'];
    
    // Validate status
    $validStatuses = ['pending', 'in-progress', 'completed'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Update cleanup request
        $updateQuery = "UPDATE CleanupRequest SET Status = :status WHERE CleanupRequestID = :requestID";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([':status' => $status, ':requestID' => $requestID]);
        
        // If completed, update room status to available
        if ($status === 'completed') {
            $roomQuery = "SELECT RoomID FROM CleanupRequest WHERE CleanupRequestID = :requestID";
            $stmt = $pdo->prepare($roomQuery);
            $stmt->execute([':requestID' => $requestID]);
            $room = $stmt->fetch();
            
            if ($room) {
                $updateRoomQuery = "UPDATE Room SET Status = 'available' WHERE RoomID = :roomID";
                $stmt = $pdo->prepare($updateRoomQuery);
                $stmt->execute([':roomID' => $room['RoomID']]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cleanup request status updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>