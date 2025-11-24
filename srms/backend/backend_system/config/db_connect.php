<?php
// config/db_connect.php
$host = "127.0.0.1"; // Replace with your host
$port = "3306";
$dbname = "restaurant_db"; // Default DB name
$user = "root";   // Default user
$password = ""; // Replace with your DB password

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Database connected successfully!";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
