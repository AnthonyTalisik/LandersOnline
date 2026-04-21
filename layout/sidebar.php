<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>

<div class="col-md-3 col-lg-2 sidebar p-4">

    <!-- ========================= -->
    <!-- COMPANY SIDEBAR -->
    <!-- ========================= -->
    <?php if($role == 'company'): ?>

        <h6 class="text-muted mb-4">Company Panel</h6>

        <ul class="nav flex-column">

            <!-- DASHBOARD -->
            <li class="nav-item mb-2">
                <a href="/dbweb/company/company_dashboard.php"
                   class="nav-link sidebar-link <?= ($currentPage == 'company_dashboard.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- MANAGEMENT -->
            <li class="nav-item mb-2">

                <a class="nav-link sidebar-link d-flex justify-content-between align-items-center"
                   data-bs-toggle="collapse"
                   href="#manageMenu">
                    <span><i class="bi bi-gear me-2"></i>Management</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse 
                    <?= in_array($currentPage, [
                        'manage_employee.php',
                        'manage_job.php',
                        'manage_project.php'
                    ]) ? 'show' : '' ?>" id="manageMenu">

                    <ul class="nav flex-column ms-3 mt-2">

                        <!-- EMPLOYEES -->
                        <li>
                            <a href="/dbweb/company/manage_employee.php"
                               class="nav-link sidebar-sublink <?= ($currentPage == 'manage_employee.php') ? 'active-sidebar' : '' ?>">
                                <i class="bi bi-people me-2"></i>
                                Employees
                            </a>
                        </li>

                        <!-- JOBS -->
                        <li>
                            <a href="/dbweb/company/manage_job.php"
                               class="nav-link sidebar-sublink <?= ($currentPage == 'manage_job.php') ? 'active-sidebar' : '' ?>">
                                <i class="bi bi-briefcase me-2"></i>
                                Jobs
                            </a>
                        </li>

                        <!-- PROJECTS -->
                        <li>
                            <a href="/dbweb/company/manage_project.php"
                               class="nav-link sidebar-sublink <?= ($currentPage == 'manage_project.php') ? 'active-sidebar' : '' ?>">
                                <i class="bi bi-folder me-2"></i>
                                Projects
                            </a>
                        </li>

                    </ul>
                </div>
            </li>

        </ul>

    <!-- ========================= -->
    <!-- MANAGER SIDEBAR -->
    <!-- ========================= -->
    <?php elseif($role == 'employee' && ($_SESSION['is_manager'] ?? false)): ?>

        <h6 class="text-muted mb-4">Manager Panel</h6>

        <ul class="nav flex-column">

            <li class="nav-item mb-2">
                <a href="/dbweb/employee/manager_dashboard.php"
                   class="nav-link sidebar-link <?= ($currentPage == 'manager_dashboard.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a href="/dbweb/employee/manage_assignment.php"
                   class="nav-link sidebar-link <?= ($currentPage == 'manage_assignment.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-diagram-3 me-2"></i>
                    Assignments
                </a>
            </li>

            <li class="nav-item mb-2">
                <a href="/dbweb/employee/manager_teamview.php"
                   class="nav-link sidebar-link <?= ($currentPage == 'manager_teamview.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-folder me-2"></i>
                    Project Teams
                </a>
            </li>

        </ul>

    <!-- ========================= -->
    <!-- EMPLOYEE SIDEBAR -->
    <!-- ========================= -->
    <?php else: ?>

        <h6 class="text-muted mb-4">Employee Panel</h6>

        <ul class="nav flex-column">

            <!-- DASHBOARD -->
            <li class="nav-item mb-2">
                <a href="/dbweb/employee/employee_dashboard.php"
                class="nav-link sidebar-link <?= ($currentPage == 'employee_dashboard.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- ASSIGNMENTS -->
            <li class="nav-item mb-2">
                <a href="/dbweb/employee/employee_assignment.php"
                class="nav-link sidebar-link <?= ($currentPage == 'employee_assignment.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-diagram-3 me-2"></i>
                    Assignments
                </a>
            </li>

            <!-- PROJECT TEAMS -->
            <li class="nav-item mb-2">
                <a href="/dbweb/employee/employee_teams.php"
                class="nav-link sidebar-link <?= ($currentPage == 'employee_teams.php') ? 'active-sidebar' : '' ?>">
                    <i class="bi bi-people me-2"></i>
                    Project Teams
                </a>
            </li>

        </ul>

    <?php endif; ?>

</div>