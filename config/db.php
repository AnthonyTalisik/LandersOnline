<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli("localhost", "root", "", "LandersOnline", 3307);

if ($conn->connect_error) {
    $conn = null;
}
?>
