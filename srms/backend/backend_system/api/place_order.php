<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['table_no']) || !isset($data['order_items']) || !is_array($data['order_items'])) {
http_response_code(400);
echo json_encode(['error' => 'Invalid request body']);
exit;
}


$table_no = trim($data['table_no']);
$order_items = $data['order_items'];
$total_amount = 0;


try {
// Calculate total
foreach ($order_items as $item) {
if (!isset($item['menu_id'], $item['quantity'])) continue;


$stmt = $pdo->prepare("SELECT price FROM menu_items WHERE id = ?");
$stmt->execute([$item['menu_id']]);
$menuItem = $stmt->fetch();


if ($menuItem) {
$total_amount += $menuItem['price'] * (int)$item['quantity'];
}
}


// Insert order
$insert = $pdo->prepare(
"INSERT INTO orders (table_no, order_items, total_amount) VALUES (?, ?, ?)"
);
$insert->execute([$table_no, json_encode($order_items), $total_amount]);


echo json_encode([
'success' => true,
'order_id' => $pdo->lastInsertId(),
'total' => $total_amount,
'status' => 'pending'
]);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Unable to place order']);
}