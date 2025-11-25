<?php
require_once '../config/db_config.php';
// Allow cross-origin requests from localhost
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'];
    $table_number = $_POST['table_number'];

    $sql = "UPDATE customers SET table_number=? WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $table_number, $email);
    // $stmt->execute();

    if($stmt->execute()){
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Failed to update table']);
    }
}
?>
