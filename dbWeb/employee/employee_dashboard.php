<?php
session_start();
require_once("../config/db.php");

if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'employee'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$empId = $_SESSION['employee_id'];

$title = "Employee Dashboard";
include("../layout/layout.php");
?>

<div class="container-fluid">
    <div class="row">
        <?php include("../layout/sidebar.php"); ?>
        <div class="col-md-9 col-lg-10 p-5">
            <h3 class="mb-4">Dashboard</h3>
            <?php
            $summary = mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total_tasks,

                    IFNULL(SUM(CASE 
                        WHEN Assgn_Status = 'ongoing' THEN 1 
                        ELSE 0 
                    END), 0) as ongoing,

                    IFNULL(SUM(CASE 
                        WHEN Assgn_Status = 'completed' THEN 1 
                        ELSE 0 
                    END), 0) as completed

                FROM Assignments
                WHERE Assgn_EmpId = '$empId'
                AND Assgn_Status IN ('accepted','ongoing','completed')
            ");

            $data = mysqli_fetch_assoc($summary);
            ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-modern p-3 text-center">
                        <h6>Total Tasks</h6>
                        <h4><?= $data['total_tasks'] ?></h4>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-modern p-3 text-center">
                        <h6>Ongoing</h6>
                        <h4><?= $data['ongoing'] ?></h4>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-modern p-3 text-center">
                        <h6>Completed</h6>
                        <h4><?= $data['completed'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>