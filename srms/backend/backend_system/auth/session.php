<?php
session_start();
require_once 'db.php';

$currentUser = null;
if(isset($_SESSION['customer_id'])){
    $stmt = $pdo->prepare("SELECT id, full_name, points FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $currentUser = $stmt->fetch();
}
?>
