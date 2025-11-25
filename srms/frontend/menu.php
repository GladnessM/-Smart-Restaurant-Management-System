<?php
session_start();
require_once '../config/db_config.php';

// Check if user is logged in
$currentUser = null;
if(isset($_SESSION['customer_id'])){
    $stmt = $pdo->prepare("SELECT id, full_name, points FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $currentUser = $stmt->fetch();
}

// Fetch menu items from database
$stmt = $pdo->query("SELECT id, name, price, image, category FROM menu ORDER BY category, name");
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pass data to HTML via JSON
$menuJSON = json_encode($menuItems);
$currentUserJSON = json_encode($currentUser);

?>
<!-- Redirect to HTML file -->
<?php include 'menu.html'; ?>
