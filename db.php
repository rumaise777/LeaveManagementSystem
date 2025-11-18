<?php
$host = "localhost";      // default host
$user = "root";           // default user
$password = "";           // Default password is blank
$db = "leave_system";    //database name

// Create connection using object-oriented MySQLi
$conn = new mysqli($host, $user, $password, $db,3306);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
