<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "db.php";

$username = "admin";
$password = "admin123";
$role     = "admin";

// Generate password hash on your exact PHP environment
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Clean up old admin account if present
$conn->query("DELETE FROM users WHERE username = '$username'");

// Insert fresh admin account
$stmt = $conn->prepare("INSERT INTO users (fullname, mobile, email, username, password, role) VALUES ('System Admin', '9999999999', 'admin@example.com', ?, ?, ?)");
$stmt->bind_param("sss", $username, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<h2 style='color: green;'>Admin created successfully!</h2>";
    echo "<p><b>Username:</b> admin</p>";
    echo "<p><b>Password:</b> admin123</p>";
    echo "<p><a href='login.html'>Click here to go to Login</a></p>";
} else {
    echo "<h2 style='color: red;'>Error: " . $stmt->error . "</h2>";
}
?>