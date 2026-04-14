<?php
$host = "sql100.infinityfree.com";
$user = "if0_41662958";
$pass = "opac2026";
$dbname = "if0_41662958_opac_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 