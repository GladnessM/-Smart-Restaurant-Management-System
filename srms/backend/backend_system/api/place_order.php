<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db_config.php';

if(!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];
$table_id = $data['table_id'] ?? null;

if(empty($cart)) {
    http_response_code(400);
    echo json_encode(['error'=>'Cart is empty']);
    exit;
}

// Calculate total
$total = 0;
foreach($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

try {
    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, table_id, total) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['customer_id'], $table_id, $total]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach($cart as $item){
        $stmtItem->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
    }

    // Add loyalty points (e.g., 1 point per 100 Ksh)
    $pointsEarned = floor($total / 100);
    $stmt = $pdo->prepare("UPDATE customers SET points = points + ? WHERE id = ?");
    $stmt->execute([$pointsEarned, $_SESSION['customer_id']]);

    $pdo->commit();

    echo json_encode(['success'=>true, 'pointsEarned'=>$pointsEarned]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}

// $data = json_decode(file_get_contents('php://input'), true);


// if (!isset($data['table_no']) || !isset($data['order_items']) || !is_array($data['order_items'])) {
// http_response_code(400);
// echo json_encode(['error' => 'Invalid request body']);
// exit;
// }


// $table_no = trim($data['table_no']);
// $order_items = $data['order_items'];
// $total_amount = 0;


// try {
// // Calculate total
// foreach ($order_items as $item) {
// if (!isset($item['menu_id'], $item['quantity'])) continue;


// $stmt = $pdo->prepare("SELECT price FROM menu_items WHERE id = ?");
// $stmt->execute([$item['menu_id']]);
// $menuItem = $stmt->fetch();


// if ($menuItem) {
// $total_amount += $menuItem['price'] * (int)$item['quantity'];
// }
// }


// // Insert order
// $insert = $pdo->prepare(
// "INSERT INTO orders (table_no, order_items, total_amount) VALUES (?, ?, ?)"
// );
// $insert->execute([$table_no, json_encode($order_items), $total_amount]);


// echo json_encode([
// 'success' => true,
// 'order_id' => $pdo->lastInsertId(),
// 'total' => $total_amount,
// 'status' => 'pending'
// ]);


// } catch (Exception $e) {
// http_response_code(500);
// echo json_encode(['error' => 'Unable to place order']);
// }

