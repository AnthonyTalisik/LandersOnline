<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['account_id'])) {
    $_SESSION['open_modal'] = 'login';
    header("Location: /landersonline/index.php");
    exit();
}

$acctId = $_SESSION['account_id'];

// ── ADD TO CART ──
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $prodId = (int)$_POST['prod_id'];
    $qty    = max(1, (int)($_POST['qty'] ?? 1));

    // Check existing
    $check = $conn->prepare("SELECT Cart_Id, Cart_Qty FROM Cart WHERE Cart_AcctId=? AND Cart_ProdId=?");
    $check->bind_param("ii", $acctId, $prodId);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $newQty = $existing['Cart_Qty'] + $qty;
        $upd = $conn->prepare("UPDATE Cart SET Cart_Qty=? WHERE Cart_Id=?");
        $upd->bind_param("ii", $newQty, $existing['Cart_Id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO Cart (Cart_AcctId, Cart_ProdId, Cart_Qty) VALUES (?,?,?)");
        $ins->bind_param("iii", $acctId, $prodId, $qty);
        $ins->execute();
    }

    // Update session count
    $cq = $conn->prepare("SELECT SUM(Cart_Qty) as t FROM Cart WHERE Cart_AcctId=?");
    $cq->bind_param("i", $acctId);
    $cq->execute();
    $_SESSION['cart_count'] = (int)$cq->get_result()->fetch_assoc()['t'];

    $_SESSION['success'] = "Item added to cart!";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/landersonline/customer/cart.php'));
    exit();
}

// ── UPDATE QTY ──
if (isset($_POST['update_qty'])) {
    $cartId = (int)$_POST['cart_id'];
    $qty    = max(1, (int)$_POST['qty']);
    $upd = $conn->prepare("UPDATE Cart SET Cart_Qty=? WHERE Cart_Id=? AND Cart_AcctId=?");
    $upd->bind_param("iii", $qty, $cartId, $acctId);
    $upd->execute();
    header("Location: cart.php");
    exit();
}

// ── REMOVE ITEM ──
if (isset($_GET['remove'])) {
    $cartId = (int)$_GET['remove'];
    $del = $conn->prepare("DELETE FROM Cart WHERE Cart_Id=? AND Cart_AcctId=?");
    $del->bind_param("ii", $cartId, $acctId);
    $del->execute();

    $cq = $conn->prepare("SELECT SUM(Cart_Qty) as t FROM Cart WHERE Cart_AcctId=?");
    $cq->bind_param("i", $acctId);
    $cq->execute();
    $_SESSION['cart_count'] = (int)$cq->get_result()->fetch_assoc()['t'];

    header("Location: cart.php");
    exit();
}

// ── LOAD CART ──
$cartItems = $conn->prepare("
    SELECT c.*, p.Prod_Name, p.Prod_Price, p.Prod_Image, p.Prod_Size
    FROM Cart c
    JOIN Products p ON c.Cart_ProdId = p.Prod_Id
    WHERE c.Cart_AcctId = ?
");
$cartItems->bind_param("i", $acctId);
$cartItems->execute();
$items = $cartItems->get_result();

$grandTotal = 0;

$title = "My Cart";
include "../layout/layout.php";
?>

<div style="max-width:1100px;margin:30px auto;padding:0 24px;">
    <h4 style="font-weight:700;color:var(--green-dark);margin-bottom:24px;">
        <i class="bi bi-cart2 me-2"></i>My Cart
    </h4>

    <?php if ($items->num_rows > 0): ?>
    <div class="row g-4">

        <!-- Cart Items -->
        <div class="col-md-8">
            <div style="background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;">
                <?php
                $allItems = [];
                while ($item = $items->fetch_assoc()) {
                    $allItems[] = $item;
                    $grandTotal += $item['Prod_Price'] * $item['Cart_Qty'];
                }
                foreach ($allItems as $item):
                ?>
                <div style="display:flex;align-items:center;gap:16px;padding:16px;border-bottom:1px solid var(--border);">
                    <img src="<?= htmlspecialchars($item['Prod_Image'] ?? '/landersonline/assets/images/no-image.png') ?>"
                         style="width:70px;height:70px;object-fit:contain;background:#fafafa;border-radius:8px;padding:4px;">

                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($item['Prod_Name']) ?></div>
                        <div style="font-size:12px;color:#aaa;"><?= htmlspecialchars($item['Prod_Size'] ?? '') ?></div>
                        <div style="font-weight:700;color:var(--green-dark);margin-top:4px;">
                            ₱<?= number_format($item['Prod_Price'], 2) ?>
                        </div>
                    </div>

                    <!-- Qty control -->
                    <form method="POST" style="display:flex;align-items:center;gap:6px;">
                        <input type="hidden" name="cart_id" value="<?= $item['Cart_Id'] ?>">
                        <button type="submit" name="update_qty" formmethod="post"
                                onclick="this.previousElementSibling.previousElementSibling.value=Math.max(1,parseInt(this.form.qty.value)-1);"
                                style="width:28px;height:28px;border:1.5px solid var(--border);
                                       background:#fff;border-radius:4px;cursor:pointer;font-size:14px;
                                       display:flex;align-items:center;justify-content:center;">−</button>
                        <input type="number" name="qty" value="<?= $item['Cart_Qty'] ?>" min="1"
                               style="width:48px;text-align:center;border:1.5px solid var(--border);
                                      border-radius:4px;padding:4px;">
                        <button type="submit" name="update_qty"
                                style="width:28px;height:28px;border:1.5px solid var(--border);
                                       background:#fff;border-radius:4px;cursor:pointer;font-size:14px;
                                       display:flex;align-items:center;justify-content:center;">+</button>
                    </form>

                    <div style="font-weight:700;min-width:90px;text-align:right;">
                        ₱<?= number_format($item['Prod_Price'] * $item['Cart_Qty'], 2) ?>
                    </div>

                    <a href="?remove=<?= $item['Cart_Id'] ?>"
                       onclick="return confirm('Remove this item?')"
                       style="color:#e03030;text-decoration:none;font-size:18px;">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-md-4">
            <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:24px;position:sticky;top:120px;">
                <h6 style="font-weight:700;margin-bottom:16px;">Order Summary</h6>

                <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;">
                    <span>Subtotal</span>
                    <span>₱<?= number_format($grandTotal, 2) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;">
                    <span>Delivery Fee</span>
                    <span style="color:var(--green-main);">
                        <?= $grandTotal >= 5000 ? 'FREE' : '₱150.00' ?>
                    </span>
                </div>

                <?php if ($grandTotal < 5000): ?>
                <div style="background:var(--green-bg);border-radius:6px;padding:10px;font-size:12px;
                            color:var(--green-dark);margin-bottom:12px;">
                    <i class="bi bi-truck me-1"></i>
                    Add ₱<?= number_format(5000 - $grandTotal, 2) ?> more for FREE delivery!
                </div>
                <?php else: ?>
                <div style="background:var(--green-bg);border-radius:6px;padding:10px;font-size:12px;
                            color:var(--green-dark);margin-bottom:12px;">
                    <i class="bi bi-check-circle me-1"></i> You qualify for FREE delivery!
                </div>
                <?php endif; ?>

                <hr>

                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;margin-bottom:16px;">
                    <span>Total</span>
                    <span>₱<?= number_format($grandTotal + ($grandTotal < 5000 ? 150 : 0), 2) ?></span>
                </div>

                <a href="/landersonline/customer/checkout.php" class="btn-landers-green"
                   style="display:block;text-align:center;text-decoration:none;padding:13px;
                          border-radius:8px;font-weight:700;font-size:15px;background:var(--green-main);color:#fff;">
                    PROCEED TO CHECKOUT
                </a>

                <a href="/landersonline/customer/shop.php"
                   style="display:block;text-align:center;margin-top:10px;color:var(--green-main);
                          font-size:13px;text-decoration:none;">
                    <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                </a>
            </div>
        </div>

    </div>

    <?php else: ?>
    <div style="text-align:center;padding:80px;background:#fff;border-radius:12px;border:1px solid var(--border);">
        <i class="bi bi-cart-x" style="font-size:64px;color:#ccc;"></i>
        <h5 style="margin-top:16px;color:#aaa;">Your cart is empty</h5>
        <a href="/landersonline/customer/shop.php"
           style="display:inline-block;margin-top:16px;background:var(--green-main);color:#fff;
                  padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;">
            Start Shopping
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
