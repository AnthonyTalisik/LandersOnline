<?php
$conn = new mysqli("localhost", "root", "imdbsys31", "WorkSphere");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>