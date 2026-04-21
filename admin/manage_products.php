<?php
ob_start();
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /landersonline/index.php");
    exit();
}

// ── ADD PRODUCT ──
if (isset($_POST['add_product'])) {
    $name     = trim($_POST['prod_name']);
    $catId    = (int)$_POST['cat_id'];
    $price    = (float)$_POST['price'];
    $oldPrice = $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
    $size     = trim($_POST['size'] ?? '');
    $stock    = (int)$_POST['stock'];
    $image    = trim($_POST['image_url'] ?? '');

    $r   = $conn->query("SELECT MAX(Prod_Id) AS m FROM Products");
    $id  = ($r->fetch_assoc()['m'] ?? 1000) + 1;

    $stmt = $conn->prepare("
        INSERT INTO Products (Prod_Id, Prod_CatId, Prod_Name, Prod_Size, Prod_Price, Prod_OldPrice, Prod_Stock, Prod_Image, Prod_Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("iissddiis", $id, $catId, $name, $size, $price, $oldPrice, $stock, $image);
    // Fix: correct bind
    $stmt = $conn->prepare("
        INSERT INTO Products (Prod_Id, Prod_CatId, Prod_Name, Prod_Size, Prod_Price, Prod_OldPrice, Prod_Stock, Prod_Image, Prod_Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("iissdids", $id, $catId, $name, $size, $price, $oldPrice, $stock, $image);

    $_SESSION['success'] = $stmt->execute() ? "Product added!" : "Error: " . $conn->error;
    header("Location: manage_products.php");
    exit();
}

// ── TOGGLE STATUS ──
if (isset($_GET['toggle'])) {
    $pid = (int)$_GET['toggle'];
    $r   = $conn->prepare("SELECT Prod_Status FROM Products WHERE Prod_Id=?");
    $r->bind_param("i", $pid);
    $r->execute();
    $cur = $r->get_result()->fetch_assoc()['Prod_Status'];
    $new = $cur === 'active' ? 'inactive' : 'active';
    $upd = $conn->prepare("UPDATE Products SET Prod_Status=? WHERE Prod_Id=?");
    $upd->bind_param("si", $new, $pid);
    $upd->execute();
    $_SESSION['success'] = "Product status updated.";
    header("Location: manage_products.php");
    exit();
}

$categories = $conn->query("SELECT * FROM Categories WHERE Cat_Status='active' ORDER BY Cat_Name");
$products   = $conn->query("
    SELECT p.*, c.Cat_Name
    FROM Products p
    LEFT JOIN Categories c ON p.Prod_CatId = c.Cat_Id
    ORDER BY p.Prod_Id DESC
");

$title = "Manage Products";
include "../layout/layout.php";
?>

<div style="max-width:1200px;margin:30px auto;padding:0 24px;">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h4 style="font-weight:700;color:var(--green-dark);margin:0;">
            <i class="bi bi-box-seam me-2"></i>Manage Products
        </h4>
        <button class="btn-add-cart" style="width:auto;padding:10px 20px;"
                data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle me-2"></i>Add Product
        </button>
    </div>

    <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;">
        <table class="table" style="font-size:13px;">
            <thead style="background:var(--green-bg);">
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
                    <div style="font-size:11px;color:#aaa;"><?= htmlspecialchars($p['Prod_Size'] ?? '') ?></div>
                </td>
                <td><?= htmlspecialchars($p['Cat_Name'] ?? '—') ?></td>
                <td>₱<?= number_format($p['Prod_Price'], 2) ?></td>
                <td><?= $p['Prod_OldPrice'] ? '₱'.number_format($p['Prod_OldPrice'],2) : '—' ?></td>
                <td><?= $p['Prod_Stock'] ?></td>
                <td>
                    <span class="badge <?= $p['Prod_Status']==='active'?'bg-success':'bg-danger' ?>">
                        <?= ucfirst($p['Prod_Status']) ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $p['Prod_Id'] ?>">
                            Edit
                        </button>
                        <a href="?toggle=<?= $p['Prod_Id'] ?>"
                           class="btn btn-sm <?= $p['Prod_Status']==='active'?'btn-outline-danger':'btn-outline-success' ?>"
                           onclick="return confirm('Change status?')">
                            <?= $p['Prod_Status']==='active'?'Deactivate':'Activate' ?>
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
                                <input type="text" name="prod_name" value="<?= htmlspecialchars($p['Prod_Name']) ?>"
                                       class="landers-input" placeholder="Product Name" required>
                                <select name="cat_id" class="landers-input">
                                    <?php
                                    $categories->data_seek(0);
                                    while ($c = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?= $c['Cat_Id'] ?>" <?= $c['Cat_Id']==$p['Prod_CatId']?'selected':'' ?>>
                                        <?= htmlspecialchars($c['Cat_Name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="text" name="size" value="<?= htmlspecialchars($p['Prod_Size'] ?? '') ?>"
                                       class="landers-input" placeholder="Size (e.g. 470mL)">
                                <input type="number" name="price" step="0.01" value="<?= $p['Prod_Price'] ?>"
                                       class="landers-input" placeholder="Price" required>
                                <input type="number" name="old_price" step="0.01" value="<?= $p['Prod_OldPrice'] ?>"
                                       class="landers-input" placeholder="Old Price (optional)">
                                <input type="number" name="stock" value="<?= $p['Prod_Stock'] ?>"
                                       class="landers-input" placeholder="Stock">
                                <input type="text" name="image_url" value="<?= htmlspecialchars($p['Prod_Image'] ?? '') ?>"
                                       class="landers-input" placeholder="Image URL">
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

<!-- ADD PRODUCT MODAL -->
<div class="modal fade" id="addProductModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5>Add Product</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="text" name="prod_name" class="landers-input" placeholder="Product Name" required>
                    <select name="cat_id" class="landers-input">
                        <option value="">Select Category</option>
                        <?php
                        $categories->data_seek(0);
                        while ($c = $categories->fetch_assoc()):
                        ?>
                        <option value="<?= $c['Cat_Id'] ?>"><?= htmlspecialchars($c['Cat_Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="text" name="size" class="landers-input" placeholder="Size (e.g. 470mL)">
                    <input type="number" name="price" step="0.01" class="landers-input" placeholder="Price" required>
                    <input type="number" name="old_price" step="0.01" class="landers-input" placeholder="Old Price (optional)">
                    <input type="number" name="stock" class="landers-input" placeholder="Stock" value="0">
                    <input type="text" name="image_url" class="landers-input" placeholder="Image URL (optional)">
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="add_product" class="btn-landers-green">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
