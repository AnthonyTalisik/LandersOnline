<?php
session_start();
require_once("../config/db.php");

if(!isset($_SESSION['account_id'])){
    header("Location: login.php");
    exit();
}

$accountId = $_SESSION['account_id'];

if(isset($_POST['change_password'])){

    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if($newPassword !== $confirmPassword){
        $_SESSION['error'] = "Passwords do not match.";
    }
    else if(strlen($newPassword) < 6){
        $_SESSION['error'] = "Password must be at least 6 characters.";
    }
    else{

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE Accounts 
            SET Acct_Password=?, Acct_MustChangePassword=0
            WHERE Acct_Id=?
        ");
        $stmt->bind_param("si", $hashed, $accountId);

        if($stmt->execute()){
            $_SESSION['success'] = "Password updated successfully!";
            
            session_destroy();

            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating password.";
        }
    }
}
?>

<?php
$title = "Change Password";
include(__DIR__ . "/../layout/layout.php");
?>

<!-- TOAST SUCCESS -->
<?php if(isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="successToast" class="toast text-white bg-success border-0">
        <div class="toast-body">
            <?= $_SESSION['success']; ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<!-- TOAST ERROR -->
<?php if(isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="errorToast" class="toast text-white bg-danger border-0">
        <div class="toast-body">
            <?= $_SESSION['error']; ?>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>


<div class="row justify-content-center">
    <div class="col-md-5">

        <div class="card card-modern p-4">
            <h4 class="text-center mb-4">Change Password</h4>

            <form method="POST">

                <div class="mb-3">
                    <input type="password" name="new_password"
                           class="form-control"
                           placeholder="New Password" required>
                </div>

                <div class="mb-3">
                    <input type="password" name="confirm_password"
                           class="form-control"
                           placeholder="Confirm Password" required>
                </div>

                <button type="submit" name="change_password"
                        class="btn btn-primary-custom w-100">
                    Update Password
                </button>

            </form>
        </div>

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

</body>
</html>