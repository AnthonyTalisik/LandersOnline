<?php
ob_start();
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /LandersOnline/index.php'); exit();
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $r  = $conn->prepare("SELECT Acct_Status FROM Accounts WHERE Acct_Id=?");
    $r->bind_param("i",$id); $r->execute();
    $cur = $r->get_result()->fetch_assoc()['Acct_Status'];
    $new = $cur==='active' ? 'inactive' : 'active';
    $u   = $conn->prepare("UPDATE Accounts SET Acct_Status=? WHERE Acct_Id=?");
    $u->bind_param("si",$new,$id); $u->execute();
    $_SESSION['success'] = "Customer account ".ucfirst($new).".";
    header('Location: manage_customers.php'); exit();
}

$customers = $conn->query("
    SELECT c.*, a.Acct_Email, a.Acct_Status, a.Acct_CreatedAt,
           COUNT(o.Ord_Id) AS total_orders,
           IFNULL(SUM(o.Ord_Total),0) AS total_spent
    FROM Customers c
    JOIN Accounts a ON c.Cust_AcctId=a.Acct_Id
    LEFT JOIN Orders o ON o.Ord_CustId=c.Cust_Id AND o.Ord_Status='delivered'
    GROUP BY c.Cust_Id ORDER BY a.Acct_CreatedAt DESC
");

$title = 'Manage Customers';
$currentPage = 'manage_customers.php';
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
            --gd:  #3a6b1a;
            --gm:  #4c8c23;
            --gl:  #6aaf35;
            --gbg: #f4fbed;
            --bd:  #d4e8bb;
            --tx:  #1a2e0a;
            --tm:  #5a6a4a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f3f6f0;
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .adm-side {
            width: 230px;
            flex-shrink: 0;
            background: #2d5516;
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
            border-bottom: 1px solid rgba(255,255,255,.10);
        }

        .adm-logo .circle {
            width: 38px;
            height: 38px;
            background: var(--gl);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 17px;
            color: #fff;
            flex-shrink: 0;
        }

        .adm-logo .logo-text strong {
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            display: block;
            line-height: 1.2;
        }

        .adm-logo .logo-text small {
            color: #8fca55;
            font-size: 10px;
            display: block;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .adm-section {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,.40);
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
            color: rgba(255,255,255,.72);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .15s;
        }

        .adm-link:hover,
        .adm-link.on {
            background: rgba(106,175,53,.18);
            color: #fff;
            border-left-color: var(--gl);
        }

        .adm-link i {
            font-size: 15px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .adm-divider {
            height: 1px;
            background: rgba(255,255,255,.10);
            margin: 6px 0;
        }

        .adm-footer {
            margin-top: auto;
            padding: 14px 18px;
            border-top: 1px solid rgba(255,255,255,.10);
        }

        .adm-footer a {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255,255,255,.55);
            text-decoration: none;
        }

        .adm-footer a:hover { color: #f87171; }

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
            padding: 13px 28px;
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
            font-size: 15px;
        }

        .adm-content { padding: 28px; flex: 1; }

        /* ── CARD ── */
        .card-w {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 10px;
            overflow: hidden;
        }

        .card-w table th {
            background: var(--gbg);
            font-size: 12px;
            font-weight: 700;
            color: var(--tm);
            padding: 11px 16px;
            white-space: nowrap;
        }

        .card-w table td {
            font-size: 13px;
            padding: 11px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4ea;
        }

        .card-w table tbody tr:last-child td { border-bottom: none; }
        .card-w table tbody tr:hover td { background: var(--gbg); }

        /* ── TOAST ── */
        .toast-container { z-index: 9999; }

        /* ── AVATAR INITIALS ── */
        .cust-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8f5e9;
            color: var(--gd);
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 8px;
        }
    </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="adm-side">
    <div class="adm-logo">
        <div class="circle">L</div>
        <div class="logo-text">
            <strong>LANDERS</strong>
            <small>Admin Panel</small>
        </div>
    </div>

    <div class="adm-section">Main Menu</div>

    <?php
    $nav = [
        ['dashboard.php',         'bi-speedometer2', 'Dashboard'],
        ['manage_products.php',   'bi-box-seam',     'Products'],
        ['manage_categories.php', 'bi-tags',          'Categories'],
        ['manage_orders.php',     'bi-bag-check',     'Orders'],
        ['manage_customers.php',  'bi-people',        'Customers'],
    ];
    foreach ($nav as [$h, $i, $l]):
    ?>
        <a href="/LandersOnline/admin/<?= $h ?>" class="adm-link <?= $currentPage === $h ? 'on' : '' ?>">
            <i class="bi <?= $i ?>"></i> <?= $l ?>
        </a>
    <?php endforeach; ?>

    <div class="adm-divider"></div>

    <div class="adm-footer">
        <a href="/LandersOnline/auth/logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="adm-main">

    <!-- Topbar -->
    <div class="adm-topbar">
        <h5><i class="bi bi-people me-2"></i>Customers</h5>
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

        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 style="font-weight:700;color:var(--tx);margin:0;">
                <i class="bi bi-people me-2" style="color:var(--gm);"></i>Manage Customers
            </h4>
            <span style="font-size:13px;color:var(--tm);">
                <?php
                $total = $customers ? $customers->num_rows : 0;
                // Reset pointer after num_rows check — re-query not needed, just rewind
                if ($customers) $customers->data_seek(0);
                echo $total . ' customer' . ($total !== 1 ? 's' : '') . ' found';
                ?>
            </span>
        </div>

        <div class="card-w">
            <div style="overflow-x:auto;">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($customers && $customers->num_rows > 0):
                        while ($c = $customers->fetch_assoc()):
                            $initials = strtoupper(substr($c['Cust_Name'] ?? 'U', 0, 1));
                    ?>
                        <tr>
                            <td style="font-weight:600;">
                                <div style="display:flex;align-items:center;">
                                    <span class="cust-avatar"><?= $initials ?></span>
                                    <?= htmlspecialchars($c['Cust_Name'] ?? '—') ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($c['Acct_Email']) ?></td>
                            <td><?= htmlspecialchars($c['Cust_Phone'] ?? '—') ?></td>
                            <td>
                                <span style="font-weight:600;color:var(--gd);"><?= $c['total_orders'] ?></span>
                            </td>
                            <td style="font-weight:600;">₱<?= number_format($c['total_spent'], 2) ?></td>
                            <td>
                                <span class="badge <?= $c['Acct_Status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ucfirst($c['Acct_Status']) ?>
                                </span>
                            </td>
                            <td style="color:#aaa;white-space:nowrap;">
                                <?= date('M j, Y', strtotime($c['Acct_CreatedAt'])) ?>
                            </td>
                            <td>
                                <a href="?toggle=<?= $c['Cust_AcctId'] ?>"
                                   class="btn btn-sm <?= $c['Acct_Status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                   onclick="return confirm('<?= $c['Acct_Status'] === 'active' ? 'Deactivate' : 'Activate' ?> this customer?')">
                                    <?= $c['Acct_Status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3;"></i>
                                No customers yet.
                            </td>
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