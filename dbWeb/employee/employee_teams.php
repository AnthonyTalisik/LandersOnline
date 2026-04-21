<?php
session_start();
require_once("../config/db.php");

if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'employee'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$empId = $_SESSION['employee_id'];

if(isset($_POST['update_work'])){
    $id = $_POST['assign_id'];
    $work = $_POST['work_hours'];

    // GET CHARGE
    $res = mysqli_query($conn, "
        SELECT Assgn_Charge FROM Assignments WHERE Assgn_Id = '$id'
    ");
    $data = mysqli_fetch_assoc($res);
    $charge = $data['Assgn_Charge'];

    // AUTO STATUS
    if($work == 0){
        $status = 'accepted';
    } elseif($work < $charge){
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }

    mysqli_query($conn, "
        UPDATE Assignments
            SET Assgn_PendingHours = '$work',
            Assgn_UpdateStat = 'submitted'
        WHERE Assgn_Id = '$id'
    ");
    header("Location: employee_teams.php");
}

$title = "Project Teams";
include("../layout/layout.php");
?>
<div class="container-fluid">
    <div class="row">
        <?php include("../layout/sidebar.php"); ?>
        <div class="col-md-9 col-lg-10 p-5">
            <h3 class="mb-4">My Progress</h3>
            <div class="card card-modern p-4 mb-4">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Role</th>
                        <th>Charge Hours</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $tasks = mysqli_query($conn, "
                        SELECT a.*, p.Proj_Name, j.Job_Title
                        FROM Assignments a
                        JOIN Projects p ON a.Assgn_ProjId = p.Proj_Id
                        JOIN Jobs j ON a.Assgn_JobId = j.Job_Id
                        WHERE a.Assgn_EmpId = '$empId'
                        AND a.Assgn_Status IN ('accepted','ongoing','completed')
                    ");

                    while($row = mysqli_fetch_assoc($tasks)):
                        $work = $row['Assgn_WorkHours'] ?? 0;
                        $charge = $row['Assgn_Charge'];
                        $progress = ($charge > 0) ? ($work / $charge) * 100 : 0;
                        if($progress > 100) $progress = 100;
                    ?>
                    <tr>
                    <td><?= $row['Proj_Name'] ?></td>
                    <td><?= $row['Job_Title'] ?></td>
                    <td><?= number_format($charge,2) ?> hrs</td>
                    <td>
                        <?= number_format($work,2) ?> / <?= number_format($charge,2) ?> hrs
                        <div class="progress mt-1" style="height:8px;">
                            <div class="progress-bar bg-success"
                                style="width: <?= $progress ?>%;">
                            </div>
                        </div>
                        <small><?= round($progress) ?>%</small>
                    </td>

                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#updateModal<?= $row['Assgn_Id'] ?>">
                            Update
                        </button>
                    </td>
                    </tr>

                    <!-- MODAL -->
                    <div class="modal fade" id="updateModal<?= $row['Assgn_Id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Work Hours</h5>
                                <button class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="assign_id" value="<?= $row['Assgn_Id'] ?>">
                                <label>Work Hours</label>
                                <input type="number" step="0.01"
                                    name="work_hours"
                                    class="form-control"
                                    value="<?= $work ?>"
                                    required>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_work" class="btn btn-outline-primary">
                                    Save
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
            <hr class="mb-4">
            <?php
                $hasTeamsQuery = mysqli_query($conn, "
                    SELECT 1
                    FROM Assignments
                    WHERE Assgn_EmpId = '$empId'
                    AND Assgn_Status IN ('accepted','ongoing','completed')
                    LIMIT 1
                ");
                $hasTeams = mysqli_num_rows($hasTeamsQuery) > 0;
            ?>
            <?php if($hasTeams): ?>
            <h3 class="mb-4">My Teams</h3>
            <?php
            $projects = mysqli_query($conn, "
                SELECT DISTINCT p.*
                FROM Projects p
                JOIN Assignments a ON p.Proj_Id = a.Assgn_ProjId
                WHERE a.Assgn_EmpId = '$empId'
                AND a.Assgn_Status IN ('accepted','ongoing','completed')
            ");
            $hasVisibleTeams = false;
            while($proj = mysqli_fetch_assoc($projects)):
                $projId = $proj['Proj_Id'];
                $teams = mysqli_query($conn, "
                    SELECT a.*, e.Emp_FName, e.Emp_LName, j.Job_Title
                    FROM Assignments a
                    JOIN Employees e ON a.Assgn_EmpId = e.Emp_Id
                    JOIN Jobs j ON a.Assgn_JobId = j.Job_Id
                    WHERE a.Assgn_ProjId = '$projId'
                    AND a.Assgn_Status IN ('accepted','ongoing','completed')
                ");
                if(mysqli_num_rows($teams) > 0):
                    $hasVisibleTeams = true;
            ?>
            <div class="card card-modern p-4 mb-4">
                <div class="d-flex justify-content-between mb-3">
                    <h5><?= $proj['Proj_Name'] ?></h5>
                    <span class="badge bg-success"><?= ucfirst($proj['Proj_Status']) ?></span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Job</th>
                            <th>Hours</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($m = mysqli_fetch_assoc($teams)): ?>
                    <?php
                    // ✅ CORRECT LOGIC (pending vs confirmed)
                    if($m['Assgn_UpdateStat'] == 'submitted'){
                        $work = $m['Assgn_WorkHours']; // OLD value
                    } else {
                        $work = $m['Assgn_WorkHours']; // confirmed value (same for now)
                    }
                    $charge = $m['Assgn_Charge'] ?? 1;
                    $progress = ($charge > 0) ? ($work / $charge) * 100 : 0;
                    if($progress > 100) $progress = 100;
                    ?>
                    <tr>
                        <td><?= $m['Emp_FName']." ".$m['Emp_LName'] ?></td>
                        <td><?= $m['Job_Title'] ?></td>
                        <td><?= $m['Assgn_Charge'] ?> hrs</td>
                        <td>
                            <?= number_format($work,2) ?> / <?= number_format($charge,2) ?> hrs
                            <div class="progress mt-1" style="height:8px;">
                                <div class="progress-bar bg-success"
                                    style="width: <?= $progress ?>%;">
                                </div>
                            </div>
                            <small><?= round($progress) ?>%</small>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php
                    // TOTAL COMPUTATION (ONLY VALID STATUSES)
                    $totals = mysqli_query($conn, "
                        SELECT 
                            IFNULL(SUM(Assgn_Charge),0) AS total_charge,
                            IFNULL(SUM(Assgn_WorkHours),0) AS total_work
                        FROM Assignments
                        WHERE Assgn_ProjId = '$projId'
                        AND Assgn_Status IN ('accepted','ongoing','completed')
                    ");

                    $totalData = mysqli_fetch_assoc($totals);

                    $totalCharge = $totalData['total_charge'];
                    $totalWork = $totalData['total_work'];

                    // COMPUTE OVERALL PROGRESS
                    $overallProgress = ($totalCharge > 0)
                        ? ($totalWork / $totalCharge) * 100
                        : 0;

                    if($overallProgress > 100) $overallProgress = 100;
                ?>
                <div class="mt-3">
                    <p class="mb-1">
                        <strong>Total Hours:</strong> <?= number_format($totalCharge,2) ?> hrs
                    </p>
                    <p class="mb-1">
                        <strong>Overall Progress:</strong> <?= round($overallProgress) ?>%
                    </p>
                    <div class="progress" style="height:8px; max-width:300px;">
                        <div class="progress-bar bg-success"
                            style="width: <?= $overallProgress ?>%;">
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endif;
            endwhile;
            ?>

            <?php if(!$hasVisibleTeams): ?>
                <p class="text-muted">No team assignments yet.</p>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>