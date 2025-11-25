<?php
header('Content-Type: application/json');
require_once '../config/db_config.php';

$stmt = $pdo->query("SELECT id, name, category, price, img_url AS img FROM menu_items ORDER BY category, id");
$menu = $stmt->fetchAll();

echo json_encode($menu);
?>

// $category = isset($_GET['category']) ? trim($_GET['category']) : '';


// try {
// if ($category) {
// $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category = ?");
// $stmt->execute([$category]);
// } else {
// $stmt = $pdo->query("SELECT * FROM menu_items");
// }


// echo json_encode($stmt->fetchAll());


// } catch (Exception $e) {
// http_response_code(500);
// echo json_encode(['error' => 'Failed to fetch menu']);
// }