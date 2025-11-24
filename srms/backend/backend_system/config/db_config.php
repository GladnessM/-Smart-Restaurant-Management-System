<?php
// config/db_config.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'restaurant_db'; // Database name for your XAMPP setup
$DB_USER = 'root';             // Default XAMPP MySQL user
$DB_PASS = '';                 // Default XAMPP MySQL password

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // It's better to log the error than to echo it in production
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}
?>