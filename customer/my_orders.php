<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['open_modal'] = 'login';
    header("Location: /LandersOnline/index.php");
    exit();
}

$acctId = $_SESSION['account_id'];
$custId = $_SESSION['customer_id'];

// ── FILTERS ──
$statusFilter = $_GET['status'] ?? '';
$where  = "WHERE o.Ord_AcctId = ?";
$params = [$acctId];
$types  = "i";
if ($statusFilter !== '') {
    $where  .= " AND o.Ord_Status = ?";
    $params[] = $statusFilter;
    $types   .= "s";
}

// ── LOAD ORDERS ──
$ordQ = $conn->prepare("
    SELECT o.*,
           COUNT(oi.OrdItem_Id)          AS item_count,
           SUM(oi.OrdItem_Qty)           AS total_qty
    FROM Orders o
    LEFT JOIN OrderItems oi ON oi.OrdItem_OrdId = o.Ord_Id
    $where
    GROUP BY o.Ord_Id
    ORDER BY o.Ord_Id DESC
");
$ordQ->bind_param($types, ...$params);
$ordQ->execute();
$orders = $ordQ->get_result();

// ── COUNT PER STATUS ──
$countQ = $conn->prepare("
    SELECT Ord_Status, COUNT(*) AS cnt
    FROM Orders WHERE Ord_AcctId = ?
    GROUP BY Ord_Status
");
$countQ->bind_param("i", $acctId);
$countQ->execute();
$statusCounts = [];
$result = $countQ->get_result();
while ($r = $result->fetch_assoc()) {
    $statusCounts[$r['Ord_Status']] = $r['cnt'];
}
$totalOrders = array_sum($statusCounts);

function productImageSrc($image)
{
    $image = trim((string) $image);

    if ($image === '') {
        return '/LandersOnline/assets/images/no-image.png';
    }

    if (preg_match('#^https?://#i', $image) || strpos($image, '/') === 0) {
        return $image;
    }

    return '/LandersOnline/assets/images/' . ltrim($image, '/');
}

$title = "My Orders";
include "../layout/layout.php";
?>

<div style="max-width:1000px;margin:30px auto;padding:0 24px 60px;">

    <h4 style="font-weight:700;color:#2d5a0e;margin-bottom:6px;">
        <i class="bi bi-bag me-2"></i>My Orders
    </h4>
    <p style="color:#aaa;font-size:13px;margin-bottom:22px;">
        <?= $totalOrders ?> order(s) total
    </p>

    <!-- Status filter tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
        <?php
        $tabs = [
            ''            => 'All',
            'pending'     => 'Pending',
            'processing'  => 'Processing',
            'shipped'     => 'Shipped',
            'delivered'   => 'Delivered',
            'cancelled'   => 'Cancelled',
        ];
        foreach ($tabs as $val => $lbl):
            $cnt     = $val === '' ? $totalOrders : ($statusCounts[$val] ?? 0);
            $isActive = $statusFilter === $val;
        ?>
        <a href="?status=<?= $val ?>"
           style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;
                  text-decoration:none;transition:all .15s;
                  background:<?= $isActive ? '#3d7a18' : '#fff' ?>;
                  color:<?= $isActive ? '#fff' : '#555' ?>;
                  border:1.5px solid <?= $isActive ? '#3d7a18' : '#ddd' ?>;">
            <?= $lbl ?>
            <?php if ($cnt > 0): ?>
            <span style="background:<?= $isActive ? 'rgba(255,255,255,.25)' : '#f0f0f0' ?>;
                         color:<?= $isActive ? '#fff' : '#888' ?>;
                         font-size:11px;padding:1px 7px;border-radius:10px;margin-left:4px;">
                <?= $cnt ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($orders->num_rows > 0): ?>

        <?php while ($ord = $orders->fetch_assoc()):

            // Status badge colours
            $badgeStyle = match($ord['Ord_Status']) {
                'pending'    => 'background:#fff8e1;color:#b7700a;border:1px solid #f5a623;',
                'processing' => 'background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;',
                'shipped'    => 'background:#e8f5e9;color:#2e7d32;border:1px solid #81c784;',
                'delivered'  => 'background:#e8f5e9;color:#1b5e20;border:1px solid #388e3c;',
                'cancelled'  => 'background:#fce4ec;color:#b71c1c;border:1px solid #ef9a9a;',
                default      => 'background:#f5f5f5;color:#555;border:1px solid #ddd;',
            };

            // Status icon
            $statusIcon = match($ord['Ord_Status']) {
                'pending'    => 'bi-clock',
                'processing' => 'bi-gear',
                'shipped'    => 'bi-truck',
                'delivered'  => 'bi-check-circle-fill',
                'cancelled'  => 'bi-x-circle',
                default      => 'bi-circle',
            };

            // Load items for this order
            $itemQ = $conn->prepare("
                SELECT oi.*, p.Prod_Image
                FROM OrderItems oi
                LEFT JOIN Products p ON oi.OrdItem_ProdId = p.Prod_Id
                WHERE oi.OrdItem_OrdId = ?
                ORDER BY oi.OrdItem_Id ASC
            ");
            $itemQ->bind_param("i", $ord['Ord_Id']);
            $itemQ->execute();
            $items = $itemQ->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <div style="background:#fff;border:1px solid #dde8cc;border-radius:12px;
                    margin-bottom:16px;overflow:hidden;transition:box-shadow .2s;"
             onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'"
             onmouseout="this.style.boxShadow='none'">

            <!-- Order header -->
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:14px 20px;background:#f8fbf3;border-bottom:1px solid #dde8cc;
                        flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-weight:700;font-size:14px;color:#2d5a0e;">
                        Order #<?= str_pad($ord['Ord_Id'], 5, '0', STR_PAD_LEFT) ?>
                    </span>
                    <span style="font-size:12px;color:#aaa;">
                        <?= date('M j, Y · g:i A', strtotime($ord['Ord_CreatedAt'])) ?>
                    </span>
                </div>
                <span style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;<?= $badgeStyle ?>">
                    <i class="bi <?= $statusIcon ?> me-1"></i><?= ucfirst($ord['Ord_Status']) ?>
                </span>
            </div>

            <!-- Progress tracker (not shown for cancelled) -->
            <?php if ($ord['Ord_Status'] !== 'cancelled'): ?>
            <?php
            $steps   = ['pending','processing','shipped','delivered'];
            $current = array_search($ord['Ord_Status'], $steps);
            if ($current === false) $current = 0;
            ?>
            <div style="padding:16px 20px 10px;border-bottom:1px solid #f0f0f0;">
                <div style="display:flex;align-items:center;justify-content:space-between;position:relative;">
                    <!-- Line behind -->
                    <div style="position:absolute;top:14px;left:14px;right:14px;height:3px;
                                background:#e0e0e0;z-index:0;"></div>
                    <!-- Filled line -->
                    <div style="position:absolute;top:14px;left:14px;height:3px;z-index:1;
                                background:#3d7a18;
                                width:<?= $current === 0 ? '0%' : ($current === 1 ? '33%' : ($current === 2 ? '66%' : '100%')) ?>;
                                transition:width .3s;"></div>

                    <?php
                    $stepLabels = ['Order Placed','Processing','Shipped','Delivered'];
                    $stepIcons  = ['bi-bag-check','bi-gear','bi-truck','bi-house-check'];
                    foreach ($steps as $i => $step):
                        $done   = $i <= $current;
                        $active = $i === $current;
                    ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px;
                                z-index:2;flex:1;<?= $i === 0 ? 'align-items:flex-start;' : ($i === 3 ? 'align-items:flex-end;' : '') ?>">
                        <div style="width:28px;height:28px;border-radius:50%;
                                    background:<?= $done ? '#3d7a18' : '#e0e0e0' ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    border:3px solid <?= $active ? '#2d5a0e' : ($done ? '#3d7a18' : '#e0e0e0') ?>;">
                            <i class="bi <?= $stepIcons[$i] ?>"
                               style="font-size:12px;color:<?= $done ? '#fff' : '#aaa' ?>;"></i>
                        </div>
                        <span style="font-size:10px;font-weight:<?= $active ? '700' : '500' ?>;
                                     color:<?= $active ? '#2d5a0e' : ($done ? '#3d7a18' : '#aaa') ?>;
                                     white-space:nowrap;">
                            <?= $stepLabels[$i] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Items -->
            <div style="padding:14px 20px;">
                <?php foreach ($items as $idx => $item):
                    if ($idx >= 3 && count($items) > 3): // Show max 3, collapse rest ?>
                <div id="more-<?= $ord['Ord_Id'] ?>" style="display:none;">
                    <?php endif; ?>

                <div style="display:flex;align-items:center;gap:12px;
                            <?= $idx > 0 ? 'margin-top:10px;padding-top:10px;border-top:1px solid #f5f5f5;' : '' ?>">
                    <?php
                    $imgSrc = productImageSrc($item['Prod_Image']);
                    ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                         style="width:50px;height:50px;object-fit:contain;background:#f8f8f8;
                                border-radius:8px;border:1px solid #eee;padding:3px;flex-shrink:0;"
                         onerror="this.src='/LandersOnline/assets/images/no-image.png'">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($item['OrdItem_ProdName']) ?>
                        </div>
                        <div style="font-size:12px;color:#aaa;">
                            ₱<?= number_format($item['OrdItem_Price'], 2) ?> × <?= $item['OrdItem_Qty'] ?>
                        </div>
                    </div>
                    <div style="font-weight:700;font-size:13px;color:#2d5a0e;flex-shrink:0;">
                        ₱<?= number_format($item['OrdItem_Price'] * $item['OrdItem_Qty'], 2) ?>
                    </div>
                </div>

                <?php if ($idx >= 3 && count($items) > 3): ?>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <?php if (count($items) > 3): ?>
                <button onclick="
                    const el = document.getElementById('more-<?= $ord['Ord_Id'] ?>');
                    const isHidden = el.style.display === 'none';
                    el.style.display = isHidden ? 'block' : 'none';
                    this.textContent = isHidden
                        ? '▲ Show less'
                        : '▼ Show <?= count($items) - 3 ?> more item(s)';
                " style="background:none;border:none;color:#3d7a18;font-size:12px;
                         font-weight:600;cursor:pointer;margin-top:10px;padding:0;">
                    ▼ Show <?= count($items) - 3 ?> more item(s)
                </button>
                <?php endif; ?>
            </div>

            <!-- Order footer: totals + address -->
            <div style="padding:14px 20px;background:#fafafa;border-top:1px solid #f0f0f0;
                        display:flex;justify-content:space-between;align-items:flex-end;
                        flex-wrap:wrap;gap:12px;">
                <div style="font-size:12px;color:#888;max-width:60%;">
                    <i class="bi bi-geo-alt me-1"></i>
                    <?= htmlspecialchars($ord['Ord_Address'] ?? '—') ?>
                    <span style="margin-left:10px;">
                        <i class="bi bi-box2 me-1"></i><?= $ord['item_count'] ?> item(s)
                    </span>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px;color:#aaa;">
                        Delivery: <?= $ord['Ord_DelivFee'] == 0 ? '<span style="color:#3d7a18;font-weight:600;">FREE</span>' : '₱'.number_format($ord['Ord_DelivFee'],2) ?>
                    </div>
                    <div style="font-size:17px;font-weight:800;color:#2d5a0e;">
                        Total: ₱<?= number_format($ord['Ord_Total'], 2) ?>
                    </div>
                </div>
            </div>

        </div>
        <?php endwhile; ?>

    <?php else: ?>
    <!-- Empty state -->
    <div style="text-align:center;padding:80px 20px;background:#fff;
                border-radius:12px;border:1px solid #dde8cc;">
        <i class="bi bi-bag-x" style="font-size:60px;color:#d4e8bb;"></i>
        <h5 style="margin-top:18px;color:#aaa;font-weight:600;">
            <?= $statusFilter ? 'No '.ucfirst($statusFilter).' orders' : 'No orders yet' ?>
        </h5>
        <p style="color:#bbb;font-size:13px;margin-bottom:18px;">
            <?= $statusFilter ? 'You have no orders with this status.' : 'Start shopping and your orders will appear here.' ?>
        </p>
        <a href="/LandersOnline/customer/shop.php"
           style="display:inline-block;background:#3d7a18;color:#fff;
                  padding:11px 28px;border-radius:8px;text-decoration:none;
                  font-weight:700;font-size:14px;">
            <i class="bi bi-shop me-2"></i>Start Shopping
        </a>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
