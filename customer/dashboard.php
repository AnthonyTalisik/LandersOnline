<?php
session_start();
require_once("../config/db.php");

// CHECK LOGIN
if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'employee'){
    header("Location: /LandersOnline/auth/login.php");
    exit();
}

$empId = $_SESSION['employee_id'];

/* =========================
   ACTION HANDLERS
========================= */

// DENY
if(isset($_POST['submit_deny'])){
    $id = $_POST['assign_id'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    mysqli_query($conn, "
        UPDATE Assignments 
        SET Assgn_Status='denied',
            Assgn_Notes='$notes'
        WHERE Assgn_Id='$id'
    ");

    header("Location: employee_assignment.php");
    exit();
}

// ACCEPT TASK
if(isset($_GET['accept'])){
    $id = $_GET['accept'];

    mysqli_query($conn, "
        UPDATE Assignments 
        SET Assgn_Status='accepted'
        WHERE Assgn_Id='$id'
    ");

    header("Location: employee_assignment.php");
    exit();
}

// START TASK
if(isset($_GET['start'])){
    $id = $_GET['start'];

    mysqli_query($conn, "
        UPDATE Assignments 
        SET Assgn_Status='ongoing'
        WHERE Assgn_Id='$id'
    ");

    header("Location: employee_assignment.php");
    exit();
}

$title = "My Assignments";
include("../layout/layout.php");
?>

<div class="container-fluid">
<div class="row">

    <!-- SIDEBAR -->
    <?php include("../layout/sidebar.php"); ?>

    <!-- CONTENT -->
    <div class="col-md-9 col-lg-10 p-5">

        <h3 class="mb-4">My Assignments</h3>

        <table class="table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Job</th>
                    <th>Hours</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $assignments = mysqli_query($conn, "
                    SELECT a.*, p.Proj_Name, j.Job_Title
                    FROM Assignments a
                    JOIN Projects p ON a.Assgn_ProjId = p.Proj_Id
                    JOIN Jobs j ON a.Assgn_JobId = j.Job_Id
                    WHERE a.Assgn_EmpId = '$empId'
                ");

                while($row = mysqli_fetch_assoc($assignments)):
                ?>
                    <tr>
                        <td><?= $row['Proj_Name'] ?></td>
                        <td><?= $row['Job_Title'] ?></td>
                        <td><?= $row['Assgn_Charge'] ?> hrs</td>

                        <!-- STATUS -->
                        <td>
                            <?php if($row['Assgn_Status'] == 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>

                            <?php elseif($row['Assgn_Status'] == 'accepted'): ?>
                                <span class="badge bg-primary">Accepted</span>

                            <?php elseif($row['Assgn_Status'] == 'ongoing'): ?>
                                <span class="badge bg-info">Ongoing</span>

                            <?php elseif($row['Assgn_Status'] == 'completed'): ?>
                                <span class="badge bg-success">Completed</span>

                            <?php elseif($row['Assgn_Status'] == 'denied'): ?>
                                <span class="badge bg-danger">Denied</span>
                            <?php endif; ?>
                        </td>

                        <!-- ACTION -->
                        <td>
                            <div class="d-flex gap-2">
                                <?php if($row['Assgn_Status'] == 'pending'): ?>

                                    <!-- ACCEPT -->
                                    <a href="?accept=<?= $row['Assgn_Id'] ?>"
                                    class="btn btn-sm btn-outline-success">
                                        Accept
                                    </a>

                                    <!-- DENY -->
                                    <button class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#denyModal<?= $row['Assgn_Id'] ?>">
                                        Deny
                                    </button>
                                <?php elseif($row['Assgn_Status'] == 'accepted'): ?>

                                    <!-- START -->
                                    <a href="?start=<?= $row['Assgn_Id'] ?>"
                                    class="btn btn-sm btn-outline-primary">
                                        Start
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <!-- DENY MODAL -->
                    <div class="modal fade" id="denyModal<?= $row['Assgn_Id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reason for Denial</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="assign_id" value="<?= $row['Assgn_Id'] ?>">
                                        <div class="mb-3">
                                            <label>Reason</label>
                                            <textarea name="notes" class="form-control" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="submit_deny" class="btn btn-danger">
                                            Submit
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>
</div>