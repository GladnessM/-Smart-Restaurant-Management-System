<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($id <= 0) {
http_response_code(400);
echo json_encode(['error' => 'Invalid ID']);
exit;
}


try {
$stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
$stmt->execute([$id]);


echo json_encode(['success' => true]);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Failed to delete menu item']);
}