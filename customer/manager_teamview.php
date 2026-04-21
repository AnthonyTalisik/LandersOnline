<?php
session_start();
require_once("../config/db.php");

if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'employee'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$managerId = $_SESSION['employee_id'];

$projects = mysqli_query($conn, "
    SELECT * FROM Projects
    WHERE Proj_ManagerId = '$managerId'
");

if(isset($_POST['confirm'])){
    $id = $_POST['assign_id'];

    // GET WORK + CHARGE
    $res = mysqli_query($conn, "
        SELECT Assgn_PendingHours, Assgn_Charge
        FROM Assignments
        WHERE Assgn_Id = '$id'
    ");

    $data = mysqli_fetch_assoc($res);
    $work = $data['Assgn_PendingHours'];
    $charge = $data['Assgn_Charge'];

    // DETERMINE STATUS
    if($work == 0){
        $status = 'accepted';
    } elseif($work < $charge){
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }

    mysqli_query($conn, "
        UPDATE Assignments
        SET 
            Assgn_WorkHours = Assgn_PendingHours,
            Assgn_PendingHours = 0,
            Assgn_Status = '$status',
            Assgn_UpdateStat = 'confirmed'
        WHERE Assgn_Id = '$id'
    ");
    header("Location: manager_teamview.php");
}

$title = "Project Teams";
include("../layout/layout.php");
?>
<div class="container-fluid">
    <div class="row">
        <?php include("../layout/sidebar.php"); ?>
        <div class="col-md-9 col-lg-10 p-5">
            <h3 class="mb-4">Project Teams</h3>
            <?php while($proj = mysqli_fetch_assoc($projects)): ?>
                <div class="card card-modern p-4 mb-4">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><?= htmlspecialchars($proj['Proj_Name']) ?></h5>

                        <?php if($proj['Proj_Status'] == 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </div>

                    <!-- TABLE -->
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Job Role</th>
                                <th>Charge Hours</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php
                        $projId = $proj['Proj_Id'];

                        $teams = mysqli_query($conn, "
                            SELECT a.*, e.Emp_FName, e.Emp_LName, j.Job_Title
                            FROM Assignments a
                            JOIN Employees e ON a.Assgn_EmpId = e.Emp_Id
                            JOIN Jobs j ON a.Assgn_JobId = j.Job_Id
                            WHERE a.Assgn_ProjId = '$projId'
                            AND a.Assgn_Status IN ('accepted','ongoing','completed')
                        ");

                        // FIXED TOTALS (FILTERED + SAFE)
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

                        $avgProgress = ($totalCharge > 0)
                            ? ($totalWork / $totalCharge) * 100
                            : 0;
                        if(mysqli_num_rows($teams) > 0):
                            while($m = mysqli_fetch_assoc($teams)):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($m['Emp_FName']." ".$m['Emp_LName']) ?></td>
                            <td><?= htmlspecialchars($m['Job_Title']) ?></td>
                            <td><?= $m['Assgn_Charge'] ?> hrs</td>
                            <?php
                            if($m['Assgn_UpdateStat'] == 'confirmed'){
                                $work = $m['Assgn_WorkHours']; 
                            } else {
                                $work = $m['Assgn_WorkHours']; 
                            }
                            $charge = $m['Assgn_Charge'] ?? 1;
                            $progress = ($charge > 0) ? ($work / $charge) * 100 : 0;
                            if($progress > 100) $progress = 100;
                            ?>
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
                            <?php if($m['Assgn_UpdateStat'] == 'submitted'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="assign_id" value="<?= $m['Assgn_Id'] ?>">
                                    <button name="confirm" class="btn btn-sm btn-success">
                                        Confirm
                                    </button>
                                </form>
                            <?php elseif($m['Assgn_UpdateStat'] == 'confirmed'): ?>
                                <button class="btn btn-sm btn-secondary" disabled>
                                    Confirm
                                </button>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No team members assigned yet
                            </td>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- TOTALS -->
                    <div class="mt-3">
                        <p class="mb-1">
                            <strong>Total Hours:</strong> <?= number_format($totalCharge,2) ?> hrs
                        </p>
                        <p class="mb-1">
                            <strong>Overall Progress:</strong> <?= number_format($avgProgress, 0) ?>%
                        </p>
                        <div class="progress" style="height: 8px; max-width:300px;">
                            <div class="progress-bar bg-success" 
                                style="width: <?= $avgProgress ?>%;">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>