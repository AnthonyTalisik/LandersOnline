<?php
session_start();
require_once "config/db.php";

$title = "LandersOnline - Shop Online";
include "layout/layout.php";

// Fetch categories
$categories = $conn->query("SELECT * FROM Categories WHERE Cat_Status='active' ORDER BY Cat_Name ASC");

// Fetch featured products (latest 8)
$products = $conn->query("
    SELECT p.*, c.Cat_Name
    FROM Products p
    LEFT JOIN Categories c ON p.Prod_CatId = c.Cat_Id
    WHERE p.Prod_Status = 'active'
    ORDER BY p.Prod_Id DESC
    LIMIT 8
");
?>

<div style="display:flex;max-width:1300px;margin:0 auto;padding:0 0 0 0;">

    <!-- ══ LEFT SIDEBAR ══ -->
    <div class="category-sidebar">
        <?php if ($categories && $categories->num_rows > 0): ?>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <a href="/landersonline/customer/shop.php?cat=<?= $cat['Cat_Id'] ?>" class="cat-item">
                    <?= htmlspecialchars($cat['Cat_Name']) ?>
                    <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Demo categories if DB is empty -->
            <?php foreach (['NEW! Marketplace', 'Health & Beauty', 'Food Cupboard', 'Home & Outdoor', 'Beer, Wine & Spirits', 'Beverages', 'Household & Laundry', 'Pet Care', 'Chocolates, Candies & Sweets', 'Baby, Kids & Toys', 'Fashion', 'Electronics', 'Fruits & Vegetables', 'Dairy & Chilled', 'Bakery', 'Frozen'] as $c): ?>
                <a href="#" class="cat-item"><?= $c ?> <span class="arrow"><i class="bi bi-chevron-right"></i></span></a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ══ MAIN CONTENT ══ -->
    <div style="flex:1;padding:20px 24px;overflow:hidden;">

        <!-- ══ CAROUSEL ══ -->
        <div style="position:relative;margin-bottom:22px;border-radius:12px;overflow:hidden;">
            <div id="promoCarousel" style="position:relative;width:100%;overflow:hidden;">

                <!-- SLIDES -->
                <div class="promo-slides">

                    <!-- Slide 1 — Free Delivery -->
                    <div class="promo-slide active"
                        style="background:url('/LandersOnline/assets/images/1.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">    
                    </div>

                    <!-- Slide 2 — Marketplace -->
                    <div class="promo-slide"
                        style="background:url('/LandersOnline/assets/images/2.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">    
                    </div>

                    <div class="promo-slide"
                        style="background:url('/LandersOnline/assets/images/3.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">    
                    </div>
                    <div class="promo-slide"
                        style="background:url('/LandersOnline/assets/images/4.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">    
                    </div>
                    <div class="promo-slide"
                        style="background:url('/LandersOnline/assets/images/5.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">    
                    </div>

                    <!--
                    ══════════════════════════════════════════════════════
                    HOW TO ADD YOUR OWN BANNER IMAGES LATER:
                    Replace the gradient background with:
                      style="background:url('/LandersOnline/assets/images/banner1.jpg')
                             center/cover no-repeat; height:260px; ..."
                    Just upload banner photos to assets/images/ and swap in the filename.
                    ══════════════════════════════════════════════════════
                    -->

                </div><!-- /promo-slides -->

                <!-- PREV / NEXT arrows -->
                <button onclick="moveSlide(-1)"
                        style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                               background:rgba(0,0,0,.35);color:#fff;border:none;width:40px;height:40px;
                               border-radius:50%;font-size:18px;cursor:pointer;z-index:10;
                               display:flex;align-items:center;justify-content:center;
                               transition:background .2s;"
                        onmouseover="this.style.background='rgba(0,0,0,.6)'"
                        onmouseout="this.style.background='rgba(0,0,0,.35)'">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button onclick="moveSlide(1)"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                               background:rgba(0,0,0,.35);color:#fff;border:none;width:40px;height:40px;
                               border-radius:50%;font-size:18px;cursor:pointer;z-index:10;
                               display:flex;align-items:center;justify-content:center;
                               transition:background .2s;"
                        onmouseover="this.style.background='rgba(0,0,0,.6)'"
                        onmouseout="this.style.background='rgba(0,0,0,.35)'">
                    <i class="bi bi-chevron-right"></i>
                </button>

                <!-- DOT indicators -->
                <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);
                            display:flex;gap:7px;z-index:10;">
                    <button class="carousel-dot active" onclick="goSlide(0)"></button>
                    <button class="carousel-dot"        onclick="goSlide(1)"></button>
                    <button class="carousel-dot"        onclick="goSlide(2)"></button>
                    <button class="carousel-dot"        onclick="goSlide(3)"></button>
                    <button class="carousel-dot"        onclick="goSlide(4)"></button>
                    <button class="carousel-dot"        onclick="goSlide(5)"></button>
                </div>
            </div>
        </div>

        <style>
            .promo-slides { display:flex; transition:transform .5s ease; }
            .promo-slide  { min-width:100%; flex-shrink:0; }
            .carousel-dot {
                width:10px; height:10px; border-radius:50%;
                background:rgba(255,255,255,.45); border:none; cursor:pointer;
                padding:0; transition:background .2s, transform .2s;
            }
            .carousel-dot.active { background:#fff; transform:scale(1.3); }
        </style>

        <script>
            let currentSlide = 0;
            const totalSlides = 5;
            let autoTimer;

            function updateCarousel() {
                document.querySelector('.promo-slides').style.transform =
                    'translateX(-' + (currentSlide * 100) + '%)';
                document.querySelectorAll('.carousel-dot').forEach((d, i) =>
                    d.classList.toggle('active', i === currentSlide));
            }

            function moveSlide(dir) {
                currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
                updateCarousel();
                resetTimer();
            }

            function goSlide(idx) {
                currentSlide = idx;
                updateCarousel();
                resetTimer();
            }

            function resetTimer() {
                clearInterval(autoTimer);
                autoTimer = setInterval(() => moveSlide(1), 5000);
            }

            // Auto-slide every 5 seconds
            autoTimer = setInterval(() => moveSlide(1), 5000);
        </script>

        <!-- Products Section -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h5 style="margin:0;font-weight:700;color:var(--text-dark);">Featured Products</h5>
            <a href="/landersonline/customer/shop.php"
                style="color:var(--green-main);font-size:13px;font-weight:600;text-decoration:none;">
                See All <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">

            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($prod = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div style="height:24px; padding:8px 10px 0;">
                            <?php if ($prod['Prod_OldPrice'] && $prod['Prod_OldPrice'] > $prod['Prod_Price']): ?>
                                <?php $disc = round((1 - $prod['Prod_Price'] / $prod['Prod_OldPrice']) * 100); ?>
                            <?php endif; ?>
                        </div>

                        <img src="<?= htmlspecialchars($prod['Prod_Image'] ?? 'assets/images/no-image.png') ?>"
                            alt="<?= htmlspecialchars($prod['Prod_Name']) ?>">

                        <div class="card-body">
                            <div class="prod-name"><?= htmlspecialchars($prod['Prod_Name']) ?></div>
                            <div class="prod-sub"><?= htmlspecialchars($prod['Prod_Size'] ?? '') ?></div>

                            <!-- Stars -->
                            <div style="color:#f5a623;font-size:11px;margin-bottom:6px;">
                                &#9733;&#9733;&#9733;&#9733;&#9733;
                                <span style="color:#aaa;">(0)</span>
                            </div>

                            <div style="margin-bottom:10px;">
                                <span class="prod-price">₱<?= number_format($prod['Prod_Price'], 2) ?></span>
                                <?php if ($prod['Prod_OldPrice']): ?>
                                    <span class="prod-old-price">₱<?= number_format($prod['Prod_OldPrice'], 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="/landersonline/customer/cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="prod_id" value="<?= $prod['Prod_Id'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn-add-cart">ADD TO CART</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <!-- Demo cards when DB is empty -->
                <?php
                $demos = [
                    ['Nutella Ice Cream Pint 470mL', '470mL', '520.95', null],
                    ['Siviero Maria Blueberry Cheesecake Gelato 1L', '1L/500g', '229.95', '312.95', 27],
                    ['Haagen-Dazs Belgian Chocolate 460mL', '460mL', '352.95', '468.95', 25],
                    ['San Miguel Pale Pilsen 330mL', '330mL', '52.00', null],
                    ['Kirkland Signature Coffee 1.13kg', '1.13kg', '899.00', '1100.00', 18],
                    ['Tide Detergent Powder 3.8kg', '3.8kg', '349.75', null],
                    ['Pedigree Dog Food 3kg', '3kg', '445.00', '520.00', 14],
                    ['Energizer AA Batteries 24pk', '24pk', '399.00', null],
                ];
                foreach ($demos as $i => $d):
                    $disc = isset($d[4]) ? $d[4] : null;
                    ?>
                    <div class="product-card">

                        <div style="height:160px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-box-seam" style="font-size:48px;color:#ccc;"></i>
                        </div>
                        <div style="height:24px; padding:8px 10px 0;">
                            <?php if ($disc): ?>
                                <span class="badge-discount"><?= $disc ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="prod-name"><?= $d[0] ?></div>
                            <div class="prod-sub"><?= $d[1] ?></div>
                            <div style="color:#f5a623;font-size:11px;margin-bottom:6px;">
                                &#9733;&#9733;&#9733;&#9733;&#9733; <span style="color:#aaa;">(0)</span>
                            </div>
                            <div style="margin-bottom:10px;">
                                <span class="prod-price">₱<?= $d[2] ?></span>
                                <?php if ($d[3]): ?>
                                    <span class="prod-old-price">₱<?= $d[3] ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn-add-cart" onclick="alert('Please log in to add to cart.')">ADD TO CART</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

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
                <a href="#">Marketplace</a>
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
                <a href="#">Careers</a>
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

</body>

</html>