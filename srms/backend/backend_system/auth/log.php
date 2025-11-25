<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json');

require_once '../config/db_config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing login fields']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

$stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['customerid'] = $user['id'];
    

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'points' => $user['points'],
        'table_number' => $user['table_number'] ?? null
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password']);
}
