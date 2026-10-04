<?php
session_start();
include "db.php";

// Set Indian Standard Timezone for auto time
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION["username"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET["id"])) {
    die("Booking ID missing.");
}

$booking_id = intval($_GET["id"]);
$session_user = $_SESSION["username"];
$session_role = $_SESSION["role"];

// Fetch booking + driver info
$stmt = $conn->prepare("
    SELECT b.*, u.fullname AS driver_fullname, u.mobile AS driver_phone, u.vehicle_name, u.vehicle_no 
    FROM bookings b 
    LEFT JOIN users u ON b.driver_id = u.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invoice not found.");
}

$b = $result->fetch_assoc();

// Security: User can only access their own invoice
if ($session_role !== "admin" && $b["username"] !== $session_user) {
    die("Access denied.");
}

// Security: Trip 'Completed' aagalena invoice open aagathu
if ($b["status"] !== "Completed") {
    die("<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>
            <h2 style='color: #e67e22;'>Invoice Not Ready</h2>
            <p>Your ride status is currently <b>" . htmlspecialchars($b["status"]) . "</b>.<br>
            Invoice will only be generated after the trip is Completed.</p>
            <a href='my-bookings.php' style='color: #1f4e79;'>Back to My Bookings</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $b["id"]; ?> - QuickTaxi</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            padding: 30px;
            color: #333;
        }
        .invoice-card {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1f4e79;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1f4e79;
            margin: 0;
            font-size: 26px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
            font-size: 14px;
        }
        .details-grid div p {
            margin: 4px 0;
        }
        .route-box {
            background: #f8fbfd;
            border: 1px solid #d9e6f2;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-fare {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table-fare th, .table-fare td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .table-fare th {
            background: #f5f8fa;
            color: #555;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
        }
        .btn-print {
            background: #1f4e79;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
        }
        .btn-print:hover {
            background: #153452;
        }
        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="header">
        <div>
            <h1>🚖 QuickTaxi</h1>
            <small>Official Ride Receipt</small>
        </div>
        <!-- AUTOMATIC REAL-TIME CLOCK & DATE -->
        <div style="text-align: right;">
            <p style="margin: 2px 0;"><strong>Invoice #:</strong> QT-<?php echo str_pad($b["id"], 5, '0', STR_PAD_LEFT); ?></p>
            <p style="margin: 2px 0;"><strong>Generated Date:</strong> <?php echo date("d-m-Y"); ?></p>
            <p style="margin: 2px 0;"><strong>Generated Time:</strong> <?php echo date("h:i:s A"); ?></p>
        </div>
    </div>

    <div class="details-grid">
        <div>
            <h4 style="margin: 0 0 8px 0; color: #1f4e79;">Customer Details:</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($b["username"]); ?></p>
            <p><strong>Booking Date & Time:</strong> <?php echo date('d-m-Y h:i A', strtotime($b["pickup_time"])); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($b["payment_method"] ?? 'Cash'); ?></p>
        </div>
        <div>
            <h4 style="margin: 0 0 8px 0; color: #1f4e79;">Driver & Vehicle Details:</h4>
            <p><strong>Driver:</strong> <?php echo $b["driver_fullname"] ? htmlspecialchars($b["driver_fullname"]) : 'Assigned Pilot'; ?></p>
            <p><strong>Vehicle:</strong> <?php echo $b["vehicle_name"] ? htmlspecialchars($b["vehicle_name"]) : 'Standard Cab'; ?></p>
            <p><strong>Plate No:</strong> <?php echo $b["vehicle_no"] ? htmlspecialchars($b["vehicle_no"]) : 'N/A'; ?></p>
        </div>
    </div>

    <div class="route-box">
        <p style="margin: 0 0 6px 0;">📍 <strong>Pickup:</strong> <?php echo htmlspecialchars($b["pickup"]); ?></p>
        <p style="margin: 0;">🏁 <strong>Dropoff:</strong> <?php echo htmlspecialchars($b["dropoff"]); ?></p>
    </div>

    <table class="table-fare">
        <thead>
            <tr>
                <th>Description</th>
                <th>Distance</th>
                <th>Rate / Unit</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Base Booking Fee</td>
                <td>-</td>
                <td>Flat Fee</td>
                <td>₹50.00</td>
            </tr>
            <tr>
                <td>Distance Fare</td>
                <td><?php echo htmlspecialchars($b["distance_km"]); ?> KM</td>
                <td>₹15.00 / KM</td>
                <td>₹<?php echo number_format($b["distance_km"] * 15, 2); ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Grand Total:</td>
                <td>₹<?php echo number_format($b["fare"], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 30px;" class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</div>

</body>
</html>
