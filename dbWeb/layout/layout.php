<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $title ?? "WorkSphere"; ?></title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

        <style>
            body{
                background:#f4f1ec;
                font-family: 'Georgia', serif;
                padding-top:70px; 
                overflow-y: scroll;
            }
            
            /* NAVBAR */
            .navbar-custom{
                background:#ffffff;
                padding:12px 0;
                transition: all 0.3s ease;
            }

            .navbar-brand{
                font-weight:600;
                font-size:20px;
            }

            /* HERO */
            .hero-container{
                background:#ffffff;
                border-radius:30px;
                padding:70px;
                box-shadow:0 20px 40px rgba(0,0,0,0.06);
                position:relative;
                overflow:hidden;
            }

            .hero-container:before{
                content:"";
                position:absolute;
                width:380px;          
                height:380px;
                background:#e8e1d8;
                border-radius:50%;
                top:-90px;            
                right:-60px;
                z-index:0;
            }

            .hero-container{
                background:#ffffff;
                border-radius:30px;
                padding:70px;
                box-shadow:0 20px 40px rgba(0,0,0,0.06);
                position:relative;
                overflow:hidden;
            }


            .hero-image-wrapper{
                position:absolute;

                width:320px;
                height:320px;

                top:40px;
                right:30px;

                border-radius:50%;
                overflow:hidden;

                box-shadow: none;

                z-index:2;
            }

            .hero-image{
                width:100%;
                height:100%;
                object-fit:cover;      
                border-radius:50%;     
            }
            .hero-title{
                font-size:60px;
                font-weight:500;
                line-height:1.2;
            }

            .highlight{
                color:#e46a2e;
                font-style:italic;
            }

            .hero-text{
                color:#6c757d;
                font-size:18px;
                margin-top:20px;
            }

            .hero-bg-image{
                position:absolute;
                right:40px;
                bottom:0;
                width:380px;
                opacity:0.15;   
                border-radius:20px;
            }

            .btn-primary-custom{
                background:#8c6d5a;
                border:none;
                padding:8px 20px;
                border-radius:50px;
                font-size:14px;
                color:white;
            }

            .btn-primary-custom:hover{
                background:#6d5444;
                color:white;
            }

            /* CARD STYLE */
            .card-modern{
                border:none;
                border-radius:20px;
                box-shadow:0 10px 25px rgba(0,0,0,0.05);
            }

            /* CIRCLE IMAGE */

            .circle-image-wrapper{
                width:360px;
                height:360px;
                border-radius:50%;
                overflow:hidden;
                position:relative;
                z-index:2;
            }

            .circle-image{
                width:100%;
                height:100%;
                object-fit:cover;
            }

            /* SIDEBAR */
            .sidebar{
                background:#f8f6f2;
                min-height:calc(100vh - 70px); 
                border-right:1px solid #e0ddd8;
                position:sticky;
                top:70px; 
            }

            /* Main link */
            .sidebar-link{
                color:#444;
                padding:10px 12px;
                border-left:4px solid transparent;
                border-radius:6px;
                transition: all 0.3s ease;
                font-weight:500;
            }

            .collapse{
                transition: all 0.3s ease;
            }

            .sidebar-link:hover{
                background:#eee8df;
            }

            /* Sub links */
            .sidebar-sublink{
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 16px; 
                border-left: 3px solid transparent;
                border-radius: 10px;
                transition: all 0.25s ease;
                font-weight: 500;
                color: #444 !important;
                text-decoration: none;
            }

            .sidebar-sublink:visited {
                color: #444 !important;
            }

            .sidebar-sublink i {
                width: 20px;
                text-align: center;
                font-size: 16px;
            }

            .sidebar-sublink {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 8px 10px 16px;
                font-weight: 500;
                color: #444;
                text-decoration: none;
            }

            .sidebar-sublink:hover {
                background:#f7f3ee;
            }

            /* Collapsed container styling */
            .sidebar .collapse.show {
                background: #ffffff;
                border-radius: 12px;
                padding: 8px 6px;
                margin-top: 6px;
                box-shadow: 0 8px 20px rgba(0,0,0,0.06);
                transition: all 0.3s ease;
                border-left: 3px solid #8c6d5a;
            }

            /* Remove default list spacing */
            .sidebar .collapse ul {
                padding-left: 0;
                margin-bottom: 0;
            }

            /* Slight animation */
            .sidebar .collapsing {
                transition: height 0.3s ease;
            }

            /* ACTIVE LEFT BORDER */
            .active-sidebar{
                border-left:3px solid #8c6d5a;
                background:#f0ebe5;
                color:#8c6d5a !important;
            }

            /* DASHBOARD CARDS */
            .dashboard-card{
                background:#ffffff;
                border-radius:18px;
                box-shadow:0 10px 30px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }

            .dashboard-card:hover{
                transform: translateY(-5px);
                box-shadow:0 15px 35px rgba(0,0,0,0.08);
            }

            .dashboard-icon{
                font-size:28px;
                color:#8c6d5a;
            }

            .btn-sm {
                min-width: 90px;
            }
        </style>
    </head>
    <body>
        <!-- FIXED NAVBAR -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top shadow-sm">
            <div class="container">

                <a class="navbar-brand" href="/dbweb/index.php">
                    WorkSphere
                </a>

                <div>
                <?php if(isset($_SESSION['account_id'])): ?>

                    <div class="dropdown">
                        <button class="btn btn-primary-custom dropdown-toggle"
                                data-bs-toggle="dropdown">
                            <?= $_SESSION['display_name'] ?? 'User' ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="bi bi-person-circle me-2"></i>
                                    Profile
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item text-danger"
                                href="/dbweb/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>
                            </li>

                        </ul>
                    </div>

                <?php else: ?>

                    <a href="/dbweb/auth/login.php" class="btn btn-primary-custom">
                        Login
                    </a>

                <?php endif; ?>
                </div>

            </div>
        </nav>
        <div class="container my-5">
        </div> <!-- container -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <!-- PROFILE MODAL -->
        <div class="modal fade" id="profileModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 shadow">

                <div class="modal-header border-0">
                    <h5 class="modal-title">Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">

                    <div class="mb-3">
                        <i class="bi bi-person-circle" style="font-size:60px; color:#8c6d5a;"></i>
                    </div>

                    <h5><?= $_SESSION['display_name'] ?? 'User' ?></h5>

                    <p class="text-muted mb-1">
                        Account ID: <?= $_SESSION['account_id'] ?? '' ?>
                    </p>

                    <p class="text-muted mb-1">
                        Role: <?= ucfirst($_SESSION['role'] ?? '') ?>
                    </p>

                    <p class="text-muted mb-1">
                        Email: <?= $_SESSION['email'] ?? 'N/A' ?>
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
    </body>
</html>