<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            if ($role == "admin") {
                header("Location: admin-dashboard.php");
                
            }elseif ($user["role"]==="driver"){
                header("Location: driver-dashboard.php");
            } 
            else {
                header("Location: user-dashboard.php");
            }
            exit();
        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }
}
?>
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role     = trim($_POST["role"]);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            
            // CHECK DRIVER APPROVAL STATUS
            if ($user["role"] === "driver") {
                if ($user["approval_status"] === "Pending") {
                    echo "<script>alert('Your driver registration is still pending Admin approval!'); window.location.href='login.html';</script>";
                    exit();
                } elseif ($user["approval_status"] === "Rejected") {
                    echo "<script>alert('Your driver registration was rejected by the Admin.'); window.location.href='login.html';</script>";
                    exit();
                }
            }

            // SET SESSION & REDIRECT
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"]     = $user["role"];

            if ($user["role"] === "admin") {
                header("Location: admin-dashboard.php");
            } elseif ($user["role"] === "driver") {
                header("Location: driver-dashboard.php");
            } else {
                header("Location: user-dashboard.php");
            }
            exit();

        } else {
            echo "<script>alert('Invalid password!'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found or role mismatched!'); window.location.href='login.html';</script>";
    }
}
?>