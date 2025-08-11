<?php
require_once 'cors_headers.php';
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    // Check user credentials
    $stmt = $pdo->prepare("
        SELECT u.UserID, u.Username, u.Password, u.Email, u.Fullname, u.Role,
               r.RoleName
        FROM Users u
        JOIN Role r ON u.Role = r.RoleID
        WHERE u.Email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['Password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    // Set session data
    $_SESSION['user'] = [
        'id' => $user['UserID'],
        'username' => $user['Username'],
        'email' => $user['Email'],
        'fullname' => $user['Fullname'],
        'role' => $user['RoleName']
    ];

    // Update last login time
    $updateStmt = $pdo->prepare("UPDATE Users SET UpdatedAt = NOW() WHERE UserID = ?");
    $updateStmt->execute([$user['UserID']]);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['UserID'],
            'username' => $user['Username'],
            'email' => $user['Email'],
            'fullname' => $user['Fullname'],
            'role' => $user['RoleName']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>