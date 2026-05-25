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


// ── ADD PRODUCT ──
if (isset($_POST['add_product'])) {

    $name = trim($_POST['prod_name']);
    $catId = (int) $_POST['cat_id'];
    $price = (float) $_POST['price'];
    $oldPrice = $_POST['old_price'] !== '' ? (float) $_POST['old_price'] : null;
    $size = trim($_POST['size'] ?? '');
    $stock = (int) $_POST['stock'];
    $imageInput = trim($_POST['image_url'] ?? '');
    $image = $imageInput !== '' ? '/LandersOnline/assets/images/' . $imageInput : '';
    $brand = trim($_POST['brand'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $store->addProduct([
        'Prod_CatId' => $catId,
        'Prod_Name' => $name,
        'Prod_Size' => $size,
        'Prod_Price' => $price,
        'Prod_OldPrice' => $oldPrice,
        'Prod_Stock' => $stock,
        'Prod_Image' => $image,
        'Prod_Brand' => $brand,
        'Prod_Desc' => $description,
    ]);
    $_SESSION['success'] = "Product added!";

    header("Location: manage_products.php");
    exit();
}

// ── UPDATE PRODUCT ──
if (isset($_POST['update_product'])) {
    $pid = (int) $_POST['prod_id'];
    $name = trim($_POST['prod_name']);
    $catId = (int) $_POST['cat_id'];
    $price = (float) $_POST['price'];
    $oldPrice = ($_POST['old_price'] !== '') ? (float) $_POST['old_price'] : null;
    $size = trim($_POST['size'] ?? '');
    $stock = (int) $_POST['stock'];
    $imageInput = trim($_POST['image_url'] ?? '');
    $image = $imageInput !== '' ? '/LandersOnline/assets/images/' . $imageInput : '';
    $brand = trim($_POST['brand'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $store->updateProduct($pid, [
        'Prod_Name' => $name,
        'Prod_CatId' => $catId,
        'Prod_Size' => $size,
        'Prod_Price' => $price,
        'Prod_OldPrice' => $oldPrice,
        'Prod_Stock' => $stock,
        'Prod_Image' => $image,
        'Prod_Brand' => $brand,
        'Prod_Desc' => $description,
    ]);
    $_SESSION['success'] = "Product updated!";
    header("Location: manage_products.php");
    exit();
}

// ── TOGGLE STATUS ──
if (isset($_GET['toggle'])) {
    $pid = (int) $_GET['toggle'];
    $store->toggleProduct($pid);
    $_SESSION['success'] = "Product status updated.";
    header("Location: manage_products.php");
    exit();
}

// ── DELETE PRODUCT ──
if (isset($_GET['delete'])) {
    $pid = (int) $_GET['delete'];
    $store->deleteProduct($pid);
    $_SESSION['success'] = "Product deleted.";
    header("Location: manage_products.php");
    exit();
}

$categories = $store->categories(true);
$products = $store->productsWithCategory();
$title = 'Manage Products';
$currentPage = 'manage_products.php';
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

    <!-- MAIN -->
    <div class="adm-main">
        <div class="adm-topbar">
            <h5><i class="bi bi-box-seam me-2"></i>Manage Products</h5>
            <button class="btn-grn" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast text-white bg-success border-0 show">
                    <div class="toast-body"><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

        <div class="adm-content">
            <div class="card-w" style="padding:0;overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Old Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                        <div style="font-size:11px;color:#aaa;">
                                            <?= htmlspecialchars($p['Prod_Size'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($p['Cat_Name'] ?? '—') ?></td>
                                    <td>₱<?= number_format($p['Prod_Price'], 2) ?></td>
                                    <td><?= $p['Prod_OldPrice'] ? '₱' . number_format($p['Prod_OldPrice'], 2) : '—' ?></td>
                                    <td>
                                        <?php if ($p['Prod_Stock'] == 0): ?>
                                            <span style="color:#d93025;font-weight:700;">Out</span>
                                        <?php elseif ($p['Prod_Stock'] <= 5): ?>
                                            <span style="color:#f5a623;font-weight:700;"><?= $p['Prod_Stock'] ?> ⚠</span>
                                        <?php else: ?>
                                            <?= $p['Prod_Stock'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?= $p['Prod_Status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($p['Prod_Status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $p['Prod_Id'] ?>">
                                                Edit
                                            </button>
                                            <a href="?toggle=<?= $p['Prod_Id'] ?>"
                                                class="btn btn-sm <?= $p['Prod_Status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                onclick="return confirm('Change product status?')">
                                                <?= $p['Prod_Status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <a href="?delete=<?= $p['Prod_Id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Permanently delete this product? This cannot be undone.')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>

                                </tr>

                                <!-- EDIT MODAL -->
                                <div class="modal fade" id="editModal<?= $p['Prod_Id'] ?>">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4">
                                            <div class="modal-header border-0">
                                                <h5>Edit Product</h5>
                                                <button class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="manage_products.php">
                                                <input type="hidden" name="update_product" value="1">
                                                <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                                                <div class="modal-body">
                                                    <input type="text" name="prod_name"
                                                        value="<?= htmlspecialchars($p['Prod_Name']) ?>"
                                                        class="landers-input" placeholder="Product Name" required>
                                                    <select name="cat_id" class="landers-input">
                                                        <?php
                                                        $categories->data_seek(0);
                                                        while ($c = $categories->fetch_assoc()):
                                                            ?>
                                                            <option value="<?= $c['Cat_Id'] ?>"
                                                                <?= $c['Cat_Id'] == $p['Prod_CatId'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($c['Cat_Name']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <input type="text" name="size"
                                                        value="<?= htmlspecialchars($p['Prod_Size'] ?? '') ?>"
                                                        class="landers-input" placeholder="Size (e.g. 470mL)">
                                                    <input type="number" name="price" step="0.01"
                                                        value="<?= $p['Prod_Price'] ?>" class="landers-input"
                                                        placeholder="Price" required>
                                                    <input type="number" name="old_price" step="0.01"
                                                        value="<?= $p['Prod_OldPrice'] ?>" class="landers-input"
                                                        placeholder="Old Price (optional)">
                                                    <input type="number" name="stock" value="<?= $p['Prod_Stock'] ?>"
                                                        class="landers-input" placeholder="Stock">
                                                    <input type="text" name="image_url"
                                                        value="<?= htmlspecialchars(basename($p['Prod_Image'] ?? '')) ?>"
                                                        class="landers-input" placeholder="Image.jpg">

                                                    <label
                                                        style="font-size:12px;color:var(--tm);font-weight:600;margin-top:4px;">Brand</label>
                                                    <input type="text" name="brand"
                                                        value="<?= htmlspecialchars($p['Prod_Brand'] ?? '') ?>"
                                                        class="landers-input" placeholder="e.g. Nestle, Green Cross">

                                                    <label
                                                        style="font-size:12px;color:var(--tm);font-weight:600;">Description</label>
                                                    <textarea name="description" class="landers-input" rows="3"
                                                        style="resize:vertical;"
                                                        placeholder="Brief product description..."><?= htmlspecialchars($p['Prod_Desc'] ?? '') ?></textarea>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="submit" class="btn-landers-green">Update Product</button>
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
        </div><!-- /adm-content -->
    </div><!-- /adm-main -->

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header border-0">
                    <h5>Add New Product</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Product Name *</label>
                        <input type="text" name="prod_name" class="landers-input" required>

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Category *</label>
                        <select name="cat_id" class="landers-input">
                            <option value="">Select Category</option>
                            <?php $categories->data_seek(0);
                            while ($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['Cat_Id'] ?>"><?= htmlspecialchars($c['Cat_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Size / Unit</label>
                        <input type="text" name="size" class="landers-input" placeholder="e.g. 470mL">

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:12px;color:var(--tm);font-weight:600;">Price (₱) *</label>
                                <input type="number" name="price" step="0.01" class="landers-input" required>
                            </div>
                            <div>
                                <label style="font-size:12px;color:var(--tm);font-weight:600;">Old Price – for discount
                                    (₱)</label>
                                <input type="number" name="old_price" step="0.01" class="landers-input"
                                    placeholder="Optional">
                            </div>
                        </div>

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Stock</label>
                        <input type="number" name="stock" value="0" class="landers-input">

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Image URL</label>
                        <input type="text" name="image_url" class="landers-input" placeholder="Image.jpg">

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Brand</label>
                        <input type="text" name="brand" class="landers-input" placeholder="e.g. Nestle, Green Cross">

                        <label style="font-size:12px;color:var(--tm);font-weight:600;">Description</label>
                        <textarea name="description" class="landers-input" rows="3"
                            placeholder="Brief product description shown on the product page..."
                            style="resize:vertical;"></textarea>

                        <p style="font-size:11px;color:var(--tm);margin-top:4px;">
                            💡 Set <strong>Old Price</strong> higher than <strong>Price</strong> to show a discount
                            badge on the storefront.
                        </p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="add_product" class="btn-grn w-100">Add Product</button>
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
