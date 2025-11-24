<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['id'])) {
http_response_code(400);
echo json_encode(['error' => 'Missing menu item ID']);
exit;
}


$id = (int)$data['id'];


try {
$stmt = $pdo->prepare(
"UPDATE menu_items SET name=?, description=?, price=?, category=?, image=? WHERE id=?"
);


$stmt->execute([
$data['name'] ?? '',
$data['description'] ?? '',
$data['price'] ?? 0,
$data['category'] ?? '',
$data['image'] ?? '',
$id
]);


echo json_encode(['success' => true]);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Failed to update item']);
}