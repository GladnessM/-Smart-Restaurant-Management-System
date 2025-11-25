<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Handle Preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// 3. Suppress HTML warnings that break JSON
error_reporting(0); 
ini_set('display_errors', 0);


require_once '../config/db_config.php';



//$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    

    $sql = "INSERT INTO customers (full_name, email, password_hash)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $pass);

    if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Email already exists or invalid input']);
    }
}