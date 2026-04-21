<?php
    session_start();
    require_once("../config/db.php");
    if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'company'){
        header("Location: /dbweb/auth/login.php");
        exit();
    }

    $title = "Company Dashboard";
    include("../layout/layout.php");
    require_once("../config/db.php");
    $currentPage = basename($_SERVER['PHP_SELF']);

    /* Get Company ID from session */
    $companyId = $_SESSION['company_id'];

    // ACTIVE EMPLOYEES
    $empRes = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM Employees e
        JOIN Accounts a ON e.Emp_AcctId = a.Acct_Id
        WHERE e.Emp_CompId = '$companyId'
        AND a.Acct_Status = 'active'
    ");
    $empData = mysqli_fetch_assoc($empRes);

    // ACTIVE JOBS
    $jobRes = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM Jobs
        WHERE Job_CompId = '$companyId'
        AND Job_Status = 'active'
    ");
    $jobData = mysqli_fetch_assoc($jobRes);

    // ACTIVE PROJECTS
    $projRes = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM Projects
        WHERE Proj_CompId = '$companyId'
        AND Proj_Status = 'active'
    ");
    $projData = mysqli_fetch_assoc($projRes);
?>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <?php include("../layout/sidebar.php"); ?>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-5">
            <h2 class="mb-4">Company Dashboard</h2>
            <div class="row">

                <!-- ACTIVE EMPLOYEES -->
                <div class="col-md-4">
                    <div class="card card-modern text-center p-4">
                        <i class="bi bi-people fs-2 mb-2"></i>
                        <h6>Active Employees</h6>
                        <h3><?= $empData['total'] ?></h3>
                    </div>
                </div>

                <!-- ACTIVE JOBS -->
                <div class="col-md-4">
                    <div class="card card-modern text-center p-4">
                        <i class="bi bi-briefcase fs-2 mb-2"></i>
                        <h6>Active Jobs</h6>
                        <h3><?= $jobData['total'] ?></h3>
                    </div>
                </div>

                <!-- ACTIVE PROJECTS -->
                <div class="col-md-4">
                    <div class="card card-modern text-center p-4">
                        <i class="bi bi-folder fs-2 mb-2"></i>
                        <h6>Active Projects</h6>
                        <h3><?= $projData['total'] ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>