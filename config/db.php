<?php
$conn = new mysqli("localhost", "root", "", "LandersOnline", 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>