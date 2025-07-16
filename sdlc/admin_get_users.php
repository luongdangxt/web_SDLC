<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT u.UserID as id, u.Fullname as name, u.Email as email, 
                          r.RoleName as role
                          FROM user_ u JOIN role r ON u.Role = r.RoleID");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>