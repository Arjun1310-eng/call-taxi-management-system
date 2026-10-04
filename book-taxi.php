<?php
session_start();
include "db.php";

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: login.html");
    exit();
}

$message = "";

// Cumulative distances in KM from Manadu
$locations = [
    "Manadu"               => 0,
    "Paramankuruchi"       => 3,
    "Nalumulai kinaru"     => 8,
    "Tiruchendur"          => 13,
    "Virapandianpatanam"   => 15,
    "Kayalpatanam"         => 22,
    "Arumuganeri"          => 27,
    "Sagupuram"            => 30,
    "authoor"              => 35,
    "Mukani"               => 37,
    "Palayakayal"          => 42
];

// Cab Rates Per KM
$cab_rates = [
    "Mini"  => 12.00,
    "Sedan" => 15.00,
    "SUV"   => 20.00
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username    = $_SESSION["username"];
    $pickup      = trim($_POST["pickup"]);
    $dropoff     = trim($_POST["dropoff"]);
    $cab_type    = isset($_POST["cab_type"]) ? trim($_POST["cab_type"]) : "Sedan";
    $pickup_time = date('Y-m-d H:i:s');

    if ($pickup === $dropoff) {
        $message = "<p style='color: #e74c3c; font-weight: bold;'>Pickup and Drop-off locations cannot be the same!</p>";
    } elseif (!isset($locations[$pickup]) || !isset($locations[$dropoff])) {
        $message = "<p style='color: #e74c3c; font-weight: bold;'>Please select valid locations.</p>";
    } else {
        $distance_km = abs($locations[$dropoff] - $locations[$pickup]);
        $base_fare   = 50.00;
        $rate_per_km = isset($cab_rates[$cab_type]) ? $cab_rates[$cab_type] : 15.00;
        $fare        = $base_fare + ($distance_km * $rate_per_km);

        $stmt = $conn->prepare("INSERT INTO bookings (username, pickup, dropoff, distance_km, fare, pickup_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdds", $username, $pickup, $dropoff, $distance_km, $fare, $pickup_time);

        if ($stmt->execute()) {
            $message = "<div style='background: #eafaf1; border: 1px solid #27ae60; padding: 12px; border-radius: 6px; margin-bottom: 15px;'>
                            <h3 style='color: #27ae60; margin: 0 0 6px 0;'>🚖 Taxi Booked Successfully!</h3>
                            <p style='margin: 2px 0;'><b>Vehicle Type:</b> " . htmlspecialchars($cab_type) . "</p>
                            <p style='margin: 2px 0;'><b>Route:</b> " . htmlspecialchars($pickup) . " ➔ " . htmlspecialchars($dropoff) . " (" . $distance_km . " KM)</p>
                            <p style='margin: 2px 0;'><b>Estimated Fare:</b> ₹" . number_format($fare, 2) . "</p>
                        </div>";
        } else {
            $message = "<p style='color: #e74c3c;'>Booking failed: " . $stmt->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Taxi - Call Taxi Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .car-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 10px 0 18px 0;
        }
        .car-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .car-card:hover {
            border-color: #1f4e79;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }
        .car-card.selected {
            border-color: #1f4e79;
            background: #f0f7ff;
            box-shadow: 0 0 8px rgba(31, 78, 121, 0.3);
        }
        .car-card svg {
            display: block;
            margin: 0 auto 8px auto;
        }
        .car-card .capacity {
            font-size: 13px;
            font-weight: 600;
            color: #1f4e79;
            margin: 0;
        }
        .car-card .rate {
            font-size: 12px;
            color: #555;
            margin: 4px 0 0 0;
        }
        .fare-box {
            background: #f4f7f6;
            padding: 14px;
            border-radius: 8px;
            margin: 12px 0;
            border: 1px solid #dcdcdc;
        }
    </style>

    <script>
        const places = {
            "Manadu": 0,
            "Paramankuruchi": 3,
            "Nalumulai kinaru": 8,
            "Tiruchendur": 13,
            "Virapandianpatanam": 15,
            "Kayalpatanam": 22,
            "Arumuganeri": 27,
            "Sagupuram": 30,
            "authoor": 35,
            "Mukani": 37,
            "Palayakayal": 42
        };

        const cabRates = {
            "Mini": 12,
            "Sedan": 15,
            "SUV": 20
        };

        let currentCab = "Sedan";

        function selectCab(type) {
            currentCab = type;
            document.getElementById("cab_type_input").value = type;

            document.querySelectorAll(".car-card").forEach(c => c.classList.remove("selected"));
            document.getElementById("card-" + type).classList.add("selected");

            updateCalculation();
        }

        function updateCalculation() {
            const pickup = document.getElementById("pickup").value;
            const dropoff = document.getElementById("dropoff").value;

            if (!pickup || !dropoff) {
                document.getElementById("dist_preview").innerText = "0 KM";
                document.getElementById("fare_preview").innerText = "₹0.00";
                document.getElementById("breakdown").innerText = "";
                return;
            }

            if (pickup === dropoff) {
                document.getElementById("dist_preview").innerText = "0 KM";
                document.getElementById("fare_preview").innerText = "Select different places";
                document.getElementById("breakdown").innerText = "";
                return;
            }

            const km = Math.abs(places[dropoff] - places[pickup]);
            const baseFare = 50;
            const ratePerKm = cabRates[currentCab];
            const total = baseFare + (km * ratePerKm);

            document.getElementById("dist_preview").innerText = km + " KM";
            document.getElementById("fare_preview").innerText = "₹" + total.toFixed(2);
            document.getElementById("breakdown").innerText = "(Base ₹" + baseFare + " + " + km + " KM × ₹" + ratePerKm + " [" + currentCab + "])";
        }
    </script>
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
            <h1>Book a Taxi</h1>
            <?php echo $message; ?>

            <form action="book-taxi.php" method="POST" style="max-width: 520px;">
                <label><b>Pickup Location:</b></label><br>
                <select id="pickup" name="pickup" onchange="updateCalculation()" required style="width: 100%; padding: 10px; margin: 6px 0 12px 0;">
                    <option value="">-- Select Pickup Location --</option>
                    <?php foreach ($locations as $place => $dist): ?>
                        <option value="<?php echo htmlspecialchars($place); ?>"><?php echo htmlspecialchars($place); ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label><b>Dropoff Location:</b></label><br>
                <select id="dropoff" name="dropoff" onchange="updateCalculation()" required style="width: 100%; padding: 10px; margin: 6px 0 14px 0;">
                    <option value="">-- Select Dropoff Location --</option>
                    <?php foreach ($locations as $place => $dist): ?>
                        <option value="<?php echo htmlspecialchars($place); ?>"><?php echo htmlspecialchars($place); ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label><b>Select Vehicle (Members Capacity):</b></label>
                <input type="hidden" name="cab_type" id="cab_type_input" value="Sedan">

                <!-- CLEAN SINGLE ROW: 3 CARDS ONLY -->
                <div class="car-grid">
                    <!-- Mini -->
                    <div class="car-card" id="card-Mini" onclick="selectCab('Mini')">
                        <svg viewBox="0 0 24 24" width="60" height="42" fill="#1f4e79">
                            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.04 3H5.81l1.04-3zM7.5 16c-.83 0-1.5-.67-1.5-1.5S6.67 13 7.5 13s1.5.67 1.5 1.5S8.33 16 7.5 16zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                        </svg>
                        <p class="capacity">👥 Up to 3 Persons</p>
                        <p class="rate">₹12 / KM</p>
                    </div>

                    <!-- Sedan (Default) -->
                    <div class="car-card selected" id="card-Sedan" onclick="selectCab('Sedan')">
                        <svg viewBox="0 0 24 24" width="68" height="42" fill="#1f4e79">
                            <path d="M5 11l1.5-4.5h11L19 11m-1.5 5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5m-11 0c-.83 0-1.5-.67-1.5-1.5S3.67 13 4.5 13s1.5.67 1.5 1.5S5.33 16 4.5 16M21 11l-2-6c-.2-.6-.8-1-1.5-1h-11c-.7 0-1.3.4-1.5 1L3 11c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1h1v2c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-2h8v2c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-2h1c.6 0 1-.4 1-1v-6c0-.6-.4-1-1-1z"/>
                        </svg>
                        <p class="capacity">👥 Up to 4 Persons</p>
                        <p class="rate">₹15 / KM</p>
                    </div>

                    <!-- SUV -->
                    <div class="car-card" id="card-SUV" onclick="selectCab('SUV')">
                        <svg viewBox="0 0 24 24" width="68" height="42" fill="#1f4e79">
                            <path d="M19 10.5V6c0-.55-.45-1-1-1H6c-.55 0-1 .45-1 1v4.5C3.9 10.85 3 12 3 13.5V19c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-5.5c0-1.5-.9-2.65-2-3zM7 7h10v3H7V7zm-.5 10c-.83 0-1.5-.67-1.5-1.5S5.67 14 6.5 14s1.5.67 1.5 1.5S7.33 17 6.5 17zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                        </svg>
                        <p class="capacity">👥 Up to 6-7 Persons</p>
                        <p class="rate">₹20 / KM</p>
                    </div>
                </div>

                <div class="fare-box">
                    <p style="margin: 0; color: #333;">Distance: <b id="dist_preview" style="color: #1f4e79; font-size: 16px;">0 KM</b></p>
                    <p style="margin: 6px 0 0 0; color: #333;">Total Fare: <b id="fare_preview" style="color: #27ae60; font-size: 20px;">₹0.00</b></p>
                    <small id="breakdown" style="color: #777; display: block; margin-top: 4px;"></small>
                </div>

                <button type="submit" style="padding: 12px 20px; cursor: pointer; width: 100%; background: #1f4e79; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 15px;">Confirm Booking</button>
            </form>
        </main>
    </div>
</body>
</html>
