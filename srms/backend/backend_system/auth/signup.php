<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$username = trim($data['username']);
$password = password_hash($data['password'], PASSWORD_BCRYPT); // hash password

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);

    echo json_encode(['success' => true, 'message' => 'User created']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Username already exists']);
}
