<?php 
    session_start();
    session_destroy();
    $title="Home"; 
    include("layout/layout.php");
?>
<div class="hero-container position-relative">

    <div class="row align-items-center">

        <!-- LEFT SIDE TEXT -->
        <div class="col-lg-7">
            <h1 class="hero-title">
                Manage Your
                <span class="highlight">Projects</span> &
                <span class="highlight">Employees</span> Easily
            </h1>

            <p class="hero-text">
                Create projects, assign jobs, and manage employees
                efficiently inside one centralized system.
            </p>

            <a href="company/register_company.php" class="btn btn-primary-custom mt-3">
                Register Company →
            </a>
        </div>

        <!-- RIGHT SIDE IMAGE -->
        <div class="hero-image-wrapper">
            <img src="assets/images/workspace.jpg" class="hero-image">
        </div>

    </div>
</div>
</div>
</body>
</html>
