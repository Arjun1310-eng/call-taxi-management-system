<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "call_taxi_db";

try{
$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");
}catch(mysql_sql_exception $e){
    die("Database connection failed.Please try again later.");
}
?>
