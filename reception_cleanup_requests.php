<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền Reception
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Reception') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all cleanup requests
        $status = $_GET['status'] ?? 'all';
        
        $whereClause = '';
        $params = [];
        
        if ($status !== 'all') {
            $whereClause = 'WHERE cr.Status = :status';
            $params[':status'] = $status;
        }

        $query = "
            SELECT 
                cr.CleanupRequestID,
                cr.RoomID,
                r.RoomNumber,
                rt.TypeName as RoomType,
                cr.RequestTime,
                cr.Status,
                cr.CreatedAt,
                -- Get checkout info if available
                b.BookingID,
                u.Fullname as LastGuestName,
                b.CheckoutDate
            FROM cleanuprequest cr
            JOIN room r ON cr.RoomID = r.RoomID
            JOIN roomtype rt ON r.RoomTypeID = rt.RoomTypeID
            LEFT JOIN booking b ON cr.RoomID = b.RoomID 
                AND b.Status = 'completed'
                AND DATE(b.CheckoutDate) = DATE(cr.RequestTime)
            LEFT JOIN users u ON b.UserID = u.UserID
            $whereClause
            ORDER BY cr.RequestTime DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data
        foreach ($requests as &$request) {
            $requestTime = new DateTime($request['RequestTime']);
            $request['RequestTime_formatted'] = $requestTime->format('M d, Y H:i');
            
            if ($request['CheckoutDate']) {
                $checkoutTime = new DateTime($request['CheckoutDate']);
                $request['CheckoutDate_formatted'] = $checkoutTime->format('M d, Y H:i');
            }

            // Status classes
            switch ($request['Status']) {
                case 'pending':
                    $request['StatusClass'] = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'in_progress':
                    $request['StatusClass'] = 'bg-blue-100 text-blue-800';
                    break;
                case 'completed':
                    $request['StatusClass'] = 'bg-green-100 text-green-800';
                    break;
                default:
                    $request['StatusClass'] = 'bg-gray-100 text-gray-800';
            }
        }

        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'total' => count($requests)
        ]);

    } elseif ($method === 'POST') {
        // Create new cleanup request
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['roomID'])) {
            echo json_encode(['success' => false, 'message' => 'Room ID is required']);
            exit;
        }

        // Check if room already has pending cleanup request
        $checkQuery = "SELECT CleanupRequestID FROM cleanuprequest WHERE RoomID = :roomID AND Status = 'pending'";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([':roomID' => $data['roomID']]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Room already has pending cleanup request']);
            exit;
        }

        // Create cleanup request
        $insertQuery = "INSERT INTO cleanuprequest (RoomID, RequestTime, Status, CreatedAt) VALUES (:roomID, NOW(), 'pending', NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([':roomID' => $data['roomID']]);

        $requestID = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Cleanup request created successfully',
            'requestID' => $requestID
        ]);

    } elseif ($method === 'PUT') {
        // Update cleanup request status
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['requestID']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Request ID and status are required']);
            exit;
        }

        $validStatuses = ['pending', 'in_progress', 'completed'];
        if (!in_array($data['status'], $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        // Update cleanup request
        $updateQuery = "UPDATE cleanuprequest SET Status = :status WHERE CleanupRequestID = :requestID";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([
            ':status' => $data['status'],
            ':requestID' => $data['requestID']
        ]);

        // If completed, set room status to available
        if ($data['status'] === 'completed') {
            $roomQuery = "SELECT RoomID FROM cleanuprequest WHERE CleanupRequestID = :requestID";
            $roomStmt = $pdo->prepare($roomQuery);
            $roomStmt->execute([':requestID' => $data['requestID']]);
            $room = $roomStmt->fetch();

            if ($room) {
                $updateRoomQuery = "UPDATE room SET Status = 'available' WHERE RoomID = :roomID";
                $updateRoomStmt = $pdo->prepare($updateRoomQuery);
                $updateRoomStmt->execute([':roomID' => $room['RoomID']]);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Cleanup request updated successfully'
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    error_log("Reception cleanup requests error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>