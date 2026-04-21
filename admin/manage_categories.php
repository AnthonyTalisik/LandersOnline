<?php
ob_start();
session_start();
require_once("../config/db.php");

// Check if company logged in
if(!isset($_SESSION['account_id']) || $_SESSION['role'] != 'company'){
    header("Location: /dbweb/auth/login.php");
    exit();
}

$companyId = $_SESSION['company_id'];

// =========================
// ADD EMPLOYEE
// =========================
if(isset($_POST['add_employee'])){

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];

    // CHECK DUPLICATE EMAIL FIRST
    $check = $conn->prepare("
        SELECT Acct_Id FROM Accounts WHERE Acct_Email = ?
    ");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $_SESSION['error'] = "Email already registered!";
        header("Location: manage_employee.php");
        exit();

    }

    // Default password
    $defaultPassword = "123456";
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Generate Account ID
    $res1 = $conn->query("SELECT MAX(Acct_Id) AS max_id FROM Accounts");
    $row1 = $res1->fetch_assoc();
    $acctId = (!empty($row1['max_id'])) ? $row1['max_id'] + 1 : 1001;

    // Generate Employee ID
    $res2 = $conn->query("SELECT MAX(Emp_Id) AS max_id FROM Employees");
    $row2 = $res2->fetch_assoc();
    $empId = (!empty($row2['max_id'])) ? $row2['max_id'] + 1 : 3001;

    // INSERT ACCOUNT
    $stmt1 = $conn->prepare("
        INSERT INTO Accounts 
        (Acct_Id, Acct_Email, Acct_Password, Acct_Role, Acct_MustChangePassword, Acct_Status)
        VALUES (?, ?, ?, 'employee', TRUE, 'active')
    ");
    $stmt1->bind_param("iss", $acctId, $email, $hashedPassword);
    if(!$stmt1->execute()){
        $_SESSION['error'] = "Error creating account.";
        header("Location: manage_employee.php");
        exit();
    }

    // INSERT EMPLOYEE
    $stmt2 = $conn->prepare("
        INSERT INTO Employees 
        (Emp_Id, Emp_CompId, Emp_AcctId, Emp_FName, Emp_LName) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("iiiss", $empId, $companyId, $acctId, $fname, $lname);

    if(!$stmt2->execute()){
        $_SESSION['error'] = "Error creating employee.";
        header("Location: manage_employee.php");
        exit();
    }

    $_SESSION['success'] = "Employee added! Default password: 123456";
    header("Location: manage_employee.php");
    exit();
}

// =========================
// UPDATE EMPLOYEE
// =========================
if(isset($_POST['update_employee'])){

    $empId = $_POST['emp_id'];
    $acctId = $_POST['acct_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];

    // CHECK IF EMAIL ALREADY EXISTS (EXCEPT CURRENT USER)
    $check = $conn->prepare("
        SELECT Acct_Id FROM Accounts 
        WHERE Acct_Email = ? AND Acct_Id != ?
    ");
    $check->bind_param("si", $email, $acctId);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){

        $_SESSION['error'] = "Email already exists!";
        header("Location: manage_employee.php");
        exit();

    } else {

        // UPDATE EMPLOYEE
        $stmt = $conn->prepare("
            UPDATE Employees 
            SET Emp_FName=?, Emp_LName=? 
            WHERE Emp_Id=?
        ");
        $stmt->bind_param("ssi", $fname, $lname, $empId);
        $stmt->execute();

        // UPDATE ACCOUNT EMAIL
        $stmt2 = $conn->prepare("
            UPDATE Accounts 
            SET Acct_Email=? 
            WHERE Acct_Id=?
        ");
        $stmt2->bind_param("si", $email, $acctId);
        $stmt2->execute();

        $_SESSION['success'] = "Employee updated successfully.";
        header("Location: manage_employee.php");
        exit();
    }
}

// =========================
// TOGGLE ACTIVE / INACTIVE
// =========================
if(isset($_GET['toggle_status'])){

    $acctId = $_GET['toggle_status'];

    // PREPARED STATEMENT
    $stmt = $conn->prepare("
        SELECT Acct_Status FROM Accounts WHERE Acct_Id = ?
    ");
    $stmt->bind_param("i", $acctId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if(!$data){
        $_SESSION['error'] = "Employee not found.";
        header("Location: manage_employee.php");
        exit();
    }

    $newStatus = ($data['Acct_Status'] == 'active') ? 'inactive' : 'active';
    $stmt2 = $conn->prepare("
        UPDATE Accounts SET Acct_Status=? WHERE Acct_Id=?
    ");
    $stmt2->bind_param("si", $newStatus, $acctId);
    $stmt2->execute();

    $_SESSION['success'] = "Employee status updated.";
    header("Location: manage_employee.php");
    exit();
}

// =========================
// LOAD UI
// =========================
$title = "Manage Employees";
include("../layout/layout.php");
?>

<?php if(isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast" class="toast text-white bg-success border-0">
        <div class="toast-body">
            <?= $_SESSION['success']; ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="errorToast" class="toast text-white bg-danger border-0">
        <div class="toast-body">
            <?= $_SESSION['error']; ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container-fluid">
<div class="row">

<?php include("../layout/sidebar.php"); ?>

    <div class="col-md-9 col-lg-10 p-5">
        <!-- ADD EMPLOYEE FORM -->
        <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Manage Employees</h3>

        <button class="btn btn-primary-custom"
                data-bs-toggle="modal"
                data-bs-target="#addEmployeeModal">
            <i class="bi bi-plus-circle me-2"></i>
            Add Employee
        </button>
    </div>

    <!-- EMPLOYEE LIST -->
    <div class="card card-modern p-4">
        <h5 class="mb-3">Employee List</h5>

        <table class="table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $employees = mysqli_query($conn, "
                SELECT e.*, a.Acct_Email, a.Acct_Status, c.Comp_Name
                FROM Employees e
                JOIN Accounts a ON e.Emp_AcctId = a.Acct_Id
                JOIN Companies c ON e.Emp_CompId = c.Comp_Id
                WHERE e.Emp_CompId = '$companyId'
            ");

            while($row = mysqli_fetch_assoc($employees)):
            ?>
                <tr>
                    <td><?= $row['Emp_FName'] . " " . $row['Emp_LName'] ?></td>
                    <td><?= $row['Acct_Email'] ?></td>
                    <td>
                        <?php if($row['Acct_Status'] == 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end align-middle pe-5">
                        <div class="d-flex justify-content-end gap-2">

                            <!-- DETAILS -->
                            <button class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailsModal<?= $row['Emp_Id'] ?>">
                                Details
                            </button>

                            <!-- EDIT -->
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal<?= $row['Emp_Id'] ?>">
                                Edit
                            </button>

                            <!-- TOGGLE BUTTON -->
                            <?php if($row['Acct_Status'] == 'active'): ?>

                                <a href="manage_employee.php?toggle_status=<?= $row['Emp_AcctId'] ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Deactivate this employee?')">
                                    Deactivate
                                </a>

                            <?php else: ?>

                                <a href="manage_employee.php?toggle_status=<?= $row['Emp_AcctId'] ?>"
                                class="btn btn-sm btn-outline-success"
                                onclick="return confirm('Activate this employee?')">
                                    Activate
                                </a>

                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <div class="modal fade" id="detailsModal<?= $row['Emp_Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow">

                        <div class="modal-header border-0">
                            <h5 class="modal-title">Employee Profile</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-center">

                            <i class="bi bi-person-circle" style="font-size:60px; color:#8c6d5a;"></i>

                            <h5 class="mt-3">
                                <?= $row['Emp_FName'] . " " . $row['Emp_LName'] ?>
                            </h5>

                            <p class="text-muted mb-1">
                                Employee ID: <?= $row['Emp_Id'] ?>
                            </p>

                            <p class="text-muted mb-1">
                                Company: <?= $row['Comp_Name'] ?>
                            </p>

                            <p class="text-muted mb-1">
                                Email: <?= $row['Acct_Email'] ?>
                            </p>

                            <p class="mt-2">
                                Status:
                                <?php if(($row['Acct_Status'] ?? 'active') == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </p>

                        </div>

                        <div class="modal-footer border-0 justify-content-center">
                            <button class="btn btn-primary-custom" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>

                        </div>
                    </div>
                    </div>
                    <div class="modal fade" id="editModal<?= $row['Emp_Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow">

                        <div class="modal-header border-0">
                            <h5 class="modal-title">Edit Employee</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST">

                        <div class="modal-body">

                                <input type="hidden" name="emp_id" value="<?= $row['Emp_Id'] ?>">
                                <input type="hidden" name="acct_id" value="<?= $row['Emp_AcctId'] ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" name="fname" class="form-control"
                                            value="<?= $row['Emp_FName'] ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <input type="text" name="lname" class="form-control"
                                            value="<?= $row['Emp_LName'] ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <input type="email" name="email" class="form-control"
                                        value="<?= $row['Acct_Email'] ?>" required>
                                </div>

                        </div>

                        <div class="modal-footer border-0">
                            <button type="submit" name="update_employee" class="btn btn-primary-custom w-100">
                                Update Employee
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
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">

      <div class="modal-header border-0">
        <h5 class="modal-title">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST">

      <div class="modal-body">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" name="fname" class="form-control" placeholder="First Name" required>
                </div>

                <div class="col-md-6 mb-3">
                    <input type="text" name="lname" class="form-control" placeholder="Last Name" required>
                </div>
            </div>

            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

      </div>

      <div class="modal-footer border-0">
        <button type="submit" name="add_employee" class="btn btn-primary-custom w-100">
            Add Employee
        </button>
      </div>

      </form>

    </div>
  </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const success = document.getElementById('successToast');
    const error = document.getElementById('errorToast');

    if(success){
        new bootstrap.Toast(success, { delay: 2000 }).show();
    }

    if(error){
        new bootstrap.Toast(error, { delay: 2000 }).show();
    }

});
</script>