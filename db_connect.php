<?php
$host = "localhost";  // Change this if using an external database
$user = "root";       // Default user in XAMPP
$pass = "";           // Default password in XAMPP is empty
$dbname = "opac_library";  // Your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
