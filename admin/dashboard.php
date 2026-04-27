<?php
    session_start();
    require_once("../config/db.php");
    if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'admin'){
        header("Location: /LandersOnline/auth/login.php");
        exit();
    }

?>    