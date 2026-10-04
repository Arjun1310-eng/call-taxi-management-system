<?php
session_start();
if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
    <h2>User Panel</h2>
    <a href="book-taxi.php">Book Taxi</a>
    <a href="my-bookings.php">My Bookings</a>
    <a href="logout.php">Logout</a>
</aside>

        <main class="content">
            <h1>Welcome User, <?php echo $_SESSION["username"]; ?></h1>
            <p>You can book taxis and view your booking history here.</p>
        </main>
    </div>
</body>
</html>