<?php
    session_start();
    require_once "../config/db.php";

    if(isset($_POST['register'])){

        $companyName = $_POST['company_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Generate IDs
        $res1 = $conn->query("SELECT MAX(Acct_Id) AS max_id FROM Accounts");
        $row1 = $res1->fetch_assoc();
        $acctId = $row1['max_id'] ? $row1['max_id'] + 1 : 1001;

        $res2 = $conn->query("SELECT MAX(Comp_Id) AS max_id FROM Companies");
        $row2 = $res2->fetch_assoc();
        $compId = $row2['max_id'] ? $row2['max_id'] + 1 : 2001;

        // Check email
        $check = $conn->prepare("SELECT Acct_Id FROM Accounts WHERE Acct_Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $_SESSION['error'] = "Email already registered.";
            header("Location: register_company.php");
            exit();
        }

        // Insert account
        $stmt1 = $conn->prepare("INSERT INTO Accounts (Acct_Id, Acct_Email, Acct_Password, Acct_Role, Acct_MustChangePassword) 
            VALUES (?, ?, ?, 'company', FALSE)");
        $stmt1->bind_param("iss", $acctId, $email, $hashedPassword);
        $stmt1->execute();

        // Insert company
        $stmt2 = $conn->prepare("INSERT INTO Companies (Comp_Id, Comp_AcctId, Comp_Name) 
            VALUES (?, ?, ?)");
        $stmt2->bind_param("iis", $compId, $acctId, $companyName);
        $stmt2->execute();

        $_SESSION['success'] = "Company Registered Successfully!";
        header("Location: register_company.php");
        exit();
    }

    // LOAD UI
    $title = "Register Company";
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

<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card card-modern p-4">
            <h3 class="text-center mb-4">Register Company</h3>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" name="register" class="btn btn-primary-custom w-100">
                    Register
                </button>

            </form>
        </div>

    </div>
</div>

</div> <!-- container close -->
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
</body>
</html>