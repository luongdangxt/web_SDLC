<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'You need to login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['bookingID'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$userID = $_SESSION['user']['id'];
$bookingID = $data['bookingID'];
$checkoutTime = $data['checkoutTime'] ?? date('Y-m-d H:i:s'); // Default to now

try {
    $pdo->beginTransaction();
    
    // Kiểm tra booking có thuộc về user này không và đã check-in chưa
    $stmt = $pdo->prepare("
        SELECT BookingID, Status, RoomID, CheckoutDate, TotalAmount 
        FROM booking 
        WHERE BookingID = :bookingID AND UserID = :userID
    ");
    $stmt->execute([
        ':bookingID' => $bookingID,
        ':userID' => $userID
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found or you do not have permission');
    }
    
    // Kiểm tra trạng thái phải là checked-in
    if ($booking['Status'] !== 'checked-in') {
        throw new Exception('You can only request checkout for bookings that are currently checked-in');
    }
    
    // Kiểm tra có đã request checkout chưa
    $stmt = $pdo->prepare("
        SELECT RequestID FROM checkout_requests 
        WHERE BookingID = :bookingID AND Status IN ('pending', 'processing')
    ");
    $stmt->execute([':bookingID' => $bookingID]);
    
    if ($stmt->fetch()) {
        throw new Exception('A checkout request for this booking is already pending');
    }
    
    // Tạo checkout request
    $stmt = $pdo->prepare("
        INSERT INTO checkout_requests (BookingID, UserID, RequestedCheckoutTime, Status, CreatedAt) 
        VALUES (:bookingID, :userID, :checkoutTime, 'pending', NOW())
    ");
    $stmt->execute([
        ':bookingID' => $bookingID,
        ':userID' => $userID,
        ':checkoutTime' => $checkoutTime
    ]);
    
    $requestID = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Checkout request submitted successfully. Reception will process your request shortly.',
        'requestID' => $requestID,
        'bookingID' => $bookingID
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("User checkout request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("User checkout request DB error: " . $e->getMessage());
    
    // Check if table doesn't exist and create it
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS checkout_requests (
                    RequestID INT AUTO_INCREMENT PRIMARY KEY,
                    BookingID INT NOT NULL,
                    UserID INT NOT NULL,
                    RequestedCheckoutTime DATETIME NOT NULL,
                    Status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
                    Notes TEXT,
                    ProcessedBy INT NULL,
                    ProcessedAt DATETIME NULL,
                    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (BookingID) REFERENCES booking(BookingID),
                    FOREIGN KEY (UserID) REFERENCES users(UserID)
                )
            ");
            echo json_encode(['success' => false, 'message' => 'Database table created. Please try again.']);
        } catch (PDOException $createError) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>