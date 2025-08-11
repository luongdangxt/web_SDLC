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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['bookingID'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$bookingId = $data['bookingID'];
$action = $data['action'] ?? ($data['status'] ?? null);
$action = is_string($action) ? strtolower($action) : null;

// Map status values to actions for compatibility
if ($action === 'confirmed') { $action = 'confirm'; }
if ($action === 'checked-in' || $action === 'checkedin') { $action = 'checkin'; }
if ($action === 'completed') { $action = 'checkout'; }
if ($action === 'cancelled') { $action = 'cancel'; }
$amount = $data['amount'] ?? 0;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Missing action/status']);
    exit;
}

try {
    // Get current booking info
    $stmt = $pdo->prepare("SELECT RoomID, Status as CurrentStatus FROM Booking WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    switch ($action) {
        case 'confirm':
            // Confirm booking
            if ($booking['CurrentStatus'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending bookings can be confirmed']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE Booking SET Status = 'confirmed', UpdatedAt = NOW() WHERE BookingID = :bookingID");
            $stmt->execute([':bookingID' => $bookingId]);
            
            break;
            
        case 'checkin':
            // Check-in guest
            if ($booking['CurrentStatus'] !== 'confirmed') {
                echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be checked in']);
                exit;
            }
            
            // Update booking status
            $stmt = $pdo->prepare("UPDATE Booking SET Status = 'checked-in', UpdatedAt = NOW() WHERE BookingID = :bookingID");
            $stmt->execute([':bookingID' => $bookingId]);
            
            // Update room status to occupied
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'occupied' WHERE RoomID = :roomID");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            
            // Create cleanup request for future checkout
            $stmt = $pdo->prepare("
                INSERT INTO CleanupRequest (RoomID, RequestTime, Status, CreatedAt) 
                VALUES (:roomID, NOW(), 'pending', NOW())
                ON DUPLICATE KEY UPDATE RequestTime = NOW()
            ");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            
            break;
            
        case 'checkout':
            // Check-out guest
            if ($booking['CurrentStatus'] !== 'checked-in') {
                echo json_encode(['success' => false, 'message' => 'Only checked-in bookings can be checked out']);
                exit;
            }
            
            // Update booking status
            $stmt = $pdo->prepare("UPDATE Booking SET Status = 'completed', UpdatedAt = NOW() WHERE BookingID = :bookingID");
            $stmt->execute([':bookingID' => $bookingId]);
            
            // Update room status to maintenance for cleaning
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'maintenance' WHERE RoomID = :roomID");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            
            // Create payment record if amount provided
            if ($amount > 0) {
                $stmt = $pdo->prepare("INSERT INTO Payment (BookingID, Amount, PaymentMethod, Status, CreatedAt) VALUES (?, ?, 'cash', 'completed', NOW())");
                $stmt->execute([$bookingId, $amount]);
            }
            
            break;
            
        case 'cancel':
            // Cancel booking
            if (!in_array($booking['CurrentStatus'], ['pending', 'confirmed'])) {
                echo json_encode(['success' => false, 'message' => 'Only pending or confirmed bookings can be cancelled']);
                exit;
            }
            
            // Update booking status
            $stmt = $pdo->prepare("UPDATE Booking SET Status = 'cancelled', UpdatedAt = NOW() WHERE BookingID = :bookingID");
            $stmt->execute([':bookingID' => $bookingId]);
            
            // Update room status to available
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'available' WHERE RoomID = :roomID");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking updated successfully'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reception update booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reception update booking DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>