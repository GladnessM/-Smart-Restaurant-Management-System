<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing login fields']);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'role' => $user['role']
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
}
