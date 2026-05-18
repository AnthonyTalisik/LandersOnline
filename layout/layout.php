<?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= $title ?? 'LandersOnline' ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                :root {
                    --green-dark: #3a6b1a;
                    --green-main: #4c8c23;
                    --green-light: #6aaf35;
                    --green-bg: #f4fbed;
                    --text-dark: #1a2e0a;
                    --text-muted: #5a6a4a;
                    --white: #ffffff;
                    --border: #d4e8bb;
                }

                * {
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Arial, sans-serif;
                    background: #f7f7f7;
                    margin: 0;
                    padding-top: 110px;
                }

                /* ── TOP NAV (dark olive bar) ── */
                .topbar {
                    background: var(--green-dark);
                    color: #fff;
                    font-size: 12px;
                    padding: 4px 0;
                }

                .topbar a {
                    color: #c8e6a0;
                    text-decoration: none;
                }

                .topbar a:hover {
                    color: #fff;
                }

                /* ── MAIN NAVBAR ── */
                .main-nav {
                    background: var(--white);
                    border-bottom: 2px solid var(--border);
                    padding: 10px 0;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    z-index: 1000;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
                }

                .nav-inner {
                    max-width: 1300px;
                    margin: 0 auto;
                    padding: 0 16px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                /* Logo */
                .nav-logo {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    text-decoration: none;
                    flex-shrink: 0;
                }

                .nav-logo .logo-circle {
                    width: 38px;
                    height: 38px;
                    background: var(--green-main);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-weight: 900;
                    font-size: 18px;
                }

                .nav-logo span {
                    font-size: 22px;
                    font-weight: 800;
                    color: var(--green-dark);
                    letter-spacing: 2px;
                }

                /* Search bar */
                .nav-search {
                    flex: 1;
                    display: flex;
                    border: 2px solid var(--border);
                    border-radius: 6px;
                    overflow: hidden;
                    max-width: 600px;
                }

                .nav-search select {
                    border: none;
                    background: #f0f8e8;
                    padding: 0 10px;
                    font-size: 13px;
                    color: var(--text-dark);
                    border-right: 1px solid var(--border);
                    cursor: pointer;
                    outline: none;
                }

                .nav-search input {
                    flex: 1;
                    border: none;
                    padding: 8px 12px;
                    font-size: 14px;
                    outline: none;
                }

                .nav-search button {
                    background: var(--green-main);
                    border: none;
                    color: #fff;
                    padding: 0 16px;
                    cursor: pointer;
                    font-size: 16px;
                }

                .nav-search button:hover {
                    background: var(--green-dark);
                }

                /* Nav actions */
                .nav-actions {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    flex-shrink: 0;
                }

                .btn-membership {
                    background: var(--green-dark);
                    color: #fff !important;
                    border: none;
                    padding: 8px 14px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 700;
                    text-decoration: none;
                    white-space: nowrap;
                }

                .btn-membership:hover {
                    background: #2a5010;
                }

                .btn-membership .highlight {
                    color: #b8e05a;
                }

                .btn-nav-link {
                    background: none;
                    border: none;
                    color: var(--text-dark);
                    font-size: 13px;
                    padding: 6px 10px;
                    cursor: pointer;
                    text-decoration: none;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .btn-nav-link:hover {
                    color: var(--green-main);
                }

                .btn-signup,
                .btn-login {
                    font-size: 13px;
                    font-weight: 600;
                    padding: 7px 14px;
                    border-radius: 4px;
                    text-decoration: none;
                    border: none;
                    cursor: pointer;
                }

                .btn-signup {
                    background: none;
                    color: var(--text-dark);
                }

                .btn-signup:hover {
                    color: var(--green-main);
                }

                .btn-login {
                    background: none;
                    color: var(--text-dark);
                }

                .btn-login:hover {
                    color: var(--green-main);
                }

                /* Cart */
                .cart-btn {
                    position: relative;
                    background: none;
                    border: none;
                    color: var(--text-dark);
                    font-size: 13px;
                    padding: 6px 10px;
                    cursor: pointer;
                    text-decoration: none;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .cart-btn:hover {
                    color: var(--green-main);
                }

                .cart-count {
                    position: absolute;
                    top: -2px;
                    right: 2px;
                    background: #e03030;
                    color: #fff;
                    font-size: 10px;
                    font-weight: 700;
                    width: 16px;
                    height: 16px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                /* User dropdown */
                .user-dropdown .dropdown-toggle {
                    background: var(--green-main);
                    color: #fff;
                    border: none;
                    padding: 7px 14px;
                    border-radius: 4px;
                    font-size: 13px;
                    font-weight: 600;
                }

                /* Delivery bar */
                .delivery-bar {
                    background: var(--white);
                    border-bottom: 1px solid var(--border);
                    padding: 6px 0;
                    font-size: 13px;
                    position: fixed;
                    top: 62px;
                    left: 0;
                    right: 0;
                    z-index: 999;
                }

                .delivery-bar-inner {
                    max-width: 1300px;
                    margin: 0 auto;
                    padding: 0 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .delivery-location {
                    color: var(--green-main);
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    cursor: pointer;
                }

                .delivery-links a {
                    color: var(--text-muted);
                    text-decoration: none;
                    margin-left: 20px;
                    font-size: 12px;
                }

                .delivery-links a:hover {
                    color: var(--green-main);
                }

                /* ── SIDEBAR CATEGORIES ── */
                .category-sidebar {
                    background: var(--white);
                    border-right: 1px solid var(--border);
                    width: 220px;
                    flex-shrink: 0;
                    position: sticky;
                    top: 110px;
                    height: calc(100vh - 110px);
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: var(--border) transparent;
                }

                .category-sidebar::-webkit-scrollbar {
                    width: 4px;
                }

                .category-sidebar::-webkit-scrollbar-track {
                    background: transparent;
                }

                .category-sidebar::-webkit-scrollbar-thumb {
                    background: var(--border);
                    border-radius: 4px;
                }

                .cat-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px 16px;
                    font-size: 13px;
                    color: var(--text-dark);
                    cursor: pointer;
                    border-bottom: 1px solid #f0f0f0;
                    text-decoration: none;
                }

                .cat-item:hover {
                    background: var(--green-bg);
                    color: var(--green-main);
                }

                .cat-item .arrow {
                    color: #aaa;
                    font-size: 11px;
                }

                /* ── PRODUCT CARDS ── */
                .product-card {
                    background: var(--white);
                    border: 1px solid var(--border);
                    border-radius: 8px;
                    overflow: hidden;
                    transition: box-shadow .2s, transform .2s;
                }

                .product-card:hover {
                    box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
                    transform: translateY(-2px);
                }

                .product-card img {
                    width: 100%;
                    height: 180px;
                    object-fit: contain;
                    padding: 12px;
                    background: #fafafa;
                }

                .product-card .card-body {
                    padding: 12px;
                }

                .product-card .prod-name {
                    font-size: 13px;
                    font-weight: 600;
                    color: var(--text-dark);
                    min-height: 40px;
                    margin-bottom: 4px;
                }

                .product-card .prod-sub {
                    font-size: 11px;
                    color: var(--text-muted);
                    margin-bottom: 8px;
                }

                .product-card .prod-price {
                    font-size: 16px;
                    font-weight: 700;
                    color: var(--text-dark);
                }

                .product-card .prod-old-price {
                    font-size: 12px;
                    color: #aaa;
                    text-decoration: line-through;
                    margin-left: 6px;
                }

                .badge-discount {
                    background: #e03030;
                    color: #fff;
                    font-size: 11px;
                    font-weight: 700;
                    padding: 2px 7px;
                    border-radius: 4px;
                    margin-bottom: 6px;
                    display: inline-block;
                }

                .btn-add-cart {
                    width: 100%;
                    background: var(--green-main);
                    color: #fff;
                    border: none;
                    padding: 9px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 700;
                    cursor: pointer;
                    letter-spacing: .5px;
                    transition: background .2s;
                }

                .btn-add-cart:hover {
                    background: var(--green-dark);
                }

                /* ── MODALS ── */
                .landers-modal .modal-content {
                    border-radius: 12px;
                    border: none;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, .18);
                }

                .landers-modal .modal-tabs {
                    display: flex;
                    gap: 0;
                    margin-bottom: 20px;
                }

                .modal-tab-btn {
                    background: none;
                    border: none;
                    padding: 8px 20px 10px;
                    font-size: 16px;
                    font-weight: 600;
                    color: #aaa;
                    cursor: pointer;
                    border-bottom: 3px solid transparent;
                }

                .modal-tab-btn.active {
                    color: var(--green-main);
                    border-bottom-color: var(--green-main);
                }

                .landers-input {
                    width: 100%;
                    padding: 11px 14px;
                    border: 1.5px solid #ddd;
                    border-radius: 8px;
                    font-size: 14px;
                    margin-bottom: 12px;
                    outline: none;
                    transition: border-color .2s;
                }

                .landers-input:focus {
                    border-color: var(--green-main);
                }

                .btn-landers-green {
                    width: 100%;
                    background: var(--green-main);
                    color: #fff;
                    border: none;
                    padding: 13px;
                    border-radius: 8px;
                    font-size: 15px;
                    font-weight: 700;
                    cursor: pointer;
                    letter-spacing: .5px;
                    transition: background .2s;
                }

                .btn-landers-green:hover {
                    background: var(--green-dark);
                }

                .forgot-link {
                    color: var(--green-main);
                    font-size: 13px;
                    font-weight: 600;
                    text-decoration: none;
                    display: block;
                    text-align: center;
                    margin: 8px 0;
                }

                .social-btn {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    border: 1.5px solid #ddd;
                    border-radius: 8px;
                    padding: 10px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    background: #fff;
                    text-decoration: none;
                    color: var(--text-dark);
                    transition: border-color .2s;
                }

                .social-btn:hover {
                    border-color: #999;
                    color: var(--text-dark);
                }

                .pw-rules {
                    font-size: 12px;
                    color: #aaa;
                    list-style: none;
                    padding: 0;
                    margin: 0 0 12px 0;
                }

                .pw-rules li {
                    padding: 2px 0;
                }

                .pw-rules li.ok {
                    color: var(--green-main);
                }

                .membership-toggle {
                    display: flex;
                    gap: 8px;
                    margin-bottom: 14px;
                }

                .toggle-yes,
                .toggle-no {
                    padding: 7px 20px;
                    border-radius: 6px;
                    border: 1.5px solid #ddd;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    background: #fff;
                }

                .toggle-yes.active {
                    background: var(--green-main);
                    color: #fff;
                    border-color: var(--green-main);
                }

                .toggle-no.active {
                    background: #fff;
                    color: var(--text-dark);
                    border-color: #ddd;
                }

                /* ── TOASTS ── */
                .toast-container {
                    z-index: 9999;
                }

                /* ── FOOTER ── */
                .site-footer {
                    background: var(--green-dark);
                    color: #c8e6a0;
                    padding: 40px 0 20px;
                    margin-top: 60px;
                }

                .site-footer h6 {
                    color: #fff;
                    font-weight: 700;
                    margin-bottom: 12px;
                }

                .site-footer a {
                    color: #a0cc70;
                    font-size: 13px;
                    text-decoration: none;
                    display: block;
                    margin-bottom: 6px;
                }

                .site-footer a:hover {
                    color: #fff;
                }

                .footer-bottom {
                    border-top: 1px solid #4a7a25;
                    margin-top: 30px;
                    padding-top: 16px;
                    font-size: 12px;
                    color: #89aa60;
                }
            </style>
        </head>

        <body>

            <!-- ══ MAIN NAVBAR ══ -->
            <nav class="main-nav">
                <div class="nav-inner">

                    <!-- Logo -->
                    <a href="/landersonline/index.php" class="nav-logo">
                        <div class="logo-circle">L</div>
                        <span>LANDERS</span>
                    </a>

                    <!-- Search -->
                    <div class="nav-search">
                        <select>
                            <option>All</option>
                            <option>Food</option>
                            <option>Beverages</option>
                            <option>Health</option>
                            <option>Electronics</option>
                        </select>
                        <input type="text" placeholder="Search products...">
                        <button><i class="bi bi-search"></i></button>
                    </div>

                    <!-- Actions -->
                    <div class="nav-actions">
                        <a href="#" class="btn-membership">
                            APPLY <span class="highlight">MEMBERSHIP</span>
                        </a>
                        <a href="#" class="btn-nav-link">Help</a>

                        <!-- Cart -->
                        <a href="/LandersOnline/customer/cart.php" class="cart-btn">
                            <i class="bi bi-cart2" style="font-size:18px;"></i>
                            Cart
                            <?php
                            $cartCount = $_SESSION['cart_count'] ?? 0;
                            if ($cartCount > 0):
                                ?>
                                <span class="cart-count"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>

                        <?php if (isset($_SESSION['account_id'])): ?>
                            <!-- Logged in -->
                            <div class="dropdown">
                                <button class="btn dropdown-toggle user-dropdown" data-bs-toggle="dropdown"
                                    style="background:var(--green-main);color:#fff;border:none;padding:7px 14px;border-radius:4px;font-size:13px;font-weight:600;">
                                    <?= htmlspecialchars($_SESSION['display_name'] ?? 'Account') ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <li><a class="dropdown-item" href="/LandersOnline/admin/dashboard.php">
                                                <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                            </a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="/LandersOnline/customer/dashboard.php">
                                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                            </a></li>
                                    <?php endif; ?>
                                    </li>
                                    <li><a class="dropdown-item" href="/LandersOnline/customer/my_orders.php">
                                            <i class="bi bi-bag me-2"></i>My Orders
                                        </a></li>
                                    <li><a class="dropdown-item" href="/LandersOnline/auth/change_password.php">
                                            <i class="bi bi-key me-2"></i>Change Password
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="/LandersOnline/auth/logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </a></li>
                                </ul>
                            </div>

                        <?php else: ?>
                            <!-- Guest -->
                            <button class="btn-signup" data-bs-toggle="modal" data-bs-target="#authModal"
                                onclick="switchTab('signup')">
                                Sign Up
                            </button>
                            <button class="btn-login" data-bs-toggle="modal" data-bs-target="#authModal"
                                onclick="switchTab('login')">
                                Login
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

            <!-- ══ DELIVERY BAR ══ -->
            <div class="delivery-bar">
                <div class="delivery-bar-inner">
                    <div class="delivery-location">
                        <i class="bi bi-geo-alt-fill" style="color:var(--green-main);"></i>
                        Enter Your Delivery Location
                        <i class="bi bi-info-circle" style="font-size:12px;color:#aaa;"></i>
                    </div>
                    <div class="delivery-links">
                        <a href="#">Download the App</a>
                        <a href="#">About Landers</a>
                        <a href="#">The Landers Experience</a>
                        <a href="#">Delivery Areas</a>
                        <a href="#"
                            style="border:1.5px solid var(--green-main);border-radius:20px;padding:3px 12px;color:var(--green-main);">
                            <i class="bi bi-truck"></i> Track Orders
                        </a>
                    </div>
                </div>
            </div>

            <!-- ══ TOAST SUCCESS ══ -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="successToast" class="toast text-white bg-success border-0 show">
                        <div class="toast-body"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    </div>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

            <!-- ══ TOAST ERROR ══ -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="errorToast" class="toast text-white bg-danger border-0 show">
                        <div class="toast-body"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    </div>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

            <!-- ══════════════════════════════ -->
            <!--   AUTH MODAL (Sign Up / Login) -->
            <!-- ══════════════════════════════ -->
            <div class="modal fade landers-modal" id="authModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
                    <div class="modal-content p-4">

                        <button type="button" class="btn-close position-absolute" style="top:16px;right:16px;"
                            data-bs-dismiss="modal"></button>

                        <!-- Tabs -->
                        <div class="modal-tabs">
                            <button class="modal-tab-btn active" id="tabSignup" onclick="switchTab('signup')">Sign Up</button>
                            <button class="modal-tab-btn" id="tabLogin" onclick="switchTab('login')">Login</button>
                        </div>

                        <!-- ── SIGN UP PANEL ── -->
                        <div id="panelSignup">
                            <form method="POST" action="/LandersOnline/auth/register.php">
                                <input type="hidden" name="action" value="register">
                                <input type="text" name="email" class="landers-input" placeholder="Email Address" required>
                                <input type="text" name="fname" class="landers-input" placeholder="First Name" required>
                                 <input type="text" name="lname" class="landers-input" placeholder="Last Name" required>
                                <input type="text" name="phone" class="landers-input" placeholder="Phone Number" required>

                                <div style="position:relative;">
                                    <input type="password" name="password" id="regPw" class="landers-input"
                                        placeholder="Enter Password" required>
                                    <i class="bi bi-eye-slash" onclick="togglePw('regPw',this)"
                                        style="position:absolute;right:14px;top:12px;cursor:pointer;color:#aaa;"></i>
                                </div>

                                <div style="position:relative;">
                                    <input type="password" name="confirm_password" id="regPw2" class="landers-input"
                                        placeholder="Confirm Password" required>
                                    <i class="bi bi-eye-slash" onclick="togglePw('regPw2',this)"
                                        style="position:absolute;right:14px;top:12px;cursor:pointer;color:#aaa;"></i>
                                </div>

                                <!-- Password rules -->
                                <ul class="pw-rules" id="pwRules">
                                    <li id="rule-upper"><i class="bi bi-circle"></i> Min. One uppercase</li>
                                    <li id="rule-lower"><i class="bi bi-circle"></i> Min. One lowercase</li>
                                    <li id="rule-num"><i class="bi bi-circle"></i> At least One number</li>
                                    <li id="rule-len"><i class="bi bi-circle"></i> Eight or more characters</li>
                                    <li id="rule-match"><i class="bi bi-circle"></i> Passwords match</li>
                                </ul>


                                <button type="submit" class="btn-landers-green mb-3">SIGN UP</button>

                                <p style="font-size:11px;color:#888;text-align:center;">
                                    By clicking "SIGN UP", I confirm that I have read and agree to Landers'
                                    <a href="#" style="color:var(--green-main);">Terms of Use</a> and
                                    <a href="#" style="color:var(--green-main);">Privacy Policy</a>.
                                </p>

                                <div style="display:flex;align-items:center;gap:10px;margin:12px 0;">
                                    <hr style="flex:1;"> <span style="font-size:13px;color:#aaa;">Or, sign up with</span>
                                    <hr style="flex:1;">
                                </div>
                                <div style="display:flex;gap:10px;">
                                    <a href="#" class="social-btn" style="flex:1;">
                                        <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg" width="18"
                                            style="filter:invert(28%) sepia(80%) saturate(900%) hue-rotate(200deg);"> Facebook
                                    </a>
                                    <a href="#" class="social-btn" style="flex:1;">
                                        <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/google.svg" width="18">
                                        Google
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- ── LOGIN PANEL ── -->
                        <div id="panelLogin" style="display:none;">
                            <form method="POST" action="/LandersOnline/auth/login.php">
                                <input type="hidden" name="action" value="login">
                                <input type="email" name="email" class="landers-input" placeholder="Email Address" required>

                                <div style="position:relative;">
                                    <input type="password" name="password" id="loginPw" class="landers-input"
                                        placeholder="Enter Password" required>
                                    <i class="bi bi-eye-slash" onclick="togglePw('loginPw',this)"
                                        style="position:absolute;right:14px;top:12px;cursor:pointer;color:#aaa;"></i>
                                </div>

                                <button type="submit" name="login" class="btn-landers-green mb-2">LOG IN</button>
                                <a href="#" class="forgot-link">FORGOT PASSWORD?</a>

                                <div style="display:flex;align-items:center;gap:10px;margin:12px 0;">
                                    <hr style="flex:1;"> <span style="font-size:13px;color:#aaa;">Or, login with</span>
                                    <hr style="flex:1;">
                                </div>
                                <div style="display:flex;gap:10px;">
                                    <a href="#" class="social-btn" style="flex:1;">
                                        <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/facebook.svg" width="18"
                                            style="filter:invert(28%) sepia(80%) saturate(900%) hue-rotate(200deg);"> Facebook
                                    </a>
                                    <a href="#" class="social-btn" style="flex:1;">
                                        <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/google.svg" width="18">
                                        Google
                                    </a>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function switchTab(tab) {
                    document.getElementById('panelSignup').style.display = tab === 'signup' ? '' : 'none';
                    document.getElementById('panelLogin').style.display = tab === 'login' ? '' : 'none';
                    document.getElementById('tabSignup').classList.toggle('active', tab === 'signup');
                    document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
                }

                function togglePw(id, icon) {
                    const inp = document.getElementById(id);
                    if (inp.type === 'password') {
                        inp.type = 'text';
                        icon.classList.replace('bi-eye-slash', 'bi-eye');
                    } else {
                        inp.type = 'password';
                        icon.classList.replace('bi-eye', 'bi-eye-slash');
                    }
                }

                function toggleMembership(show) {
                    document.getElementById('membershipFields').style.display = show ? '' : 'none';
                    document.querySelector('.toggle-yes').classList.toggle('active', show);
                    document.querySelector('.toggle-no').classList.toggle('active', !show);
                }

                // Password validation rules
                const regPw = document.getElementById('regPw');
                const regPw2 = document.getElementById('regPw2');
                if (regPw) {
                    const check = (id, ok) => {
                        const el = document.getElementById(id);
                        el.classList.toggle('ok', ok);
                        el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                    };
                    regPw.addEventListener('input', function () {
                        const v = this.value;
                        check('rule-upper', /[A-Z]/.test(v));
                        check('rule-lower', /[a-z]/.test(v));
                        check('rule-num', /[0-9]/.test(v));
                        check('rule-len', v.length >= 8);
                        check('rule-match', v && v === regPw2.value);                   
                    });
                    regPw2.addEventListener('input', function () {
                        check('rule-match', this.value && this.value === regPw.value);
                    }); 
                }

                // Auto-open modal if session says to
                <?php if (isset($_SESSION['open_modal'])): ?>
                    document.addEventListener('DOMContentLoaded', function () {
                        const m = new bootstrap.Modal(document.getElementById('authModal'));
                        switchTab('<?= $_SESSION['open_modal'] ?>');
                        m.show();
                    });
                    <?php unset($_SESSION['open_modal']); endif; ?>

                // Auto-show toasts
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.toast.show').forEach(function (el) {
                        new bootstrap.Toast(el, { delay: 3000 }).show();
                    });
                });
            </script>