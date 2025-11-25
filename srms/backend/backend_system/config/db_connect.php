<?php
header("Content-Type: application/json");

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "restaurant";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "error" => $conn->connect_error
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Database connected successfully!"
]);
?>
