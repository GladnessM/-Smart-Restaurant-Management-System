<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';


try {
$stmt = $pdo->query("SELECT * FROM orders ORDER BY timestamp DESC");
$orders = $stmt->fetchAll();


foreach ($orders as &$order) {
$order['order_items'] = json_decode($order['order_items'], true);
}


echo json_encode($orders);


} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => 'Failed to load orders']);
}