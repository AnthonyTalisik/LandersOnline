<?php
session_start();
require_once "../config/db.php";

$error = "";

if(isset($_POST['login'])){

    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM Accounts WHERE Acct_Email = '$email' LIMIT 1";
    $result = $conn->query($query);
    if($result->num_rows > 0){
        $account = $result->fetch_assoc();

        // Check inactive
        if($account['Acct_Status'] == 'inactive'){
            $error = "Account is inactive.";
        }
        else if(password_verify($password, $account['Acct_Password'])){

            // =========================
            // BASIC SESSION
            // =========================
            $_SESSION['account_id'] = $account['Acct_Id'];
            $_SESSION['role'] = $account['Acct_Role'];
            $_SESSION['email'] = $account['Acct_Email'];

            // =========================
            // COMPANY LOGIN
            // =========================
            if($account['Acct_Role'] == 'company'){

                $getCompany = mysqli_query($conn, "
                    SELECT Comp_Id, Comp_Name 
                    FROM Companies 
                    WHERE Comp_AcctId = '{$account['Acct_Id']}'
                    LIMIT 1
                ");

                $company = mysqli_fetch_assoc($getCompany);
                $_SESSION['display_name'] = $company['Comp_Name'];
                $_SESSION['company_id'] = $company['Comp_Id'];

                // FORCE PASSWORD CHANGE
                if($account['Acct_MustChangePassword'] == 1){
                    header("Location: change_password.php");
                    exit();
                }
                header("Location: ../company/company_dashboard.php");
                exit();
            }

            // =========================
            // EMPLOYEE LOGIN
            // =========================
            else if($account['Acct_Role'] == 'employee'){
                $getEmp = mysqli_query($conn, "
                    SELECT Emp_Id, Emp_FName, Emp_LName, Emp_CompId 
                    FROM Employees 
                    WHERE Emp_AcctId = '{$account['Acct_Id']}'
                    LIMIT 1
                ");
                $emp = mysqli_fetch_assoc($getEmp);

                // FULL NAME FIX HERE
                $_SESSION['display_name'] = $emp['Emp_FName'] . " " . $emp['Emp_LName'];
                $_SESSION['company_id'] = $emp['Emp_CompId'];
                $_SESSION['employee_id'] = $emp['Emp_Id'];

                // =========================
                // 🔍 CHECK IF MANAGER
                // =========================
                $checkManager = mysqli_query($conn, "
                    SELECT 1 
                    FROM Projects 
                    WHERE Proj_ManagerId = '{$emp['Emp_Id']}'
                    LIMIT 1
                ");
                $_SESSION['is_manager'] = (mysqli_num_rows($checkManager) > 0);

                // =========================
                // FORCE PASSWORD CHANGE
                // =========================
                if($account['Acct_MustChangePassword'] == 1){
                    header("Location: change_password.php");
                    exit();
                }

                // =========================
                // REDIRECT BASED ON ROLE
                // =========================
                if($_SESSION['is_manager']){
                    header("Location: ../employee/manager_dashboard.php");
                } else {
                    header("Location: ../employee/employee_dashboard.php");
                }
                exit();
            }

        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>

<?php
$title = "Login";
include("../layout/layout.php");
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-modern p-4">
            <h3 class="text-center mb-4">Login</h3>
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary-custom w-100">
                    Login
                </button>
            </form>
        </div>
    </div>
</div>
</div>
</body>
</html>