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

// ── ADD ──
if (isset($_POST['add_category'])) {
    $name = trim($_POST['cat_name']);

    if ($store->categoryExistsByName($name)) {
        $_SESSION['error'] = "Category '$name' already exists.";
        header('Location: manage_categories.php');
        exit();
    }

    $store->addCategory($name);
    $_SESSION['success'] = "Category added!";
    header('Location: manage_categories.php');
    exit();
}

// ── UPDATE ──
if (isset($_POST['update_category'])) {
    $id = (int) $_POST['cat_id'];
    $name = trim($_POST['cat_name']);
    $store->updateCategory($id, $name);
    $_SESSION['success'] = "Category updated!";
    header('Location: manage_categories.php');
    exit();
}

// ── TOGGLE STATUS ──
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $store->toggleCategory($id);
    $_SESSION['success'] = "Category status updated.";
    header('Location: manage_categories.php');
    exit();
}

$categories = $store->categoriesWithProductCount();

$title = 'Manage Categories';
$currentPage = 'manage_categories.php';
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

        .landers-input {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid #ddd;
            border-radius: 7px;
            font-size: 13px;
            margin-bottom: 10px;
            outline: none;
            transition: border-color .2s;
        }

        .landers-input:focus {
            border-color: var(--gm);
        }

        .btn-grn {
            background: var(--gm);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-grn:hover {
            background: var(--gd);
        }

        .toast-container {
            z-index: 9999;
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
            <h5><i class="bi bi-tags me-2"></i>Manage Categories</h5>
            <button class="btn-grn" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast text-white bg-success border-0 show">
                    <div class="toast-body"><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast text-white bg-danger border-0 show">
                    <div class="toast-body"><?= htmlspecialchars($_SESSION['error']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

        <div class="adm-content">
            <div class="card-w" style="padding:0;overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Active Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            while ($c = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#aaa;"><?= $i++ ?></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($c['Cat_Name']) ?></td>
                                    <td><?= $c['prod_count'] ?></td>
                                    <td>
                                        <span
                                            class="badge <?= $c['Cat_Status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($c['Cat_Status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $c['Cat_Id'] ?>">
                                                Edit
                                            </button>
                                            <a href="?toggle=<?= $c['Cat_Id'] ?>"
                                                class="btn btn-sm <?= $c['Cat_Status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                onclick="return confirm('Change category status?')">
                                                <?= $c['Cat_Status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- EDIT MODAL -->
                                <div class="modal fade" id="editModal<?= $c['Cat_Id'] ?>">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4">
                                            <div class="modal-header border-0">
                                                <h5>Edit Category</h5>
                                                <button class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="cat_id" value="<?= $c['Cat_Id'] ?>">
                                                <div class="modal-body">
                                                    <label style="font-size:12px;color:var(--tm);font-weight:600;">Category
                                                        Name</label>
                                                    <input type="text" name="cat_name"
                                                        value="<?= htmlspecialchars($c['Cat_Name']) ?>"
                                                        class="landers-input" required>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="submit" name="update_category" class="btn-grn w-100">
                                                        Update Category
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

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header border-0">
                    <h5>Add New Category</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Category Name *</label>
                        <input type="text" name="cat_name" class="landers-input" required>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="add_category" class="btn-grn w-100">Add Category</button>
                    </div>
                </form>
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
