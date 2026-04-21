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
// ADD JOB
// =========================
if(isset($_POST['add_job'])){

    $jobTitle = $_POST['job_title'];
    $jobRate  = $_POST['job_rate'];

    $res = $conn->query("SELECT MAX(Job_Id) AS max_id FROM Jobs");
    $row = $res->fetch_assoc();
    $jobId = (!empty($row['max_id'])) ? $row['max_id'] + 1 : 4001;

    $stmt = $conn->prepare("
        INSERT INTO Jobs (Job_Id, Job_CompId, Job_Title, Job_Rate, Job_Status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("iisd", $jobId, $companyId, $jobTitle, $jobRate);

    if($stmt->execute()){
        $_SESSION['success'] = "Job added successfully!";
    } else {
        $_SESSION['error'] = "Error adding job.";
    }

    header("Location: manage_job.php");
    exit();
}


// =========================
// UPDATE JOB
// =========================
if(isset($_POST['update_job'])){

    $jobId = $_POST['job_id'];
    $jobTitle = $_POST['job_title'];
    $jobRate  = $_POST['job_rate'];

    $stmt = $conn->prepare("
        UPDATE Jobs 
        SET Job_Title=?, Job_Rate=?
        WHERE Job_Id=?
    ");
    $stmt->bind_param("sdi", $jobTitle, $jobRate, $jobId);
    $stmt->execute();

    $_SESSION['success'] = "Job updated successfully.";
    header("Location: manage_job.php");
    exit();
}


// =========================
// TOGGLE STATUS
// =========================
if(isset($_GET['toggle_job_status'])){

    $jobId = $_GET['toggle_job_status'];

    $res = mysqli_query($conn, "
        SELECT Job_Status FROM Jobs WHERE Job_Id = '$jobId'
    ");
    $data = mysqli_fetch_assoc($res);

    $newStatus = ($data['Job_Status'] == 'active') ? 'inactive' : 'active';

    mysqli_query($conn, "
        UPDATE Jobs SET Job_Status = '$newStatus'
        WHERE Job_Id = '$jobId'
    ");

    $_SESSION['success'] = "Job status updated.";
    header("Location: manage_job.php");
    exit();
}


// =========================
// LOAD UI
// =========================
$title = "Manage Jobs";
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Manage Jobs</h3>

        <button class="btn btn-primary-custom"
                data-bs-toggle="modal"
                data-bs-target="#addJobModal">
            <i class="bi bi-plus-circle me-2"></i>
            Add Job
        </button>
    </div>

    <!-- JOB LIST -->
    <div class="card card-modern p-4">
        <h5 class="mb-3">Job List</h5>

        <table class="table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Rate Per Hour</th>
                    <th>Status</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $jobs = mysqli_query($conn, "
                SELECT j.*, c.Comp_Name
                FROM Jobs j
                JOIN Companies c ON j.Job_CompId = c.Comp_Id
                WHERE j.Job_CompId = '$companyId'
            ");

            while($row = mysqli_fetch_assoc($jobs)):
            ?>

            <tr>
                <td><?= $row['Job_Title'] ?></td>
                <td>₱<?= number_format($row['Job_Rate'], 2) ?></td>

                <td>
                    <?php if($row['Job_Status'] == 'active'): ?>
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
                                data-bs-target="#detailsModal<?= $row['Job_Id'] ?>">
                            Details
                        </button>

                        <!-- EDIT -->
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $row['Job_Id'] ?>">
                            Edit
                        </button>

                        <!-- TOGGLE -->
                        <?php if($row['Job_Status'] == 'active'): ?>
                            <a href="manage_job.php?toggle_job_status=<?= $row['Job_Id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Deactivate this job?')">
                                Inactive
                            </a>
                        <?php else: ?>
                            <a href="manage_job.php?toggle_job_status=<?= $row['Job_Id'] ?>"
                               class="btn btn-sm btn-outline-success"
                               onclick="return confirm('Activate this job?')">
                                Active
                            </a>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>

            <!-- DETAILS MODAL -->
            <div class="modal fade" id="detailsModal<?= $row['Job_Id'] ?>">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow">

                        <div class="modal-header border-0">
                            <h5 class="modal-title">Job Details</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body text-center">

                            <i class="bi bi-briefcase" style="font-size:60px; color:#8c6d5a;"></i>

                            <h5 class="mt-3"><?= $row['Job_Title'] ?></h5>

                            <p class="text-muted mb-1">Job ID: <?= $row['Job_Id'] ?></p>
                            <p class="text-muted mb-1">Company: <?= $row['Comp_Name'] ?></p>
                            <p class="text-muted mb-1">Rate: ₱<?= number_format($row['Job_Rate'], 2) ?></p>

                            <p class="mt-2">
                                Status:
                                <?php if($row['Job_Status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </p>

                        </div>

                        <div class="modal-footer border-0">
                            <button class="btn btn-primary-custom" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- EDIT MODAL -->
            <div class="modal fade" id="editModal<?= $row['Job_Id'] ?>">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow">

                        <div class="modal-header border-0">
                            <h5>Edit Job</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST">
                            <div class="modal-body">

                                <input type="hidden" name="job_id" value="<?= $row['Job_Id'] ?>">

                                <div class="mb-3">
                                    <input type="text" name="job_title"
                                        value="<?= $row['Job_Title'] ?>"
                                        class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <input type="number" step="0.01" name="job_rate"
                                        value="<?= $row['Job_Rate'] ?>"
                                        class="form-control" required>
                                </div>

                            </div>

                            <div class="modal-footer border-0">
                                <button type="submit" name="update_job"
                                        class="btn btn-primary-custom w-100">
                                    Update Job
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
<!-- ADD JOB MODAL -->
<div class="modal fade" id="addJobModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">

      <div class="modal-header border-0">
        <h5>Add Job</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST">
      <div class="modal-body">

            <div class="mb-3">
                <input type="text" name="job_title"
                       class="form-control"
                       placeholder="Job Title" required>
            </div>

            <div class="mb-3">
                <input type="number" step="0.01" name="job_rate"
                       class="form-control"
                       placeholder="Rate" required>
            </div>

      </div>

      <div class="modal-footer border-0">
        <button type="submit" name="add_job"
                class="btn btn-primary-custom w-100">
            Add Job
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