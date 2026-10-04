<?php
session_start();
include "db.php";

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "driver") {
    header("Location: login.html");
    exit();
}

$driver_username = $_SESSION["username"];
$message = "";

// Driver ID fetch pannuvom
$d_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$d_stmt->bind_param("s", $driver_username);
$d_stmt->execute();
$driver_data = $d_stmt->get_result()->fetch_assoc();
$driver_id = $driver_data["id"];

// 1. RIDE ACCEPT LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_ride"])) {
    $b_id = intval($_POST["booking_id"]);
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Accepted', driver_id = ? WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("ii", $driver_id, $b_id);
    if ($stmt->execute()) {
        $message = "<p style='color: green;'>Ride accepted successfully!</p>";
    }
}

// 2. COMPLETE RIDE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["complete_ride"])) {
    $b_id = intval($_POST["booking_id"]);
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Completed' WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("ii", $b_id, $driver_id);
    if ($stmt->execute()) {
        $message = "<p style='color: green;'>Trip marked as Completed!</p>";
    }
}

// 3. SUBMIT CUSTOMER RATING (Driver rating user)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_user_rating"])) {
    $b_id   = intval($_POST["booking_id"]);
    $rating = intval($_POST["rating"]);
    $review = trim($_POST["review"]);

    $stmt = $conn->prepare("UPDATE bookings SET user_rating = ?, user_review = ? WHERE id = ? AND driver_id = ?");
    $stmt->bind_param("isii", $rating, $review, $b_id, $driver_id);
    if ($stmt->execute()) {
        $message = "<p style='color: green;'>Customer rating submitted!</p>";
    }
}

// New booking requests (Pending)
$pending_rides = $conn->query("SELECT * FROM bookings WHERE status = 'Pending' ORDER BY id DESC");

// This driver's active & completed trips
$my_trips_stmt = $conn->prepare("SELECT * FROM bookings WHERE driver_id = ? ORDER BY id DESC");
$my_trips_stmt->bind_param("i", $driver_id);
$my_trips_stmt->execute();
$my_trips = $my_trips_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard - Call Taxi</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .btn-accept { background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-complete { background: #2980b9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-rate { background: #e67e22; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .rating-box { background: #fdfefe; border: 1px dashed #e67e22; padding: 6px; border-radius: 5px; display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <h2>Driver Panel</h2>
            <a href="driver-dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="content">
            <h1>Driver Dashboard</h1>
            <p>Welcome, <b><?php echo htmlspecialchars($driver_username); ?></b>!</p>
            <?php echo $message; ?>

            <!-- PENDING RIDES TO ACCEPT -->
            <h2>🚖 Available Ride Requests</h2>
            <?php if ($pending_rides && $pending_rides->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Distance</th>
                            <th>Fare</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $pending_rides->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $r["id"]; ?></td>
                                <td><?php echo htmlspecialchars($r["username"]); ?></td>
                                <td><?php echo htmlspecialchars($r["pickup"]); ?></td>
                                <td><?php echo htmlspecialchars($r["dropoff"]); ?></td>
                                <td><?php echo htmlspecialchars($r["distance_km"]); ?> KM</td>
                                <td><b>₹<?php echo number_format($r["fare"], 2); ?></b></td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $r["id"]; ?>">
                                        <button type="submit" name="accept_ride" class="btn-accept">Accept Ride</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#777;">No new ride requests right now.</p>
            <?php endif; ?>

            <hr style="margin: 30px 0;">

            <!-- MY TRIPS & RATINGS -->
            <h2>📋 My Assigned Trips & Reviews</h2>
            <?php if ($my_trips && $my_trips->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Customer Rating</th>
                            <th>Action / Rate User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $my_trips->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $t["id"]; ?></td>
                                <td><?php echo htmlspecialchars($t["username"]); ?></td>
                                <td><?php echo htmlspecialchars($t["pickup"]); ?> ➔ <?php echo htmlspecialchars($t["dropoff"]); ?></td>
                                <td>₹<?php echo number_format($t["fare"], 2); ?></td>
                                <td><b><?php echo htmlspecialchars($t["status"]); ?></b></td>
                                
                                <!-- Customer kudutha Rating & Review display aagum -->
                                <td>
                                    <?php if (!empty($t["driver_rating"])): ?>
                                        <span style="color:#f39c12; font-weight:bold;">
                                            <?php echo str_repeat("⭐", $t["driver_rating"]); ?>
                                        </span>
                                        <?php if (!empty($t["driver_review"])): ?>
                                            <br><small style="color:#555;">"<?php echo htmlspecialchars($t["driver_review"]); ?>"</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small style="color:#888;">Not rated yet</small>
                                    <?php endif; ?>
                                </td>

                                <!-- Complete trip button matrum Customer-ku Rating kudukkum box -->
                                <td>
                                    <?php if ($t["status"] === "Accepted"): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="booking_id" value="<?php echo $t["id"]; ?>">
                                            <button type="submit" name="complete_ride" class="btn-complete">Finish Trip</button>
                                        </form>
                                    <?php elseif ($t["status"] === "Completed"): ?>
                                        <?php if (empty($t["user_rating"])): ?>
                                            <div class="rating-box">
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $t["id"]; ?>">
                                                    <select name="rating" required style="padding: 2px;">
                                                        <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                                        <option value="4">⭐⭐⭐⭐ (4)</option>
                                                        <option value="3">⭐⭐⭐ (3)</option>
                                                        <option value="2">⭐⭐ (2)</option>
                                                        <option value="1">⭐ (1)</option>
                                                    </select>
                                                    <input type="text" name="review" placeholder="User review" style="padding: 2px; width: 90px;">
                                                    <button type="submit" name="submit_user_rating" class="btn-rate">Rate</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#27ae60; font-size:12px; font-weight:bold;">
                                                You Rated: <?php echo str_repeat("⭐", $t["user_rating"]); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#777;">No assigned trips found.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>