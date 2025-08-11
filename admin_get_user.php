<?php
require_once 'db.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT u.UserID as id, u.Fullname as name, u.Email as email, 
                      r.RoleName as role
                      FROM users u JOIN role r ON u.Role = r.RoleID 
                      WHERE u.UserID = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>