<?php
session_start();
require_once "config/db.php";
require_once "config/firebase_store.php";
$store = firebaseStore();

$title = "LandersOnline - Shop Online";
include "layout/layout.php";

$activeCatId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;

$categories = $store->categories(true);
$products = $store->productsWithCategory($activeCatId, true);

$activeCatName = '';
if ($activeCatId > 0) {
    $activeCatName = $store->categoryNameById($activeCatId);
}
?>

<div style="display:flex;max-width:1300px;margin:0 auto;padding:0 0 0 0;">

    <!-- ══ LEFT SIDEBAR ══ -->
    <div class="category-sidebar">
        <!-- "All Products" link -->
        <a href="/LandersOnline/index.php" class="cat-item <?= $activeCatId === 0 ? 'active' : '' ?>"
            style="<?= $activeCatId === 0 ? 'background:var(--green-bg);color:var(--green-dark);font-weight:700;' : '' ?>">
            All Products
            <span class="arrow"><i class="bi bi-chevron-right"></i></span>
        </a>

        <?php
        // Rewind categories result
        if ($categories)
            $categories->data_seek(0);
        if ($categories && $categories->num_rows > 0):
            while ($cat = $categories->fetch_assoc()):
                $isActive = ($activeCatId === (int) $cat['Cat_Id']);
                ?>
                <a href="/LandersOnline/index.php?cat=<?= $cat['Cat_Id'] ?>" class="cat-item <?= $isActive ? 'active' : '' ?>"
                    style="<?= $isActive ? 'background:var(--green-bg);color:var(--green-dark);font-weight:700;border-left:3px solid var(--green-main);' : '' ?>">
                    <?= htmlspecialchars($cat['Cat_Name']) ?>
                    <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                </a>
            <?php endwhile; endif; ?>
    </div>
    <!-- ══ MAIN CONTENT ══ -->
    <div style="flex:1;padding:20px 24px;overflow:hidden;">

        <!-- ══ CAROUSEL ══ -->
         
        <div style="position:relative;margin-bottom:22px;border-radius:12px;overflow:hidden;">
            <div id="promoCarousel" style="position:relative;width:100%;overflow:hidden;">

                <!-- SLIDES -->
                <div class="promo-slides">

                    <!-- Slide 1 — Free Delivery -->
                    <div class="promo-slide active" style="background:url('/LandersOnline/assets/images/1.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">
                    </div>

                    <!-- Slide 2 — Marketplace -->
                    <div class="promo-slide" style="background:url('/LandersOnline/assets/images/2.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">
                    </div>

                    <div class="promo-slide" style="background:url('/LandersOnline/assets/images/3.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">
                    </div>
                    <div class="promo-slide" style="background:url('/LandersOnline/assets/images/4.jpg');
                                height:300px;display:flex;align-items:center;padding:0 52px;
                                position:relative;overflow:hidden;">
                    </div>
                    <div class="promo-slide" style="background:url('/LandersOnline/assets/images/5.jpg');
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
                <button onclick="moveSlide(-1)" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                               background:rgba(0,0,0,.35);color:#fff;border:none;width:40px;height:40px;
                               border-radius:50%;font-size:18px;cursor:pointer;z-index:10;
                               display:flex;align-items:center;justify-content:center;
                               transition:background .2s;" onmouseover="this.style.background='rgba(0,0,0,.6)'"
                    onmouseout="this.style.background='rgba(0,0,0,.35)'">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button onclick="moveSlide(1)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                               background:rgba(0,0,0,.35);color:#fff;border:none;width:40px;height:40px;
                               border-radius:50%;font-size:18px;cursor:pointer;z-index:10;
                               display:flex;align-items:center;justify-content:center;
                               transition:background .2s;" onmouseover="this.style.background='rgba(0,0,0,.6)'"
                    onmouseout="this.style.background='rgba(0,0,0,.35)'">
                    <i class="bi bi-chevron-right"></i>
                </button>

                <!-- DOT indicators -->
                <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);
                            display:flex;gap:7px;z-index:10;">
                    <button class="carousel-dot active" onclick="goSlide(0)"></button>
                    <button class="carousel-dot" onclick="goSlide(1)"></button>
                    <button class="carousel-dot" onclick="goSlide(2)"></button>
                    <button class="carousel-dot" onclick="goSlide(3)"></button>
                    <button class="carousel-dot" onclick="goSlide(4)"></button>
                </div>
            </div>
        </div>

        <style>
            .promo-slides {
                display: flex;
                transition: transform .5s ease;
            }

            .promo-slide {
                min-width: 100%;
                flex-shrink: 0;
            }

            .carousel-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .45);
                border: none;
                cursor: pointer;
                padding: 0;
                transition: background .2s, transform .2s;
            }

            .carousel-dot.active {
                background: #fff;
                transform: scale(1.3);
            }
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
                        <a href="/LandersOnline/public/products.php?id=<?= $prod['Prod_Id'] ?>" style="text-decoration:none;color:inherit;">
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
                        </a>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <!-- Demo cards when DB is empty -->
                
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
