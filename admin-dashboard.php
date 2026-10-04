<?php
session_start();
include "db.php";

// Auth check
if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.html");
    exit();
}

// Fetch all drivers for the assignment dropdown
$drivers_query = "SELECT id, fullname, username FROM users WHERE role = 'driver'";
$drivers_result = $conn->query($drivers_query);
$drivers = [];
if ($drivers_result) {
    while ($d = $drivers_result->fetch_assoc()) {
        $drivers[] = $d;
    }
}

// Handle Status Updates & Driver Assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_booking"])) {
    $booking_id = intval($_POST["booking_id"]);
    $action     = $_POST["action"];
    $driver_id  = !empty($_POST["driver_id"]) ? intval($_POST["driver_id"]) : NULL;

    if ($action === "approve") {
        $status = "Approved";
        $stmt = $conn->prepare("UPDATE bookings SET status = ?, driver_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $driver_id, $booking_id);
        $stmt->execute();
    } elseif ($action === "cancel") {
        $status = "Cancelled";
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
    }

    header("Location: admin-dashboard.php");
    exit();
}

// Fetch all bookings with driver details
$query = "SELECT b.*, u.fullname AS driver_name 
          FROM bookings b 
          LEFT JOIN users u ON b.driver_id = u.id 
          ORDER BY b.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Call Taxi Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <a href="admin-dashboard.php">Manage Bookings</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="content">
            <h1>Admin Dashboard</h1>
            <p>Manage bookings and assign drivers below:</p>

            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Pickup Time</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["username"]); ?></td>
                                <td><?php echo htmlspecialchars($row["pickup"]); ?></td>
                                <td><?php echo htmlspecialchars($row["dropoff"]); ?></td>
                                <td><?php echo htmlspecialchars($row["pickup_time"]); ?></td>
                                <td><?php echo htmlspecialchars($row["driver_name"] ?? "Not Assigned"); ?></td>
                                <td class="status-<?php echo htmlspecialchars($row["status"]); ?>">
                                    <?php echo htmlspecialchars($row["status"]); ?>
                                </td>
                                <td>
                                    <?php if ($row["status"] === "Pending"): ?>
                                        <form method="post" action="admin-dashboard.php" style="padding:0; background:none; box-shadow:none; display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $row["id"]; ?>">
                                            <select name="driver_id" style="width:auto; padding:4px; margin:0 4px;" required>
                                                <option value="">Select Driver</option>
                                                <?php foreach ($drivers as $drv): ?>
                                                    <option value="<?php echo $drv["id"]; ?>"><?php echo htmlspecialchars($drv["fullname"]); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="action" value="cancel" class="btn-cancel">Cancel</button>
                                            <input type="hidden" name="update_booking" value="1">
                                        </form>
                                    <?php else: ?>
                                        <span>Completed / Actioned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No taxi bookings found yet.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>