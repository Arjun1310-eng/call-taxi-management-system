<?php
session_start();
include "db.php";

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: login.html");
    exit();
}

$username = $_SESSION["username"];
$message = "";

// 1. CANCEL BOOKING LOGIC (Pending-la irundha mattum)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_booking"])) {
    $booking_id = intval($_POST["booking_id"]);

    $check_stmt = $conn->prepare("SELECT status FROM bookings WHERE id = ? AND username = ?");
    $check_stmt->bind_param("is", $booking_id, $username);
    $check_stmt->execute();
    $res = $check_stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row["status"] === "Pending") {
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
            $update_stmt->bind_param("i", $booking_id);
            if ($update_stmt->execute()) {
                $message = "<p style='color: green;'>Booking #{$booking_id} cancelled successfully.</p>";
            } else {
                $message = "<p style='color: red;'>Error cancelling booking.</p>";
            }
        } else {
            $message = "<p style='color: red;'>Cannot cancel: Ride is already " . htmlspecialchars($row["status"]) . ".</p>";
        }
    }
}

// 2. DRIVER RATING SUBMISSION LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_driver_rating"])) {
    $b_id   = intval($_POST["booking_id"]);
    $rating = intval($_POST["rating"]);
    $review = trim($_POST["review"]);

    $rate_stmt = $conn->prepare("UPDATE bookings SET driver_rating = ?, driver_review = ? WHERE id = ? AND username = ?");
    $rate_stmt->bind_param("isis", $rating, $review, $b_id, $username);
    if ($rate_stmt->execute()) {
        $message = "<p style='color: green;'>Thank you! Rating submitted successfully.</p>";
    }
}

// Fetch user bookings
$stmt = $conn->prepare("SELECT * FROM bookings WHERE username = ? ORDER BY id DESC");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Call Taxi Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .btn-cancel-ride {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        .btn-cancel-ride:hover { background-color: #c0392b; }
        .btn-invoice {
            text-decoration: none;
            display: inline-block;
            padding: 5px 10px;
            background: #1f4e79;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .btn-invoice:hover { background: #133350; }
        .status-Pending { color: #f39c12; font-weight: bold; }
        .status-Cancelled { color: #e74c3c; font-weight: bold; }
        .status-Accepted { color: #27ae60; font-weight: bold; }
        .status-Completed { color: #2980b9; font-weight: bold; }
        .rating-box {
            margin-top: 6px;
            padding: 5px;
            background: #fdfefe;
            border: 1px dashed #27ae60;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
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
            <h1>My Bookings</h1>
            <?php echo $message; ?>

            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pickup Location</th>
                            <th>Dropoff Location</th>
                            <th>Distance</th>
                            <th>Total Fare</th>
                            <th>Pickup Date & Time</th>
                            <th>Status</th>
                            <th>Actions & Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row["id"]; ?></td>
                                <td><?php echo htmlspecialchars($row["pickup"]); ?></td>
                                <td><?php echo htmlspecialchars($row["dropoff"]); ?></td>
                                <td><?php echo htmlspecialchars($row["distance_km"]); ?> KM</td>
                                <td><b>₹<?php echo number_format($row["fare"], 2); ?></b></td>
                                <td><?php echo htmlspecialchars($row["pickup_time"]); ?></td>
                                <td class="status-<?php echo htmlspecialchars($row["status"]); ?>">
                                    <?php echo htmlspecialchars($row["status"]); ?>
                                </td>
                                <td>
                                    <!-- 1. CANCEL BUTTON: Pending-la iruntha mattum theriyum -->
                                    <?php if ($row["status"] === "Pending"): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display:inline; margin: 0;">
                                            <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" name="cancel_booking" class="btn-cancel-ride">Cancel</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- 2. INVOICE BUTTON: Trip Completed aana piragu mattume varum -->
                                    <?php if ($row["status"] === "Completed"): ?>
                                        <a href="invoice.php?id=<?php echo $row["id"]; ?>" target="_blank" class="btn-invoice">
                                            🧾 Invoice
                                        </a>

                                        <!-- Rating Section -->
                                        <?php if (empty($row["driver_rating"])): ?>
                                            <div class="rating-box">
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                                    <select name="rating" required style="padding: 2px; font-size: 12px;">
                                                        <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                                        <option value="4">⭐⭐⭐⭐ (4)</option>
                                                        <option value="3">⭐⭐⭐ (3)</option>
                                                        <option value="2">⭐⭐ (2)</option>
                                                        <option value="1">⭐ (1)</option>
                                                    </select>
                                                    <input type="text" name="review" placeholder="Review" style="padding: 2px; font-size: 12px; width: 80px;">
                                                    <button type="submit" name="submit_driver_rating" style="background:#27ae60; color:white; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size: 11px;">Rate</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-top: 4px; color: #f39c12; font-weight: bold; font-size: 12px;">
                                                Rated: <?php echo str_repeat("⭐", $row["driver_rating"]); ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif ($row["status"] === "Cancelled"): ?>
                                        <span style="color: #e74c3c; font-size: 12px; font-weight: bold;">Cancelled</span>
                                    <?php else: ?>
                                        <span style="color: #777; font-size: 12px;">Trip in progress</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No bookings found. <a href="book-taxi.php">Book a taxi now</a>.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>