<?php
session_start();
session_destroy();
header("Location: /LandersOnline/index.php");
exit();
?>