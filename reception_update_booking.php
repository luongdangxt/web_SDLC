<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra quyền Reception hoặc Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Reception', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['bookingID']) || empty($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get booking details first
    $stmt = $pdo->prepare("SELECT RoomID, Status as CurrentStatus FROM booking WHERE BookingID = :bookingID");
    $stmt->execute([':bookingID' => $data['bookingID']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Validate status transition
    $validTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['checked-in', 'cancelled'],
        'checked-in' => ['completed'],
        'completed' => [],
        'cancelled' => []
    ];
    
    if (!in_array($data['status'], $validTransitions[$booking['CurrentStatus']] ?? [])) {
        throw new Exception('Invalid status transition');
    }
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE booking 
        SET Status = :status, UpdatedAt = NOW() 
        WHERE BookingID = :bookingID
    ");
    $stmt->execute([
        ':status' => $data['status'],
        ':bookingID' => $data['bookingID']
    ]);
    
    // Handle room status updates based on booking status
    switch ($data['status']) {
        case 'checked-in':
            // Set room as occupied
            $stmt = $pdo->prepare("UPDATE room SET Status = 'occupied' WHERE RoomID = :roomID");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            break;
            
        case 'completed':
            // Create cleanup request when guest checks out
            $stmt = $pdo->prepare("
                INSERT INTO cleanuprequest (RoomID, RequestTime, Status, CreatedAt) 
                VALUES (:roomID, NOW(), 'pending', NOW())
                ON DUPLICATE KEY UPDATE RequestTime = NOW()
            ");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            
            // Set room status to needs cleaning
            $stmt = $pdo->prepare("UPDATE room SET Status = 'maintenance' WHERE RoomID = :roomID");
            $stmt->execute([':roomID' => $booking['RoomID']]);
            break;
            
        case 'cancelled':
            // If was checked-in, make room available
            if ($booking['CurrentStatus'] === 'checked-in') {
                $stmt = $pdo->prepare("UPDATE room SET Status = 'available' WHERE RoomID = :roomID");
                $stmt->execute([':roomID' => $booking['RoomID']]);
            }
            break;
    }
    
    // Record payment if provided
    if (isset($data['paymentMethod']) && isset($data['amount']) && $data['status'] === 'completed') {
        $stmt = $pdo->prepare("
            INSERT INTO payment (BookingID, Amount, PaymentMethod, Status, CreatedAt) 
            VALUES (:bookingID, :amount, :paymentMethod, 'completed', NOW())
        ");
        $stmt->execute([
            ':bookingID' => $data['bookingID'],
            ':amount' => $data['amount'],
            ':paymentMethod' => $data['paymentMethod']
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully',
        'newStatus' => $data['status']
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Reception update booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Reception update booking DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>