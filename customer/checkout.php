<?php
session_start();
require_once "../config/db.php";

// Must be logged in as customer
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['open_modal'] = 'login';
    header("Location: /LandersOnline/index.php");
    exit();
}

$acctId = $_SESSION['account_id'];
$custId = $_SESSION['customer_id'];

// ── LOAD CART ──────────────────────────────────────────────────────
$cartQ = $conn->prepare("
    SELECT c.Cart_Id, c.Cart_Qty,
           p.Prod_Id, p.Prod_Name, p.Prod_Price, p.Prod_Image, p.Prod_Size, p.Prod_Stock
    FROM Cart c
    JOIN Products p ON c.Cart_ProdId = p.Prod_Id
    WHERE c.Cart_AcctId = ?
    ORDER BY c.Cart_Id ASC
");
$cartQ->bind_param("i", $acctId);
$cartQ->execute();
$cartResult = $cartQ->get_result();
$cartItems  = [];
$subtotal   = 0;

while ($row = $cartResult->fetch_assoc()) {
    $cartItems[] = $row;
    $subtotal   += $row['Prod_Price'] * $row['Cart_Qty'];
}

// Redirect back if cart is empty
if (empty($cartItems)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: /LandersOnline/customer/cart.php");
    exit();
}

$delivFee  = $subtotal >= 5000 ? 0 : 150;
$total     = $subtotal + $delivFee;

// ── LOAD CUSTOMER INFO for pre-fill ────────────────────────────────
$custQ = $conn->prepare("SELECT Cust_Id, Cust_FName, Cust_LName, Cust_Phone FROM Customers WHERE Cust_Id = ? LIMIT 1");
$custQ->bind_param("i", $custId);
$custQ->execute();
$cust = $custQ->get_result()->fetch_assoc();

// ── PLACE ORDER ────────────────────────────────────────────────────
if (isset($_POST['place_order'])) {
    $address = trim($_POST['address'] ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $fname   = trim($_POST['fname'] ?? '');
    $lname   = trim($_POST['lname'] ?? '');
    $name    = $fname . ' ' . $lname;

    if (empty($address) || empty($fname) || empty($lname)) {
        $formError = "Please fill in your first name, last name, and delivery address.";
    } else {
        // Insert into Orders
        $ordStmt = $conn->prepare("
            INSERT INTO Orders (Ord_AcctId, Ord_CustId, Ord_Total, Ord_DelivFee, Ord_Address, Ord_Status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $ordStmt->bind_param("iidds", $acctId, $custId, $total, $delivFee, $address);
        $ordStmt->execute();
        $ordId = $conn->insert_id;

        // Insert each cart item into OrderItems
        $itemStmt = $conn->prepare("
            INSERT INTO OrderItems (OrdItem_OrdId, OrdItem_ProdId, OrdItem_ProdName, OrdItem_Price, OrdItem_Qty)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($cartItems as $item) {
            $itemStmt->bind_param("iisdi",
                $ordId,
                $item['Prod_Id'],
                $item['Prod_Name'],
                $item['Prod_Price'],
                $item['Cart_Qty']
            );
            $itemStmt->execute();

            // Decrement stock safely
            $stk = $conn->prepare("UPDATE Products SET Prod_Stock = Prod_Stock - ? WHERE Prod_Id = ? AND Prod_Stock >= ?");
            $stk->bind_param("iii", $item['Cart_Qty'], $item['Prod_Id'], $item['Cart_Qty']);
            $stk->execute();
        }

        // Clear cart
        $delCart = $conn->prepare("DELETE FROM Cart WHERE Cart_AcctId = ?");
        $delCart->bind_param("i", $acctId);
        $delCart->execute();

        // Update customer phone/name if changed
        $updCust = $conn->prepare("UPDATE Customers SET Cust_FName=?, Cust_LName=?, Cust_Phone=? WHERE Cust_Id=?");
        $updCust->bind_param("sssi", $fname, $lname, $phone, $custId);
        $updCust->execute();

        $_SESSION['cart_count'] = 0;
        $_SESSION['success']    = "Order #".str_pad($ordId,5,'0',STR_PAD_LEFT)." placed successfully!";
        header("Location: /LandersOnline/customer/my_orders.php");
        exit();
    }
}

$title = "Checkout";
include "../layout/layout.php";
?>

<div style="max-width:1100px;margin:30px auto;padding:0 24px 60px;">

    <h4 style="font-weight:700;color:var(--green-dark,#2d5a0e);margin-bottom:24px;">
        <i class="bi bi-bag-check me-2"></i>Checkout
    </h4>

    <?php if (!empty($formError)): ?>
    <div style="background:#fff0f0;border:1.5px solid #f5c0c0;color:#c0392b;border-radius:8px;
                padding:12px 16px;margin-bottom:20px;font-size:14px;">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($formError) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
    <div class="row g-4">

        <!-- LEFT: Delivery Details -->
        <div class="col-md-7">

            <!-- Delivery Info -->
            <div style="background:#fff;border:1px solid #cce5a0;border-radius:12px;padding:24px;margin-bottom:20px;">
                <h6 style="font-weight:700;margin-bottom:18px;color:#2d5a0e;">
                    <i class="bi bi-geo-alt me-2"></i>Delivery Information
                </h6>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#5a6a4a;">First Name *</label>
                        <input type="text" name="fname"
                               value="<?= htmlspecialchars($cust['Cust_FName'] ?? '') ?>"
                               style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;
                                      font-size:14px;margin-top:4px;outline:none;"
                               onfocus="this.style.borderColor='#3d7a18'" onblur="this.style.borderColor='#ddd'"
                               placeholder="Juan" required>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#5a6a4a;">Last Name *</label>
                        <input type="text" name="lname"
                               value="<?= htmlspecialchars($cust['Cust_LName'] ?? '') ?>"
                               style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;
                                      font-size:14px;margin-top:4px;outline:none;"
                               onfocus="this.style.borderColor='#3d7a18'" onblur="this.style.borderColor='#ddd'"
                               placeholder="Dela Cruz" required>
                    </div>
                </div>

                <label style="font-size:12px;font-weight:600;color:#5a6a4a;">Mobile Number</label>
                <input type="tel" name="phone"
                       value="<?= htmlspecialchars($cust['Cust_Phone'] ?? '') ?>"
                       style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;
                              font-size:14px;margin-bottom:12px;outline:none;"
                       onfocus="this.style.borderColor='#3d7a18'" onblur="this.style.borderColor='#ddd'"
                       placeholder="e.g. 09171234567">

                <label style="font-size:12px;font-weight:600;color:#5a6a4a;">Delivery Address *</label>
                <textarea name="address" rows="3" required
                          style="width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;
                                 font-size:14px;margin-bottom:0;outline:none;resize:vertical;"
                          onfocus="this.style.borderColor='#3d7a18'" onblur="this.style.borderColor='#ddd'"
                          placeholder="House/Unit No., Street, Barangay, City, Province"
                          ><?= htmlspecialchars($cust['Cust_Address'] ?? '') ?></textarea>
            </div>

            <!-- Payment Method -->
            <div style="background:#fff;border:1px solid #cce5a0;border-radius:12px;padding:24px;">
                <h6 style="font-weight:700;margin-bottom:18px;color:#2d5a0e;">
                    <i class="bi bi-credit-card me-2"></i>Payment Method
                </h6>

                <label style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                              border:1.5px solid #3d7a18;border-radius:8px;cursor:pointer;
                              background:#f0f8e8;margin-bottom:10px;">
                    <input type="radio" name="payment" value="cod" checked style="width:16px;height:16px;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Cash on Delivery</div>
                        <div style="font-size:12px;color:#aaa;">Pay when your order arrives</div>
                    </div>
                    <i class="bi bi-cash-coin ms-auto" style="font-size:22px;color:#3d7a18;"></i>
                </label>

                <label style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                              border:1.5px solid #ddd;border-radius:8px;cursor:pointer;
                              background:#fafafa;margin-bottom:0;opacity:.6;">
                    <input type="radio" name="payment" value="gcash" disabled style="width:16px;height:16px;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">GCash <span style="font-size:11px;color:#aaa;">(Coming soon)</span></div>
                        <div style="font-size:12px;color:#aaa;">Pay via GCash mobile wallet</div>
                    </div>
                    <i class="bi bi-phone ms-auto" style="font-size:22px;color:#aaa;"></i>
                </label>
            </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="col-md-5">
            <div style="background:#fff;border:1px solid #cce5a0;border-radius:12px;padding:24px;position:sticky;top:120px;">
                <h6 style="font-weight:700;margin-bottom:16px;color:#2d5a0e;">
                    <i class="bi bi-receipt me-2"></i>Order Summary
                </h6>

                <!-- Items list -->
                <div style="border-bottom:1px solid #eee;padding-bottom:14px;margin-bottom:14px;">
                <?php foreach ($cartItems as $item): ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <?php if (!empty($item['Prod_Image'])): ?>
                    <img src="/LandersOnline/<?= htmlspecialchars($item['Prod_Image']) ?>"
                         style="width:46px;height:46px;object-fit:contain;background:#f5f5f5;
                                border-radius:6px;border:1px solid #eee;padding:3px;flex-shrink:0;"
                         onerror="this.src='/LandersOnline/assets/images/no-image.png'">
                    <?php else: ?>
                    <div style="width:46px;height:46px;background:#f0f0f0;border-radius:6px;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-box-seam" style="color:#ccc;font-size:18px;"></i>
                    </div>
                    <?php endif; ?>

                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($item['Prod_Name']) ?>
                        </div>
                        <div style="font-size:11px;color:#aaa;"><?= htmlspecialchars($item['Prod_Size'] ?? '') ?></div>
                    </div>

                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:13px;font-weight:700;">
                            ₱<?= number_format($item['Prod_Price'] * $item['Cart_Qty'], 2) ?>
                        </div>
                        <div style="font-size:11px;color:#aaa;">x<?= $item['Cart_Qty'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Totals -->
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                    <span style="color:#666;">Subtotal</span>
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                    <span style="color:#666;">Delivery Fee</span>
                    <span style="color:<?= $delivFee === 0 ? '#3d7a18' : '#333' ?>;font-weight:600;">
                        <?= $delivFee === 0 ? 'FREE' : '₱'.number_format($delivFee, 2) ?>
                    </span>
                </div>

                <?php if ($delivFee > 0): ?>
                <div style="background:#f0f8e8;border-radius:6px;padding:8px 12px;
                            font-size:12px;color:#3d7a18;margin-bottom:12px;">
                    <i class="bi bi-truck me-1"></i>
                    Add ₱<?= number_format(5000 - $subtotal, 2) ?> more for FREE delivery!
                </div>
                <?php endif; ?>

                <hr style="margin:12px 0;">

                <div style="display:flex;justify-content:space-between;font-weight:800;font-size:17px;margin-bottom:20px;">
                    <span>Total</span>
                    <span style="color:#2d5a0e;">₱<?= number_format($total, 2) ?></span>
                </div>

                <button type="submit" name="place_order"
                        style="width:100%;background:#3d7a18;color:#fff;border:none;
                               padding:14px;border-radius:8px;font-size:15px;font-weight:700;
                               cursor:pointer;transition:background .2s;letter-spacing:.3px;"
                        onmouseover="this.style.background='#2d5a0e'"
                        onmouseout="this.style.background='#3d7a18'">
                    <i class="bi bi-bag-check me-2"></i>PLACE ORDER
                </button>

                <a href="/LandersOnline/customer/cart.php"
                   style="display:block;text-align:center;margin-top:12px;color:#3d7a18;
                          font-size:13px;text-decoration:none;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Cart
                </a>
            </div>
        </div>

    </div>
    </form>
</div>

</body>
</html>