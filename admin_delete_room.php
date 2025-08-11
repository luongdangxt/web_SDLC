<?php
require_once 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' || !$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$roomID = $input['roomID'] ?? 0;

if (empty($roomID)) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

try {
    // Check room exists
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE RoomID = ?');
    $stmt->execute([$roomID]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Room does not exist']);
        exit;
    }
    $pdo->beginTransaction();
    // First delete images
    $stmt = $pdo->prepare('DELETE FROM room_images WHERE RoomID = ?');
    $stmt->execute([$roomID]);
    // Then delete room
    $stmt = $pdo->prepare('DELETE FROM rooms WHERE RoomID = ?');
    $stmt->execute([$roomID]);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>