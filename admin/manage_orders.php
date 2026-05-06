<?php
ob_start();
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /LandersOnline/index.php');
    exit();
}

if (isset($_POST['update_status'])) {
    $oid = (int) $_POST['ord_id'];
    $status = $_POST['status'];
    $s = $conn->prepare("UPDATE Orders SET Ord_Status=? WHERE Ord_Id=?");
    $s->bind_param("si", $status, $oid);
    $s->execute();
    $_SESSION['success'] = "Order #" . str_pad($oid, 5, '0', STR_PAD_LEFT) . " updated to " . ucfirst($status) . ".";
    header('Location: manage_orders.php');
    exit();
}

$filter = $_GET['status'] ?? '';
if ($filter) {
    $stmt = $conn->prepare("
        SELECT o.*, cu.Cust_Name, cu.Cust_Phone, COUNT(oi.OrdItem_Id) AS items
        FROM Orders o
        JOIN Customers cu ON o.Ord_CustId=cu.Cust_Id
        LEFT JOIN OrderItems oi ON oi.OrdItem_OrdId=o.Ord_Id
        WHERE o.Ord_Status=?
        GROUP BY o.Ord_Id ORDER BY o.Ord_Id DESC
    ");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query("
        SELECT o.*, cu.Cust_Name, cu.Cust_Phone, COUNT(oi.OrdItem_Id) AS items
        FROM Orders o
        JOIN Customers cu ON o.Ord_CustId=cu.Cust_Id
        LEFT JOIN OrderItems oi ON oi.OrdItem_OrdId=o.Ord_Id
        GROUP BY o.Ord_Id ORDER BY o.Ord_Id DESC
    ");
}

$title = 'Manage Orders';
$currentPage = 'manage_orders.php';
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

        .card-w {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 10px;
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

        .filter-tab {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid var(--bd);
            color: var(--tm);
            background: #fff;
            transition: all .15s;
        }

        .filter-tab.on {
            background: var(--gm);
            color: #fff;
            border-color: var(--gm);
        }

        .landers-input {
            padding: 6px 10px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            outline: none;
        }

        .landers-input:focus {
            border-color: var(--gm);
        }

        .toast-container {
            z-index: 9999;
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

    <!-- SIDEBAR -->
    <aside class="adm-side">
        <div class="adm-logo">
            <div class="circle">L</div>
            <div><span>LANDERS</span><small>ADMIN PANEL</small></div>
        </div>
        <div class="adm-section">Main Menu</div>
        <?php foreach ([
            ['dashboard.php', 'bi-speedometer2', 'Dashboard'],
            ['manage_products.php', 'bi-box-seam', 'Products'],
            ['manage_categories.php', 'bi-tags', 'Categories'],
            ['manage_orders.php', 'bi-bag-check', 'Orders'],
            ['manage_customers.php', 'bi-people', 'Customers'],
        ] as [$h, $i, $l]): ?>
            <a href="/LandersOnline/admin/<?= $h ?>" class="adm-link <?= $currentPage === $h ? 'on' : '' ?>">
                <i class="bi <?= $i ?>"></i> <?= $l ?>
            </a>
        <?php endforeach; ?>
        <div class="adm-footer">
            <a href="/LandersOnline/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h5><i class="bi bi-bag-check me-2"></i>Manage Orders</h5>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast text-white bg-success border-0 show">
                    <div class="toast-body"><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

        <div class="adm-content">

            <!-- Filter tabs -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <?php foreach ([
                    '' => 'All Orders',
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled'
                ] as $val => $lbl): ?>
                    <a href="?status=<?= $val ?>" class="filter-tab <?= $filter === $val ? 'on' : '' ?>">
                        <?= $lbl ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="card-w" style="overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Delivery</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Update</th>
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
                            if ($orders && $orders->num_rows > 0):
                                while ($o = $orders->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td style="font-weight:700;">#<?= str_pad($o['Ord_Id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <div style="font-weight:600;"><?= htmlspecialchars($o['Cust_Name']) ?></div>
                                            <div style="font-size:11px;color:#aaa;">
                                                <?= htmlspecialchars($o['Cust_Phone'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td><?= $o['items'] ?> item(s)</td>
                                        <td style="font-weight:700;">₱<?= number_format($o['Ord_Total'], 2) ?></td>
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
                                        <td>
                                            <form method="POST" style="display:flex;gap:5px;align-items:center;">
                                                <input type="hidden" name="ord_id" value="<?= $o['Ord_Id'] ?>">
                                                <select name="status" class="landers-input">
                                                    <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $st): ?>
                                                        <option value="<?= $st ?>" <?= $o['Ord_Status'] === $st ? 'selected' : '' ?>>
                                                            <?= ucfirst($st) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_status"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Update
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.toast.show').forEach(el => new bootstrap.Toast(el, { delay: 3000 }).show());
        });
    </script>
</body>

</html>