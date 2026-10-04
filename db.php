<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'call_taxi_db';
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_init();
if (!$conn) {
    die("Database init failed");
}

if (!$conn->real_connect($host, $user, $pass, $name, (int)$port)) {
    die("Database Connection failed: " . mysqli_connect_error());
}
?>
