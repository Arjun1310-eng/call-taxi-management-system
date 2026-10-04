<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"]);
    $mobile   = trim($_POST["mobile"]);
    $email    = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role     = "user";

    // Insert new user into database
    $stmt = $conn->prepare("INSERT INTO users (fullname, mobile, email, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        die("Query Preparation Failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $fullname, $mobile, $email, $username, $password, $role);

    if ($stmt->execute()) {
        // Registration successful -> Redirect to login page
        header("Location: login.html");
        exit();
    } else {
        $message = "Registration failed: " . $stmt->error;
    }
}
?>
<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname     = trim($_POST["fullname"]);
    $mobile       = trim($_POST["mobile"]);
    $email        = trim($_POST["email"]);
    $username     = trim($_POST["username"]);
    $password     = trim($_POST["password"]);
    $role         = trim($_POST["role"]);
    $vehicle_name = isset($_POST["vehicle_name"]) ? trim($_POST["vehicle_name"]) : null;
    $vehicle_no   = isset($_POST["vehicle_no"]) ? trim($_POST["vehicle_no"]) : null;

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        echo "<script>alert('Username already taken! Please choose another.'); window.location.href='login.html';</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Drivers start as 'Pending', regular Customers start as 'Approved'
    $approval_status = ($role === "driver") ? "Pending" : "Approved";

    $stmt = $conn->prepare("INSERT INTO users (fullname, mobile, email, username, password, role, vehicle_name, vehicle_no, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $fullname, $mobile, $email, $username, $hashed_password, $role, $vehicle_name, $vehicle_no, $approval_status);

    if ($stmt->execute()) {
        if ($role === "driver") {
            echo "<script>alert('Driver registration submitted! Please wait for Admin approval before logging in.'); window.location.href='login.html';</script>";
        } else {
            echo "<script>alert('Account created successfully! You can now log in.'); window.location.href='login.html';</script>";
        }
    } else {
        echo "Registration error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Call Taxi Management - Register</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <h1>Create Account</h1>
            <p>Register to book a taxi</p>

            <?php if (!empty($message)): ?>
                <p style="color: red;"><?php echo $message; ?></p>
            <?php endif; ?>

            <form action="register.php" method="post">
                <input type="text" name="fullname" placeholder="Full Name" required />
                <input type="text" name="mobile" placeholder="Mobile Number" required />
                <input type="email" name="email" placeholder="Email Address" required />
                <input type="text" name="username" placeholder="Username" required />
                <input type="password" name="password" placeholder="Password" required />

                <button type="submit">Register</button>
            </form>
            <p style="margin-top: 15px;">Already have an account? <a href="login.html">Login here</a></p>
        </div>
    </div>
</body>
</html>