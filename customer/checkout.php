<?php
ob_start();
session_start();
require_once("../config/db.php");

// CHECK LOGIN
if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'employee'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$companyId = $_SESSION['company_id'];
$managerId = $_SESSION['employee_id'];

// =========================
// ADD ASSIGNMENT
// =========================
if(isset($_POST['add_assignment'])){

    $projId = $_POST['proj_id'];
    $empId = $_POST['emp_id'];
    $jobId = $_POST['job_id'];
    $charge = $_POST['charge'];

    // PREVENT DUPLICATE
    $check = $conn->prepare("
        SELECT Assgn_Id FROM Assignments
        WHERE Assgn_ProjId=? AND Assgn_EmpId=? AND Assgn_JobId=?
    ");
    $check->bind_param("iii", $projId, $empId, $jobId);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $_SESSION['error'] = "Assignment already exists!";
        header("Location: manage_assignment.php?proj_filter=".$projId);
        exit();
    }

    // GENERATE ID
    $res = $conn->query("SELECT MAX(Assgn_Id) AS max_id FROM Assignments");
    $row = $res->fetch_assoc();
    $assignId = (!empty($row['max_id'])) ? $row['max_id'] + 1 : 5001;

    //CHECK VALID PROJECT
    if(empty($projId) || $projId == 0){
        $_SESSION['error'] = "Invalid project selected.";
        header("Location: manage_assignment.php?proj_filter=".$projId);
        exit();
    }

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO Assignments 
        (Assgn_Id, Assgn_ProjId, Assgn_EmpId, Assgn_JobId, Assgn_Charge, Assgn_WorkHours, Assgn_PendingHours, Assgn_Notes, Assgn_Status, Assgn_UpdateStat)
        VALUES (?, ?, ?, ?, ?, 0, 0, 'none', 'pending', 'none')
    ");
    $stmt->bind_param("iiiid", $assignId, $projId, $empId, $jobId, $charge);
    $stmt->execute();

    $_SESSION['success'] = "Assignment created successfully!";
    header("Location: manage_assignment.php?proj_filter=".$projId);
    exit();
}


// =========================
// UPDATE ASSIGNMENT
// =========================
if(isset($_POST['update_assignment'])){

    $assignId = $_POST['assign_id'];
    $projId = $_POST['proj_id'];

    // CHECK STATUS
    $check = $conn->prepare("
        SELECT Assgn_Status 
        FROM Assignments 
        WHERE Assgn_Id=?
    ");
    $check->bind_param("i", $assignId);
    $check->execute();
    $res = $check->get_result();
    $data = $res->fetch_assoc();
    if(!in_array($data['Assgn_Status'], ['pending','denied'])){
        $_SESSION['error'] = "Cannot edit this assignment!";
        header("Location: manage_assignment.php?proj_filter=".$projId);
        exit();
    }

    // PROCEED UPDATE
    $empId = $_POST['emp_id'];
    $jobId = $_POST['job_id'];
    $charge = $_POST['charge'];
    $stmt = $conn->prepare("
        UPDATE Assignments 
        SET Assgn_ProjId=?, Assgn_EmpId=?, Assgn_JobId=?, Assgn_Charge=?, Assgn_Status='pending' 
        WHERE Assgn_Id=?
    ");
    $stmt->bind_param("iiidi", $projId, $empId, $jobId, $charge, $assignId);
    $stmt->execute();
    $_SESSION['success'] = "Assignment updated!";
    header("Location: manage_assignment.php?proj_filter=".$projId);
    exit();
}


// =========================
// REMOVE ASSIGNMENT
// =========================
if(isset($_GET['delete'])){
    $id = $_GET['delete'];

    // CHECK STATUS FIRST
    $check = $conn->prepare("
        SELECT Assgn_Status, Assgn_ProjId 
        FROM Assignments 
        WHERE Assgn_Id=?
    ");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $data = $result->fetch_assoc();

    // ALLOW ONLY pending and denied
    if(in_array($data['Assgn_Status'], ['pending','denied'])){

        $stmt = $conn->prepare("DELETE FROM Assignments WHERE Assgn_Id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $_SESSION['success'] = "Assignment removed!";
    } else {
        $_SESSION['error'] = "Cannot delete this assignment!";
    }

    header("Location: manage_assignment.php?proj_filter=".$data['Assgn_ProjId']);
    exit();
}

if(!isset($_GET['proj_filter'])){
    $res = mysqli_query($conn, "
        SELECT Proj_Id 
        FROM Projects 
        WHERE Proj_ManagerId = '$managerId'
        LIMIT 1
    ");
    $data = mysqli_fetch_assoc($res);

    if($data){
        header("Location: manage_assignment.php?proj_filter=".$data['Proj_Id']);
        exit();
    }
}

// =========================
// LOAD UI
// =========================
$title = "Manage Assignments";
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

<?php if(isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="errorToast" class="toast text-white bg-danger border-0">
        <div class="toast-body"><?= $_SESSION['error']; ?></div>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>


<div class="container-fluid">
    <div class="row">
        <?php include("../layout/sidebar.php"); ?>
        <div class="col-md-9 col-lg-10 p-5">
            <!-- HEADER -->
            <div class="mb-3">
                <h3>Manage Assignments</h3>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <!-- LEFT: PROJECT FILTER -->
                <form method="GET" class="d-flex align-items-center gap-2">

                    <label class="fw-semibold mb-0">Project Name:</label>

                    <select name="proj_filter"
                            class="form-select"
                            onchange="this.form.submit()"
                            style="width:250px;">
                        <?php
                        $projects = mysqli_query($conn, "
                            SELECT * FROM Projects 
                            WHERE Proj_ManagerId = '$managerId'
                        ");

                        $firstProj = null;
                        while($p = mysqli_fetch_assoc($projects)){
                            // Set first project as default
                            if(!$firstProj){
                                $firstProj = $p['Proj_Id'];
                            }
                            // If no selected yet, use first project
                            $selectedValue = isset($_GET['proj_filter']) 
                                            ? $_GET['proj_filter'] 
                                            : $firstProj;
                            $selected = ($selectedValue == $p['Proj_Id']) ? 'selected' : '';
                            echo "<option value='{$p['Proj_Id']}' $selected>
                                    {$p['Proj_Name']}
                                </option>";
                        }
                        ?>
                    </select>
                </form>

                <!-- RIGHT: ADD BUTTON -->
                <button class="btn btn-primary-custom"
                        data-bs-toggle="modal"
                        data-bs-target="#addModal">
                    + Add Assignment
                </button>
            </div>
            <!-- TABLE -->
            <div class="card card-modern p-4">
                <h5 class="mb-3">Assignment List</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Job</th>
                            <th>Charge Hours</th>
                            <th>Status</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $projId = isset($_GET['proj_filter']) ? $_GET['proj_filter'] : 0;
                            $assignments = mysqli_query($conn, "
                            SELECT a.*, 
                                e.Emp_Id, e.Emp_FName, e.Emp_LName,
                                acc.Acct_Email,
                                j.Job_Title,
                                p.Proj_Name,
                                c.Comp_Name
                            FROM Assignments a
                            JOIN Employees e ON a.Assgn_EmpId = e.Emp_Id
                            JOIN Accounts acc ON e.Emp_AcctId = acc.Acct_Id
                            JOIN Jobs j ON a.Assgn_JobId = j.Job_Id
                            JOIN Projects p ON a.Assgn_ProjId = p.Proj_Id
                            JOIN Companies c ON p.Proj_CompId = c.Comp_Id
                            WHERE p.Proj_ManagerId = '$managerId'
                            AND p.Proj_Id = '$projId'
                        ");

                        while($row = mysqli_fetch_assoc($assignments)):
                        ?>
                        <tr>
                            <td><?= $row['Emp_FName'] . " " . $row['Emp_LName'] ?></td>
                            <td><?= $row['Job_Title'] ?></td>
                            <td><?= $row['Assgn_Charge'] ?> hrs</td>
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
                            <td>
                                <?php
                                    $status = $row['Assgn_Status'];
                                    $isLocked = !in_array($status, ['pending','denied']);
                                ?>
                                <div class="d-flex gap-2">

                                    <!-- DETAILS -->
                                    <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#details<?= $row['Assgn_Id'] ?>">
                                        Details
                                    </button>

                                    <!-- EDIT -->
                                    <button class="btn btn-sm btn-outline-primary"
                                        <?= $isLocked ? 'disabled' : '' ?>
                                        data-bs-toggle="modal"
                                        data-bs-target="#edit<?= $row['Assgn_Id'] ?>">
                                        Edit
                                    </button>

                                    <!-- REMOVE -->
                                    <a href="?delete=<?= $row['Assgn_Id'] ?>&proj_filter=<?= $projId ?>"
                                    class="btn btn-sm btn-outline-danger <?= $isLocked ? 'disabled' : '' ?>"
                                    onclick="return <?= $isLocked ? 'false' : 'confirm(\'Are you sure?\')' ?>">
                                        Remove
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <!-- DETAILS MODAL -->
                        <div class="modal fade" id="details<?= $row['Assgn_Id'] ?>">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 shadow">

                                <!-- HEADER -->
                                <div class="modal-header border-0">
                                    <h5 class="modal-title">Assignment Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <!-- BODY -->
                                <div class="modal-body px-4">
                                    <h5 class="mb-3">
                                        <?= htmlspecialchars($row['Emp_FName']." ".$row['Emp_LName']) ?>
                                    </h5>

                                    <p class="text-muted mb-1"><strong>Employee ID:</strong> <?= $row['Emp_Id'] ?></p>
                                    <p class="text-muted mb-1"><strong>Company:</strong> <?= htmlspecialchars($row['Comp_Name']) ?></p>
                                    <p class="text-muted mb-1"><strong>Email:</strong> <?= htmlspecialchars($row['Acct_Email']) ?></p>
                                    <p class="text-muted mb-1"><strong>Project:</strong> <?= htmlspecialchars($row['Proj_Name']) ?></p>
                                    <p class="text-muted mb-1"><strong>Job Role:</strong> <?= htmlspecialchars($row['Job_Title']) ?></p>
                                    <p class="text-muted mb-1"><strong>Charge:</strong> <?= $row['Assgn_Charge'] ?> hrs</p>
                                    <?php if($row['Assgn_Status'] === 'denied' && !empty($row['Assgn_Notes'])): ?>
                                    <p class="text-danger mt-2"><strong>Reason:</strong> <?= htmlspecialchars($row['Assgn_Notes']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- FOOTER -->
                                <div class="modal-footer border-0">
                                    <button class="btn btn-primary-custom w-100" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>

                                </div>
                            </div>
                        </div>
                        <!-- EDIT MODAL -->
                        <div class="modal fade" id="edit<?= $row['Assgn_Id'] ?>">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 shadow">

                                <div class="modal-header border-0">
                                    <h5 class="modal-title">Edit Assignment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <form method="POST">

                                    <input type="hidden" name="assign_id" value="<?= $row['Assgn_Id'] ?>">
                                    <input type="hidden" name="proj_id" value="<?= $_GET['proj_filter'] ?>">

                                    <div class="modal-body">
                                        <!-- LOCKED EMPLOYEE NAME -->
                                        <input type="text"
                                            class="form-control mb-2"
                                            value="<?= htmlspecialchars($row['Emp_FName']." ".$row['Emp_LName']) ?>"
                                            readonly>

                                        <!-- HIDDEN EMPLOYEE ID -->
                                        <input type="hidden"
                                            name="emp_id"
                                            value="<?= $row['Assgn_EmpId'] ?>">

                                        <!-- JOB -->
                                        <select name="job_id" class="form-control mb-2" required>
                                            <?php
                                            $jobs = mysqli_query($conn, "
                                                SELECT * FROM Jobs 
                                                WHERE Job_CompId = '$companyId'
                                                AND Job_Status = 'active'
                                            ");
                                            while($j = mysqli_fetch_assoc($jobs)){
                                                $selected = ($j['Job_Id'] == $row['Assgn_JobId']) ? 'selected' : '';
                                                echo "<option value='{$j['Job_Id']}' $selected>
                                                    {$j['Job_Title']}
                                                </option>";
                                            }
                                            ?>
                                        </select>

                                        <!-- CHARGE -->
                                        <input type="number"
                                            name="charge"
                                            value="<?= $row['Assgn_Charge'] ?>"
                                            class="form-control"
                                            required>

                                    </div>

                                    <div class="modal-footer border-0">
                                        <button type="submit" name="update_assignment"
                                            class="btn btn-primary-custom w-100">
                                            Update
                                        </button>
                                    </div>

                                </form>

                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($assignments) == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    No assignments yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">

      <!-- HEADER -->
      <div class="modal-header border-0">
        <h5 class="modal-title">Add Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <form method="POST">

        <!--AUTO PROJECT -->
        <input type="hidden" name="proj_id" value="<?= isset($_GET['proj_filter']) ? $_GET['proj_filter'] : '' ?>">

        <div class="modal-body">

            <!-- EMPLOYEE -->
            <select name="emp_id" class="form-control mb-2" required>
                <option value="">Select Employee</option>

                <?php
                $projId = isset($_GET['proj_filter']) ? $_GET['proj_filter'] : 0;
                $emps = mysqli_query($conn, "
                    SELECT e.*
                    FROM Employees e
                    JOIN Accounts a ON e.Emp_AcctId = a.Acct_Id
                    WHERE e.Emp_CompId = '$companyId'
                    AND a.Acct_Status = 'active'

                    -- REMOVE ALL MANAGERS
                    AND e.Emp_Id NOT IN (
                        SELECT DISTINCT Proj_ManagerId
                        FROM Projects
                        WHERE Proj_ManagerId IS NOT NULL
                    )

                    -- REMOVE ALREADY ASSIGNED EMPLOYEES
                    AND e.Emp_Id NOT IN (
                        SELECT Assgn_EmpId
                        FROM Assignments
                        WHERE Assgn_ProjId = '$projId'
                    )
                ");
                while($e = mysqli_fetch_assoc($emps)){
                    echo "<option value='{$e['Emp_Id']}'>
                            {$e['Emp_FName']} {$e['Emp_LName']}
                        </option>";
                }
                ?>
            </select>

            <select name="job_id" class="form-control mb-2" required>
                <option value="">Select Job</option>
                <?php
                $jobs = mysqli_query($conn, "
                    SELECT *
                    FROM Jobs
                    WHERE Job_CompId = '$companyId'
                    AND Job_Status = 'active'
                ");

                while($j = mysqli_fetch_assoc($jobs)){
                    echo "<option value='{$j['Job_Id']}'>
                            {$j['Job_Title']}
                        </option>";
                }
                ?>
            </select>

            <!-- CHARGE -->
            <input type="number" min="1" step="0.01" name="charge" class="form-control" placeholder="Charge Hours" required>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0">
          <button type="submit" name="add_assignment" class="btn btn-primary-custom w-100">
            Add
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const s = document.getElementById('successToast');
    const e = document.getElementById('errorToast');

    if(s) new bootstrap.Toast(s,{delay:2000}).show();
    if(e) new bootstrap.Toast(e,{delay:2000}).show();
});
</script>