<?php
session_start();
require_once "../config/db.php";
require_once "../config/firebase_store.php";
$store = firebaseStore();

// Require a valid product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /LandersOnline/index.php');
    exit();
}
$prodId = (int) $_GET['id'];

$prod = $store->productById($prodId, true);

if (!$prod) {
    header('Location: /LandersOnline/index.php');
    exit();
}

$relatedRows = array_values(array_filter(
    $store->productsWithCategory((int)$prod['Prod_CatId'], true)->fetch_all(),
    fn($p) => (int)$p['Prod_Id'] !== $prodId
));
$related = $store->result(array_slice($relatedRows, 0, 5));

$title = htmlspecialchars($prod['Prod_Name']) . " – LandersOnline";
include "../layout/layout.php";

// Discount calc
$hasDiscount = $prod['Prod_OldPrice'] && $prod['Prod_OldPrice'] > $prod['Prod_Price'];
$discPct = $hasDiscount ? round((1 - $prod['Prod_Price'] / $prod['Prod_OldPrice']) * 100) : 0;

// Add to cart success flash
$cartMsg = $_SESSION['cart_msg'] ?? '';
unset($_SESSION['cart_msg']);
?>

<div style="max-width:1300px;margin:0 auto;padding:20px 24px;">

    <!-- Breadcrumb -->
    <nav style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
        <a href="/LandersOnline/index.php" style="color:var(--green-main);text-decoration:none;">Home</a>
        <span style="margin:0 6px;">/</span>
        <a href="/LandersOnline/index.php?cat=<?= $prod['Cat_Id'] ?>"
            style="color:var(--green-main);text-decoration:none;"><?= htmlspecialchars($prod['Cat_Name'] ?? 'Category') ?></a>
        <span style="margin:0 6px;">/</span>
        <span style="color:var(--text-dark);font-weight:600;"><?= htmlspecialchars($prod['Prod_Name']) ?></span>
    </nav>

    <?php if ($cartMsg): ?>
        <div style="background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;padding:12px 18px;
                    border-radius:8px;margin-bottom:16px;font-size:14px;font-weight:600;">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($cartMsg) ?>
        </div>
    <?php endif; ?>

    <!-- ══ MAIN PRODUCT SECTION ══ -->
    <div style="display:flex;gap:32px;background:#fff;border:1px solid var(--border);
                border-radius:12px;padding:28px;margin-bottom:28px;flex-wrap:wrap;">

        <!-- LEFT: Image -->
        <div style="flex:0 0 360px;max-width:360px;">

            <!-- Main image -->
            <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;
                        background:#fafafa;display:flex;align-items:center;justify-content:center;
                        height:340px;margin-bottom:12px;">
                <img id="mainImg" src="<?= htmlspecialchars($prod['Prod_Image'] ?? 'assets/images/no-image.png') ?>"
                    alt="<?= htmlspecialchars($prod['Prod_Name']) ?>"
                    style="max-height:100%;max-width:100%;object-fit:contain;padding:20px;">
            </div>

            <!-- Thumbnail strip (same image repeated for demo; swap src for real multi-image support) -->
            <div style="display:flex;gap:8px;">
                <?php
                $thumbImg = htmlspecialchars($prod['Prod_Image'] ?? 'assets/images/no-image.png');
                for ($t = 0; $t < 3; $t++):
                    ?>
                    <div onclick="document.getElementById('mainImg').src='<?= $thumbImg ?>'" style="width:72px;height:72px;border:2px solid <?= $t === 0 ? 'var(--green-main)' : 'var(--border)' ?>;
                            border-radius:8px;overflow:hidden;cursor:pointer;background:#fafafa;
                            display:flex;align-items:center;justify-content:center;
                            transition:border-color .15s;" onmouseover="this.style.borderColor='var(--green-main)'"
                        onmouseout="this.style.borderColor='<?= $t === 0 ? 'var(--green-main)' : 'var(--border)' ?>'">
                        <img src="<?= $thumbImg ?>" style="max-height:100%;max-width:100%;object-fit:contain;padding:6px;">
                    </div>
                <?php endfor; ?>
            </div>

        </div>

        <!-- RIGHT: Details -->
        <div style="flex:1;min-width:260px;">

            <!-- Category tag -->
            <a href="/LandersOnline/index.php?cat=<?= $prod['Cat_Id'] ?>" style="display:inline-block;background:var(--green-bg);color:var(--green-dark);
                      font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                      text-decoration:none;margin-bottom:10px;border:1px solid var(--border);">
                <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($prod['Cat_Name'] ?? '') ?>
            </a>

            <h2 style="font-size:22px;font-weight:800;color:var(--text-dark);margin:0 0 4px;">
                <?= htmlspecialchars($prod['Prod_Name']) ?>
            </h2>

            <?php if ($prod['Prod_Size']): ?>
                <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">
                    <?= htmlspecialchars($prod['Prod_Size']) ?>
                </div>
            <?php endif; ?>

            <!-- Stars placeholder -->
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:16px;">
                <span style="color:#f5a623;font-size:14px;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                <span style="font-size:12px;color:#aaa;">(0 reviews)</span>
            </div>

            <!-- Price block -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
                <?php if ($hasDiscount): ?>
                    <span style="background:#e03030;color:#fff;font-size:13px;font-weight:700;
                                 padding:3px 10px;border-radius:5px;"><?= $discPct ?>% OFF</span>
                <?php endif; ?>
                <span style="font-size:30px;font-weight:800;color:var(--text-dark);">
                    ₱<?= number_format($prod['Prod_Price'], 2) ?>
                </span>
                <?php if ($hasDiscount): ?>
                    <span style="font-size:16px;color:#aaa;text-decoration:line-through;">
                        ₱<?= number_format($prod['Prod_OldPrice'], 2) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Stock status -->
            <div style="margin-bottom:20px;">
                <?php if ($prod['Prod_Stock'] > 10): ?>
                    <span style="color:#2e7d32;font-size:13px;font-weight:600;">
                        <i class="bi bi-check-circle-fill me-1"></i>In Stock
                        <span style="color:#aaa;font-weight:400;">(<?= $prod['Prod_Stock'] ?> available)</span>
                    </span>
                <?php elseif ($prod['Prod_Stock'] > 0): ?>
                    <span style="color:#e65100;font-size:13px;font-weight:600;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Low Stock — only <?= $prod['Prod_Stock'] ?>
                        left!
                    </span>
                <?php else: ?>
                    <span style="color:#c62828;font-size:13px;font-weight:600;">
                        <i class="bi bi-x-circle-fill me-1"></i>Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Quantity + Add to Cart -->
            <?php if ($prod['Prod_Stock'] > 0): ?>
                <form method="POST" action="/LandersOnline/customer/cart.php"
                    style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="prod_id" value="<?= $prod['Prod_Id'] ?>">

                    <!-- Qty stepper -->
                    <div style="display:flex;align-items:center;border:1px solid var(--border);
                            border-radius:8px;overflow:hidden;background:#fff;">
                        <button type="button" onclick="stepQty(-1)" style="width:38px;height:42px;border:none;background:none;font-size:18px;
                                   cursor:pointer;color:var(--text-dark);font-weight:700;">−</button>
                        <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?= $prod['Prod_Stock'] ?>"
                            style="width:50px;height:42px;border:none;text-align:center;
                                  font-size:15px;font-weight:700;color:var(--text-dark);
                                  -moz-appearance:textfield;outline:none;">
                        <button type="button" onclick="stepQty(1)" style="width:38px;height:42px;border:none;background:none;font-size:18px;
                                   cursor:pointer;color:var(--text-dark);font-weight:700;">+</button>
                    </div>

                    <button type="submit" style="flex:1;min-width:160px;height:42px;background:var(--green-main);color:#fff;
                               border:none;border-radius:8px;font-size:14px;font-weight:700;
                               cursor:pointer;letter-spacing:.5px;transition:background .2s;"
                        onmouseover="this.style.background='var(--green-dark)'"
                        onmouseout="this.style.background='var(--green-main)'">
                        <i class="bi bi-cart-plus me-2"></i>ADD TO CART
                    </button>
                </form>
            <?php endif; ?>

            <!-- Delivery note -->
            <div style="background:var(--green-bg);border:1px solid var(--border);border-radius:8px;
                        padding:12px 16px;font-size:13px;color:var(--text-muted);display:flex;gap:10px;
                        align-items:flex-start;">
                <i class="bi bi-truck" style="color:var(--green-main);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
                <div>
                    <strong style="color:var(--text-dark);">Free delivery</strong> on orders over ₱2,000.<br>
                    Estimated arrival: <strong style="color:var(--text-dark);">1–3 business days</strong>
                </div>
            </div>

        </div>
    </div>

    <!-- ══ DESCRIPTION & SPECS TABS ══ -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:12px;
                overflow:hidden;margin-bottom:28px;">

        <!-- Tab buttons -->
        <div style="display:flex;border-bottom:2px solid var(--border);">
            <button onclick="switchTab('desc')" id="tab-desc" style="padding:14px 24px;font-size:14px;font-weight:700;border:none;
                           background:none;cursor:pointer;color:var(--green-main);
                           border-bottom:2px solid var(--green-main);margin-bottom:-2px;
                           transition:all .15s;">
                Description
            </button>
            <button onclick="switchTab('spec')" id="tab-spec" style="padding:14px 24px;font-size:14px;font-weight:600;border:none;
                           background:none;cursor:pointer;color:var(--text-muted);
                           border-bottom:2px solid transparent;margin-bottom:-2px;
                           transition:all .15s;">
                Specifications
            </button>
        </div>

        <!-- Description panel -->
        <div id="panel-desc" style="padding:24px;">
            <?php if (!empty($prod['Prod_Brand'])): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);
                            border:1px solid var(--border);border-radius:6px;padding:4px 12px;
                            font-size:12px;color:var(--green-dark);font-weight:700;margin-bottom:14px;">
                    <i class="bi bi-patch-check-fill"></i>
                    Brand: <?= htmlspecialchars($prod['Prod_Brand']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($prod['Prod_Desc'])): ?>
                <p style="font-size:14px;line-height:1.7;color:var(--text-dark);margin:0;">
                    <?= nl2br(htmlspecialchars($prod['Prod_Desc'])) ?>
                </p>
            <?php else: ?>
                <p style="font-size:14px;color:#aaa;font-style:italic;">
                    No description available for this product.
                </p>
            <?php endif; ?>
        </div>

        <!-- Specifications panel -->
        <div id="panel-spec" style="padding:24px;display:none;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tbody>
                    <tr style="border-bottom:1px solid #f0f4ea;">
                        <td style="padding:10px 0;color:var(--text-muted);width:200px;font-weight:600;">Product Name
                        </td>
                        <td style="padding:10px 0;color:var(--text-dark);"><?= htmlspecialchars($prod['Prod_Name']) ?>
                        </td>
                    </tr>
                    <?php if ($prod['Prod_Size']): ?>
                        <tr style="border-bottom:1px solid #f0f4ea;">
                            <td style="padding:10px 0;color:var(--text-muted);font-weight:600;">Size / Variant</td>
                            <td style="padding:10px 0;color:var(--text-dark);"><?= htmlspecialchars($prod['Prod_Size']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($prod['Prod_Brand'])): ?>
                        <tr style="border-bottom:1px solid #f0f4ea;">
                            <td style="padding:10px 0;color:var(--text-muted);font-weight:600;">Brand</td>
                            <td style="padding:10px 0;color:var(--text-dark);"><?= htmlspecialchars($prod['Prod_Brand']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr style="border-bottom:1px solid #f0f4ea;">
                        <td style="padding:10px 0;color:var(--text-muted);font-weight:600;">Category</td>
                        <td style="padding:10px 0;color:var(--text-dark);">
                            <?= htmlspecialchars($prod['Cat_Name'] ?? '—') ?>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f0f4ea;">
                        <td style="padding:10px 0;color:var(--text-muted);font-weight:600;">SKU</td>
                        <td style="padding:10px 0;color:var(--text-dark);">
                            <?= str_pad($prod['Prod_Id'], 6, '0', STR_PAD_LEFT) ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:var(--text-muted);font-weight:600;">Availability</td>
                        <td style="padding:10px 0;">
                            <?php if ($prod['Prod_Stock'] > 0): ?>
                                <span style="color:#2e7d32;font-weight:600;">In Stock</span>
                            <?php else: ?>
                                <span style="color:#c62828;font-weight:600;">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══ RELATED PRODUCTS ══ -->
    <?php if ($related && $related->num_rows > 0): ?>
        <div style="margin-bottom:40px;">
            <h5
                style="font-weight:700;color:var(--text-dark);margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px;">
                <i class="bi bi-grid me-2" style="color:var(--green-main);"></i>More from
                <span style="color:var(--green-main);"><?= htmlspecialchars($prod['Cat_Name'] ?? '') ?></span>
            </h5>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
                <?php while ($r = $related->fetch_assoc()):
                    $rHasDisc = $r['Prod_OldPrice'] && $r['Prod_OldPrice'] > $r['Prod_Price'];
                    $rDiscPct = $rHasDisc ? round((1 - $r['Prod_Price'] / $r['Prod_OldPrice']) * 100) : 0;
                    ?>
                    <a href="/LandersOnline/products.php?id=<?= $r['Prod_Id'] ?>" style="text-decoration:none;">
                        <div class="product-card" style="cursor:pointer;">

                            <img src="<?= htmlspecialchars($r['Prod_Image'] ?? 'assets/images/no-image.png') ?>"
                                alt="<?= htmlspecialchars($r['Prod_Name']) ?>">
                            <div class="card-body">
                                <div style="height:24px;padding:0px 0px 20px;">
                                    <?php if ($rHasDisc): ?>
                                        <span class="badge-discount">
                                            <?= $rDiscPct ?>% OFF
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-name"><?= htmlspecialchars($r['Prod_Name']) ?></div>
                                <div class="prod-sub"><?= htmlspecialchars($r['Prod_Size'] ?? '') ?></div>
                                <div style="color:#f5a623;font-size:11px;margin-bottom:6px;">
                                    &#9733;&#9733;&#9733;&#9733;&#9733; <span style="color:#aaa;">(0)</span>
                                </div>
                                <div>
                                    <span class="prod-price">₱<?= number_format($r['Prod_Price'], 2) ?></span>
                                    <?php if ($rHasDisc): ?>
                                        <span class="prod-old-price">₱<?= number_format($r['Prod_OldPrice'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /max-width container -->

<!-- ══ FOOTER ══ -->
<footer class="site-footer">
    <div style="max-width:1300px;margin:0 auto;padding:0 24px;">
        <div class="row">
            <div class="col-md-3">
                <h6>LandersOnline</h6>
                <p style="font-size:13px;color:#a0cc70;">Your trusted online supermarket delivering quality products to
                    your doorstep.</p>
            </div>
            <div class="col-md-2">
                <h6>Shop</h6>
                <a href="#">New Arrivals</a>
                <a href="#">Best Sellers</a>
                <a href="#">On Sale</a>
            </div>
            <div class="col-md-2">
                <h6>Help</h6>
                <a href="#">FAQs</a>
                <a href="#">Track Orders</a>
                <a href="#">Returns</a>
                <a href="#">Contact Us</a>
            </div>
            <div class="col-md-2">
                <h6>About</h6>
                <a href="#">About Landers</a>
                <a href="#">Membership</a>
                <a href="#">Delivery Areas</a>
            </div>
            <div class="col-md-3">
                <h6>Download the App</h6>
                <a href="#"
                    style="display:inline-block;background:#1a2e0a;color:#fff;padding:8px 16px;border-radius:6px;margin-bottom:8px;font-size:12px;">
                    <i class="bi bi-apple me-2"></i>App Store
                </a><br>
                <a href="#"
                    style="display:inline-block;background:#1a2e0a;color:#fff;padding:8px 16px;border-radius:6px;font-size:12px;">
                    <i class="bi bi-google-play me-2"></i>Google Play
                </a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            &copy; <?= date('Y') ?> LandersOnline. All rights reserved.
        </div>
    </div>
</footer>

<script>
    // ── Tab switcher ──
    function switchTab(tab) {
        const tabs = ['desc', 'spec'];
        tabs.forEach(t => {
            const btn = document.getElementById('tab-' + t);
            const panel = document.getElementById('panel-' + t);
            const active = (t === tab);
            btn.style.color = active ? 'var(--green-main)' : 'var(--text-muted)';
            btn.style.fontWeight = active ? '700' : '600';
            btn.style.borderBottom = active ? '2px solid var(--green-main)' : '2px solid transparent';
            panel.style.display = active ? 'block' : 'none';
        });
    }

    // ── Qty stepper ──
    function stepQty(dir) {
        const input = document.getElementById('qtyInput');
        const max = parseInt(input.max) || 99;
        let val = parseInt(input.value) || 1;
        val = Math.min(Math.max(val + dir, 1), max);
        input.value = val;
    }

    // Remove spin arrows from number input
    document.getElementById('qtyInput').addEventListener('wheel', e => e.preventDefault());
</script>

</body>

</html>
