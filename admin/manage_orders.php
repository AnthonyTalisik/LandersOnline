<?php
ob_start();
session_start();
require_once("../config/db.php");

// CHECK LOGIN
if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'company'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$companyId = $_SESSION['company_id'];


// =========================
// ADD PROJECT
// =========================
if(isset($_POST['add_project'])){

    $projectName = $_POST['project_name'];
    $managerId = $_POST['manager_id'];

    $res = $conn->query("SELECT MAX(Proj_Id) AS max_id FROM Projects");
    $row = $res->fetch_assoc();
    $projId = (!empty($row['max_id'])) ? $row['max_id'] + 1 : 5001;

    $stmt = $conn->prepare("
        INSERT INTO Projects (Proj_Id, Proj_CompId, Proj_Name, Proj_ManagerId, Proj_Status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("iisi", $projId, $companyId, $projectName, $managerId);

    $_SESSION['success'] = $stmt->execute() ? "Project added successfully!" : "Error adding project.";

    header("Location: manage_project.php");
    exit();
}


// =========================
// UPDATE PROJECT
// =========================
if(isset($_POST['update_project'])){

    $projId = $_POST['proj_id'];
    $projectName = $_POST['project_name'];
    $managerId = $_POST['manager_id'];

    $stmt = $conn->prepare("
        UPDATE Projects 
        SET Proj_Name=?, Proj_ManagerId=?
        WHERE Proj_Id=?
    ");
    $stmt->bind_param("sii", $projectName, $managerId, $projId);
    $stmt->execute();

    $_SESSION['success'] = "Project updated successfully.";
    header("Location: manage_project.php");
    exit();
}


// =========================
// TOGGLE STATUS
// =========================
if(isset($_GET['toggle_project_status'])){

    $projId = $_GET['toggle_project_status'];

    $res = mysqli_query($conn, "SELECT Proj_Status FROM Projects WHERE Proj_Id = '$projId'");
    $data = mysqli_fetch_assoc($res);

    $newStatus = ($data['Proj_Status'] == 'active') ? 'inactive' : 'active';

    mysqli_query($conn, "UPDATE Projects SET Proj_Status='$newStatus' WHERE Proj_Id='$projId'");

    $_SESSION['success'] = "Project status updated.";
    header("Location: manage_project.php");
    exit();
}


// =========================
// LOAD UI
// =========================
$title = "Manage Projects";
include("../layout/layout.php");
?>

<!-- TOAST -->
<?php if(isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast" class="toast text-white bg-success border-0">
        <div class="toast-body"><?= $_SESSION['success']; ?></div>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<div class="container-fluid">
<div class="row">

<?php include("../layout/sidebar.php"); ?>

<div class="col-md-9 col-lg-10 p-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Manage Projects</h3>

        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            <i class="bi bi-plus-circle me-2"></i> Add Project
        </button>
    </div>

    <!-- PROJECT LIST -->
    <div class="card card-modern p-4">
        <h5 class="mb-3">Project List</h5>

        <table class="table">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Project Manager</th>
                    <th>Status</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $projects = mysqli_query($conn, "
                SELECT p.*, c.Comp_Name,
                       e.Emp_FName, e.Emp_LName
                FROM Projects p
                JOIN Companies c ON p.Proj_CompId = c.Comp_Id
                LEFT JOIN Employees e ON p.Proj_ManagerId = e.Emp_Id
                WHERE p.Proj_CompId = '$companyId'
            ");

            while($row = mysqli_fetch_assoc($projects)):
            ?>

            <tr>
                <td><?= $row['Proj_Name'] ?></td>

                <td>
                    <?= $row['Emp_FName'] ? $row['Emp_FName']." ".$row['Emp_LName'] : 'N/A' ?>
                </td>

                <td>
                    <?php if($row['Proj_Status'] == 'active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                    <?php endif; ?>
                </td>

                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2">

                        <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#detailsModal<?= $row['Proj_Id'] ?>">
                            Details
                        </button>

                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $row['Proj_Id'] ?>">
                            Edit
                        </button>

                        <?php if($row['Proj_Status'] == 'active'): ?>
                            <a href="?toggle_project_status=<?= $row['Proj_Id'] ?>"
                               class="btn btn-sm btn-outline-danger">Inactive</a>
                        <?php else: ?>
                            <a href="?toggle_project_status=<?= $row['Proj_Id'] ?>"
                               class="btn btn-sm btn-outline-success">Active</a>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>

            <!-- DETAILS MODAL -->
            <div class="modal fade" id="detailsModal<?= $row['Proj_Id'] ?>">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">

                        <div class="modal-header border-0">
                            <h5>Project Details</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-center">

                            <h5><?= $row['Proj_Name'] ?></h5>

                            <p class="text-muted mb-1">Project ID: <?= $row['Proj_Id'] ?></p>
                            <p class="text-muted mb-1">Company: <?= $row['Comp_Name'] ?></p>
                            <p class="text-muted mb-1">Project Manager: <?= $row['Emp_FName'] ? $row['Emp_FName']." ".$row['Emp_LName'] : 'N/A' ?></p>
                            
                            <p>
                                Status:
                                <?php if($row['Proj_Status']=='active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </p>

                        </div>

                        <div class="modal-footer border-0">
                            <button class="btn btn-primary-custom" data-bs-dismiss="modal">Close</button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- EDIT MODAL -->
            <div class="modal fade" id="editModal<?= $row['Proj_Id'] ?>">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">

                        <div class="modal-header border-0">
                            <h5>Edit Project</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST">
                            <div class="modal-body">

                                <input type="hidden" name="proj_id" value="<?= $row['Proj_Id'] ?>">

                                <div class="mb-3">
                                    <input type="text" name="project_name"
                                           value="<?= $row['Proj_Name'] ?>"
                                           class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <select name="manager_id" class="form-control" required>
                                        <?php
                                        $emps = mysqli_query($conn, "SELECT * FROM Employees WHERE Emp_CompId='$companyId'");
                                        while($emp = mysqli_fetch_assoc($emps)):
                                        ?>
                                        <option value="<?= $emp['Emp_Id'] ?>"
                                            <?= ($emp['Emp_Id']==$row['Proj_ManagerId'])?'selected':'' ?>>
                                            <?= $emp['Emp_FName'].' '.$emp['Emp_LName'] ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                            </div>

                            <div class="modal-footer border-0">
                                <button type="submit" name="update_project"
                                        class="btn btn-primary-custom w-100">
                                    Update Project
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
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addProjectModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">

      <div class="modal-header border-0">
        <h5>Add Project</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST">
      <div class="modal-body">

            <div class="mb-3">
                <input type="text" name="project_name"
                       class="form-control"
                       placeholder="Project Name" required>
            </div>

            <div class="mb-3">
                <select name="manager_id" class="form-control" required>
                    <option value="">Select Project Manager</option>
                    <?php
                    $emps = mysqli_query($conn, "SELECT * FROM Employees WHERE Emp_CompId='$companyId'");
                    while($emp = mysqli_fetch_assoc($emps)):
                    ?>
                    <option value="<?= $emp['Emp_Id'] ?>">
                        <?= $emp['Emp_FName'].' '.$emp['Emp_LName'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

      </div>

      <div class="modal-footer border-0">
        <button type="submit" name="add_project"
                class="btn btn-primary-custom w-100">
            Add Project
        </button>
      </div>
      </form>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toast = document.getElementById('successToast');
    if(toast){
        new bootstrap.Toast(toast, { delay: 2000 }).show();
    }
});
</script>