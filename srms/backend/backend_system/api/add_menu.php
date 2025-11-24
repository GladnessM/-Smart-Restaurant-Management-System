<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['name'], $data['price'])) {
http_response_code(400);
echo json_encode(['error' => 'Required fields missing']);
exit;
}


try {
$stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
trim($data['name']),
$data['description'] ?? '',
$data['price'],
$data['category'] ?? '',
$data['image'] ?? ''
]);


echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Failed to add menu item']);
}