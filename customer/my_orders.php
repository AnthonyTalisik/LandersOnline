<?php
session_start();
require_once("../config/db.php");

// ✅ ONLY MANAGER CAN ACCESS
if(
    !isset($_SESSION['account_id']) || 
    $_SESSION['role'] != 'employee' || 
    !($_SESSION['is_manager'] ?? false)
){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$empId = $_SESSION['employee_id'];

// GET PROJECTS
$projects = mysqli_query($conn, "
    SELECT * FROM Projects
    WHERE Proj_ManagerId = '$empId'
");
?>

<?php
$title = "Manager Dashboard";
include("../layout/layout.php");
?>

<div class="container-fluid">
<div class="row">

<!-- ✅ SIDEBAR -->
<?php include("../layout/sidebar.php"); ?>

<!-- ✅ CONTENT -->
<div class="col-md-9 col-lg-10 p-5">

    <h3 class="mb-4">Manager Dashboard</h3>

    <div class="card card-modern p-4">
        <h5 class="mb-3">My Managed Projects</h5>

        <table class="table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Team Members</th>
                </tr>
            </thead>
            <tbody>

            <?php while($row = mysqli_fetch_assoc($projects)): ?>

                <?php
                $projId = $row['Proj_Id'];              
                $countRes = mysqli_query($conn, "
                    SELECT COUNT(*) as total 
                    FROM Assignments 
                    WHERE Assgn_ProjId = '$projId'
                    AND Assgn_Status IN ('accepted','ongoing','completed')
                ");
                $countData = mysqli_fetch_assoc($countRes);
                ?>

                <tr>
                    <td><?= $row['Proj_Name'] ?></td>

                    <td>
                        <?php if($row['Proj_Status']=='active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>

                    <td><?= $countData['total'] ?> Members</td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>
    </div>

</div>
</div>
</div>