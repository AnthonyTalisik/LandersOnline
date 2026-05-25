<?php
ob_start();
session_start();
require_once '../config/db.php';
require_once '../config/firebase_store.php';
$store = firebaseStore();

if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /LandersOnline/index.php');
    exit();
}

// ── STATS ──
$activeProducts = $store->productsWithCategory(0, true)->fetch_all();
$allOrders = $store->orders()->fetch_all();
$totalProds = count($activeProducts);
$outOfStock = count(array_filter($activeProducts, fn($p) => (int)($p['Prod_Stock'] ?? 0) === 0));
$lowStock = count(array_filter($activeProducts, fn($p) => (int)($p['Prod_Stock'] ?? 0) > 0 && (int)($p['Prod_Stock'] ?? 0) <= 5));
$totalCats = $store->categories(true)->num_rows;
$totalOrders = count($allOrders);
$pendOrders = count(array_filter($allOrders, fn($o) => ($o['Ord_Status'] ?? '') === 'pending'));
$totalCusts = $store->customers()->num_rows;
$revenue = array_sum(array_map(fn($o) => ($o['Ord_Status'] ?? '') === 'delivered' ? (float)($o['Ord_Total'] ?? 0) : 0, $allOrders));
$recentOrders = $store->result(array_slice($allOrders, 0, 8));

$title = 'Admin Dashboard';
$currentPage = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $title ?> – LandersOnline Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --gd: #3a6b1a;
            --gm: #4c8c23;
            --gl: #6aaf35;
            --gbg: #f4fbed;
            --bd: #d4e8bb;
            --tx: #1a2e0a;
            --tm: #5a6a4a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f3f6f0;
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .adm-side {
            width: 220px;
            flex-shrink: 0;
            background: var(--gd);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: sticky;
            top: 0;
        }

        .adm-logo {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }

        .adm-logo .circle {
            width: 36px;
            height: 36px;
            background: var(--gl);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 18px;
            color: #fff;
        }

        .adm-logo span {
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .adm-logo small {
            color: #a0cc70;
            font-size: 10px;
            display: block;
            letter-spacing: .5px;
        }

        .adm-section {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, .45);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 20px 20px 8px;
        }

        .adm-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            font-size: 13.5px;
            font-weight: 500;
            color: rgba(255, 255, 255, .75);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .15s;
        }

        .adm-link:hover,
        .adm-link.on {
            background: rgba(255, 255, 255, .08);
            color: #fff;
            border-left-color: var(--gl);
        }

        .adm-link i {
            font-size: 15px;
        }

        .adm-footer {
            margin-top: auto;
            padding: 14px 20px;
            border-top: 1px solid rgba(255, 255, 255, .12);
        }

        .adm-footer a {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, .6);
            text-decoration: none;
        }

        .adm-footer a:hover {
            color: #faa;
        }

        /* ── MAIN ── */
        .adm-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: auto;
        }

        .adm-topbar {
            background: #fff;
            border-bottom: 1px solid var(--bd);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .adm-topbar h5 {
            font-weight: 700;
            color: var(--gd);
            margin: 0;
            font-size: 16px;
        }

        .adm-content {
            padding: 28px;
            flex: 1;
        }

        /* ── STAT CARDS ── */
        .stat-card {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 10px;
            padding: 18px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-val {
            font-size: 22px;
            font-weight: 800;
            color: var(--tx);
            line-height: 1;
        }

        .stat-lbl {
            font-size: 12px;
            color: var(--tm);
            margin-top: 3px;
        }

        /* ── TABLE CARD ── */
        .card-w {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 10px;
            padding: 20px;
        }

        .card-w table th {
            background: var(--gbg);
            font-size: 12px;
            font-weight: 700;
            color: var(--tm);
            padding: 10px 14px;
        }

        .card-w table td {
            font-size: 13px;
            padding: 11px 14px;
            vertical-align: middle;
        }

        /* ── TOAST ── */
        .toast-container {
            z-index: 9999;
        }

        /* ── QUICK ACTION BTNS ── */
        .qbtn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity .15s;
        }

        .qbtn:hover {
            opacity: .88;
        }

        .qbtn-green {
            background: var(--gm);
            color: #fff;
        }

        .qbtn-outline {
            background: #fff;
            color: var(--gd);
            border: 1.5px solid var(--bd);
        }

        /* ── SIDEBAR ── */
        .adm-side {
            width: 230px;
            flex-shrink: 0;
            background: #2d5516;
            /* slightly richer dark green */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: sticky;
            top: 0;
        }

        .adm-logo {
            padding: 20px 18px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .10);
        }

        .adm-logo .circle {
            width: 38px;
            height: 38px;
            background: #6aaf35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 17px;
            color: #fff;
            flex-shrink: 0;
            /* prevents circle from squishing */
        }

        .adm-logo span {
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            display: block;
            line-height: 1.2;
        }

        .adm-logo small {
            color: #8fca55;
            font-size: 10px;
            display: block;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .adm-section {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, .40);
            letter-spacing: 1.8px;
            text-transform: uppercase;
            padding: 18px 18px 6px;
        }

        .adm-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 18px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, .72);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .15s;
        }

        .adm-link:hover,
        .adm-link.on {
            background: rgba(106, 175, 53, .18);
            color: #fff;
            border-left-color: #6aaf35;
        }

        .adm-link i {
            font-size: 15px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            /* keeps icons from shifting */
        }

        .adm-footer {
            margin-top: auto;
            padding: 14px 18px;
            border-top: 1px solid rgba(255, 255, 255, .10);
        }

        .adm-footer a {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, .55);
            text-decoration: none;
        }

        .adm-footer a:hover {
            color: #f87171;
        }
    </style>
</head>

<body>

    <!-- ══ SIDEBAR ══ -->
    <aside class="adm-side">
        <div class="adm-logo">
            <div class="circle">L</div>
            <div>
                <span>LANDERS</span>
                <small>ADMIN PANEL</small>
            </div>
        </div>

        <div class="adm-section">Main Menu</div>

        <?php
        $nav = [
            ['dashboard.php', 'bi-speedometer2', 'Dashboard'],
            ['manage_products.php', 'bi-box-seam', 'Products'],
            ['manage_categories.php', 'bi-tags', 'Categories'],
            ['manage_orders.php', 'bi-bag-check', 'Orders'],
            ['manage_customers.php', 'bi-people', 'Customers'],
        ];
        foreach ($nav as [$h, $i, $l]):
            ?>
            <a href="/LandersOnline/admin/<?= $h ?>" class="adm-link <?= $currentPage === $h ? 'on' : '' ?>">
                <i class="bi <?= $i ?>"></i> <?= $l ?>
            </a>
        <?php endforeach; ?>

        <div class="adm-footer">
            <a href="/LandersOnline/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <!-- ══ MAIN ══ -->
    <div class="adm-main">

        <!-- Topbar -->
        <div class="adm-topbar">
            <h5><i class="bi bi-speedometer2 me-2"></i>Dashboard</h5>
            <span style="font-size:13px;color:var(--tm);">
                Welcome back, <strong><?= htmlspecialchars($_SESSION['display_name'] ?? 'Admin') ?></strong>
            </span>
        </div>

        <!-- Toast -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast text-white bg-success border-0 show" id="toast1">
                    <div class="toast-body"><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

        <div class="adm-content">

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <?php
                $stats = [
                    ['bi-box-seam', '#e8f5e9', 'var(--gm)', 'Active Products', $totalProds],
                    ['bi-tags', '#e8f5e9', 'var(--gd)', 'Categories', $totalCats],
                    ['bi-exclamation-triangle', '#fff8e1', '#f5a623', 'Low Stock (≤ 5)', $lowStock],
                    ['bi-x-circle', '#fdecea', '#d93025', 'Out of Stock', $outOfStock],
                    ['bi-bag-check', '#e3f2fd', '#185fa5', 'Total Orders', $totalOrders],
                    ['bi-clock-history', '#fff8e1', '#f5a623', 'Pending Orders', $pendOrders],
                    ['bi-people', '#f3e5f5', '#6a35af', 'Customers', $totalCusts],
                    ['bi-cash-coin', '#e8f5e9', 'var(--gm)', 'Revenue (Delivered)', '₱' . number_format($revenue, 2)],
                ];
                foreach ($stats as [$ico, $bg, $col, $lbl, $val]):
                    ?>
                    <div class="col-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $col ?>;">
                                <i class="bi <?= $ico ?>"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $val ?></div>
                                <div class="stat-lbl"><?= $lbl ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick actions -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:26px;">
                <a href="/LandersOnline/admin/manage_products.php" class="qbtn qbtn-green">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>
                <a href="/LandersOnline/admin/manage_categories.php" class="qbtn qbtn-outline">
                    <i class="bi bi-tags"></i> Categories
                </a>
                <a href="/LandersOnline/admin/manage_orders.php" class="qbtn qbtn-outline">
                    <i class="bi bi-bag-check"></i> View Orders
                </a>
            </div>

            <!-- Recent orders table -->
            <div class="card-w">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h6 style="font-weight:700;color:var(--tx);margin:0;">Recent Orders</h6>
                    <a href="/LandersOnline/admin/manage_orders.php"
                        style="font-size:13px;color:var(--gm);text-decoration:none;font-weight:600;">
                        View All →
                    </a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Delivery</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cls = [
                                'pending' => 'bg-warning text-dark',
                                'processing' => 'bg-info text-dark',
                                'shipped' => 'bg-primary',
                                'delivered' => 'bg-success',
                                'cancelled' => 'bg-danger',
                            ];
                            if ($recentOrders && $recentOrders->num_rows > 0):
                                while ($o = $recentOrders->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td style="font-weight:700;">#<?= str_pad($o['Ord_Id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($o['Cust_Name']) ?></td>
                                        <td>₱<?= number_format($o['Ord_Total'], 2) ?></td>
                                        <td>
                                            <?= $o['Ord_DelivFee'] == 0
                                                ? '<span style="color:var(--gm);font-weight:600;">FREE</span>'
                                                : '₱' . number_format($o['Ord_DelivFee'], 2) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $cls[$o['Ord_Status']] ?? 'bg-secondary' ?>">
                                                <?= ucfirst($o['Ord_Status']) ?>
                                            </span>
                                        </td>
                                        <td style="color:#aaa;"><?= date('M j, Y', strtotime($o['Ord_CreatedAt'])) ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No orders yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /adm-content -->
    </div><!-- /adm-main -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.toast.show').forEach(function (el) {
                new bootstrap.Toast(el, { delay: 3000 }).show();
            });
        });
    </script>
</body>

</html>
