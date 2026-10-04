<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "db.php";

// Array of drivers to insert (Add as many as you need here!)
$drivers = [
    [
        "fullname"     => "Santhose Kumar",
        "mobile"       => "9876543210",
        "email"        => "malliga07@example.com",
        "username"     => "santhose",
        "password"     => "driver001",
        "vehicle_name" => "Swift Dzire",
        "vehicle_no"   => "TN-69-AB-7777"
    ],
    [
        "fullname"     => "Sudalai Muthu",
        "mobile"       => "9876543211",
        "email"        => "preethi06@example.com",
        "username"     => "sudalaimuthu",
        "password"     => "driver002",
        "vehicle_name" => "Toyota Etios",
        "vehicle_no"   => "TN-69-CD-7777"
    ],
    [
        "fullname"     => "Mohan babu",
        "mobile"       => "9876543212",
        "email"        => "office09@example.com",
        "username"     => "mohanbabu",
        "password"     => "driver003",
        "vehicle_name" => "Hyundai Xcent",
        "vehicle_no"   => "TN-72-EF-7777"
    ]
];

$role = "driver";
$count = 0;

echo "<h2>Creating Driver Accounts...</h2><ul>";

foreach ($drivers as $d) {
    $username = $d["username"];
    $hashed_password = password_hash($d["password"], PASSWORD_DEFAULT);

    // Remove old account if it exists
    $conn->query("DELETE FROM users WHERE username = '$username'");

    // Insert driver account with vehicle info
    $stmt = $conn->prepare("INSERT INTO users (fullname, mobile, email, username, password, role, vehicle_name, vehicle_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $d["fullname"], $d["mobile"], $d["email"], $username, $hashed_password, $role, $d["vehicle_name"], $d["vehicle_no"]);

    if ($stmt->execute()) {
        echo "<li style='color: green;'>Created Driver: <b>{$username}</b> (Password: {$d['password']}) - Vehicle: {$d['vehicle_name']} [{$d['vehicle_no']}]</li>";
        $count++;
    } else {
        echo "<li style='color: red;'>Failed to create {$username}: " . $stmt->error . "</li>";
    }
}

echo "</ul>";
echo "<p style='color: blue; font-size: 18px;'><b>Successfully created {$count} drivers!</b></p>";
echo "<p><a href='login.html'>Click here to go to Login Page</a></p>";
?>