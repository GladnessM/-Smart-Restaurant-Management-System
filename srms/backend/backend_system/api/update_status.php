<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['order_id'], $data['status'])) {
http_response_code(400);
echo json_encode(['error' => 'Missing fields']);
exit;
}


$order_id = (int)$data['order_id'];
$status = trim($data['status']);
$allowed = ['pending','preparing','served','cancelled','completed'];


if (!in_array($status, $allowed)) {
http_response_code(400);
echo json_encode(['error' => 'Invalid status']);
exit;
}


try {
$stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute([$status, $order_id]);


echo json_encode(['success' => true]);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Failed to update order status']);
}