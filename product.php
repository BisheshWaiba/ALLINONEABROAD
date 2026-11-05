<?php
require_once __DIR__ . '/config.php';

$conn = connectDb();
$id = (int)($_GET['id'] ?? 0);
$product = null;

if ($id > 0) {
    $stmt = $conn->prepare('SELECT id, name, category, price, original_price, stock, image, images, colors, country_of_origin, piece_type, description, badge, rating, reviews FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if (!$product) {
    http_response_code(404);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$baseUrl = $scheme . '://' . $host;
$canonicalUrl = $baseUrl . '/product.php?id=' . $id;

if ($product) {
    $ogTitle = $product['name'];
    $ogDescription = $product['description'] ?: ('Shop ' . $product['name'] . ' at ALL IN ONE ABROAD — quality luggage and kitchen essentials.');
    $ogImage = preg_match('#^https?://#i', $product['image']) ? $product['image'] : $baseUrl . '/' . ltrim($product['image'], '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ALL IN ONE ABROAD</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico?v=1"/>
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png?v=1"/>
  <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png?v=1"/>
  <link rel="apple-touch-icon" href="apple-touch-icon.png?v=1"/>
  <?php if ($product): ?>
  <meta property="og:type" content="website"/>
  <meta property="og:site_name" content="ALL IN ONE ABROAD"/>
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>"/>
  <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>"/>
  <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>"/>
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>"/>
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>"/>
  <?php endif; ?>
  <link rel="stylesheet" href="style.css?v=26"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"/>
</head>
<body>

<div class="announce-bar"><div class="announce-track"><span>🎉 Grand Opening — Up to 50% Off</span><span>🚚 Delivery at Rs. 100 within Kathmandu</span><span>💳 eSewa • Khalti • FonePay • COD</span><span>🎉 Grand Opening — Up to 50% Off</span><span>🚚 Delivery at Rs. 100 within Kathmandu</span><span>💳 eSewa • Khalti • FonePay • COD</span></div></div>

<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a href="index.html" class="logo"><img src="allinonelogo.png?v=1" alt="ALL IN ONE ABROAD" class="logo-img"/></a>
    <form class="search-form" onsubmit="return false;">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" placeholder="Search luggage, cookers, tiffins…" id="searchInput"/>
    </form>
    <nav class="main-nav">
      <a href="shop.html?cat=luggage" data-cat="luggage">Luggage</a>
      <a href="shop.html?cat=kitchen" data-cat="kitchen">Kitchen</a>
      <a href="shop.html?cat=appliances" data-cat="appliances">Home Appliances</a>
      <a href="shop.html?cat=grocery" data-cat="grocery">Grocery</a>
      <a href="shop.html" data-cat="all">All Products</a>
    </nav>
    <div class="header-actions">
      <a href="wishlist.html" class="icon-btn" title="Wishlist" aria-label="Wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><span class="cart-count wish-count" id="wishCount" style="display:none;">0</span></a>
      <button class="icon-btn cart-btn" onclick="toggleCart()" title="Cart" aria-label="Open cart"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><span class="cart-count" id="cartCount">0</span></button>
      <a href="login.html" class="btn-signin">Sign In</a>
      <button class="mobile-menu-btn" onclick="toggleMobileMenu()" id="mobileMenuBtn" aria-label="Menu" aria-expanded="false" aria-controls="mobileNav"><span></span><span></span><span></span></button>
    </div>
  </div>
  <div class="mobile-nav" id="mobileNav">
    <a href="shop.html?cat=luggage">Luggage</a><a href="shop.html?cat=kitchen">Kitchen</a>
    <a href="shop.html?cat=appliances">Home Appliances</a>
    <a href="shop.html?cat=grocery">Grocery</a>
    <a href="shop.html">All Products</a><a href="wishlist.html">Wishlist</a><a href="login.html">Sign In</a>
  </div>
</header>

<?php if (!$product): ?>

  <div style="max-width:600px;margin:80px auto;padding:0 24px;text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">📦</div>
    <h1 style="font-size:22px;font-weight:800;margin-bottom:10px;">Product not found</h1>
    <p style="color:var(--gray);font-size:14px;margin-bottom:24px;">This product may have been removed, or the link is incorrect.</p>
    <a href="shop.html" class="btn-orange">Back to Shop</a>
  </div>

<?php else: ?>

  <?php
    $price = (float)$product['price'];
    $orig = $product['original_price'] !== null ? (float)$product['original_price'] : null;
    $off = ($orig && $orig > $price) ? round((($orig - $price) / $orig) * 100) : 0;
    $stock = (int)$product['stock'];
    $outOfStock = $stock <= 0;
    $stars = (int)$product['rating'];
    $reviews = (int)$product['reviews'];
    $features = $product['description'] ? array_filter(array_map('trim', explode(',', $product['description']))) : [];
    $countryOfOrigin = trim((string)($product['country_of_origin'] ?? ''));
    $pieceType = $product['piece_type'] ?? '';
    $specs = [
      'Category'     => ucfirst($product['category']),
      'Price'        => 'Rs. ' . number_format($price, 2),
      'Availability' => $outOfStock ? 'Out of stock' : ($stock <= 5 ? "Only {$stock} left" : 'In stock'),
      'Rating'       => $stars > 0 ? "{$stars} / 5 ({$reviews} reviews)" : 'Not yet rated',
    ];
    if ($pieceType === 'set') {
      $specs['Type'] = 'Full Set';
    } elseif ($pieceType === 'single') {
      $specs['Type'] = 'Single Piece';
    }
    if ($countryOfOrigin !== '') {
      $specs['Country of Origin'] = $countryOfOrigin;
    }

    $extraImages = $product['images'] ? array_filter(array_map('trim', explode('|', $product['images']))) : [];
    $galleryImages = array_values(array_unique(array_merge([$product['image']], $extraImages)));
    $colors = $product['colors'] ? array_filter(array_map('trim', explode('|', $product['colors']))) : [];
  ?>

  <div class="pdp-breadcrumb">
    <a href="index.html">Home</a>
    <span>/</span>
    <a href="shop.html?cat=<?= urlencode($product['category']) ?>"><?= htmlspecialchars(ucfirst($product['category'])) ?></a>
    <span>/</span>
    <span class="pdp-breadcrumb-current"><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <div class="pdp-layout">
    <div class="pdp-left-col">
      <div class="pdp-gallery-wrap">
        <?php if (count($galleryImages) > 1): ?>
        <div class="pdp-thumbs">
          <?php foreach ($galleryImages as $i => $img): ?>
            <div class="pdp-thumb<?= $i === 0 ? ' active' : '' ?>" onclick="pdpSetImage(this)">
              <img src="<?= htmlspecialchars(imgUrl($img)) ?>" alt="" onerror="handleImgError(this)"/>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="pdp-gallery">
          <?php if ($off > 0): ?><span class="pdp-gallery-badge">-<?= (int)$off ?>% OFF</span><?php endif; ?>
          <img src="<?= htmlspecialchars(imgUrl($product['image'])) ?>" alt="<?= htmlspecialchars($product['name']) ?>" id="pdpMainImage" onerror="handleImgError(this)"/>
        </div>
      </div>
      <div class="pdp-tabs-wrap">
        <div class="pdp-tabs">
          <?php if ($features): ?><button type="button" class="pdp-tab-btn active" data-tab="features" onclick="pdpSwitchTab(this)">Key Features</button><?php endif; ?>
          <button type="button" class="pdp-tab-btn<?= $features ? '' : ' active' ?>" data-tab="specs" onclick="pdpSwitchTab(this)">Specifications</button>
        </div>
        <?php if ($features): ?>
        <div class="pdp-tab-panel active" data-panel="features">
          <ul class="detail-list">
            <?php foreach ($features as $feature): ?>
              <li><span class="detail-check">✓</span><?= htmlspecialchars($feature) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <div class="pdp-tab-panel<?= $features ? '' : ' active' ?>" data-panel="specs">
          <table class="spec-table">
            <?php foreach ($specs as $label => $value): ?>
              <tr><td><?= htmlspecialchars($label) ?></td><td><?= htmlspecialchars($value) ?></td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </div>
    <div class="pdp-info">
      <div class="pdp-meta-row">
        <div class="prod-cat" style="margin-bottom:0;"><?= htmlspecialchars(strtoupper($product['category'])) ?></div>
        <?php if ($pieceType === 'set'): ?>
          <span class="pdp-type-badge pdp-type-set">📦 Full Set</span>
        <?php elseif ($pieceType === 'single'): ?>
          <span class="pdp-type-badge pdp-type-single">🧳 Single Piece</span>
        <?php endif; ?>
        <?php if (!$outOfStock && $stock <= 5): ?>
          <span class="pdp-type-badge pdp-type-lowstock">🔥 Only <?= $stock ?> left</span>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
        <h1 class="pdp-title"><?= htmlspecialchars($product['name']) ?></h1>
        <button class="icon-btn pdp-wish-btn" title="Wishlist" data-wish-pdp="<?= (int)$product['id'] ?>" onclick="toggleWish(<?= (int)$product['id'] ?>)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>
      </div>
      <div class="prod-rating" style="justify-content:space-between;margin-bottom:14px;">
        <span>
          <span class="stars"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></span>
          <span class="review-ct"><?= $stars ?>/5 | <?= number_format($reviews) ?> Ratings and <?= number_format($reviews) ?> Reviews</span>
        </span>
        <?php if ($outOfStock): ?>
          <span class="pdp-stock-pill pdp-stock-out">● Out of Stock</span>
        <?php else: ?>
          <span class="pdp-stock-pill pdp-stock-in">● In Stock</span>
        <?php endif; ?>
      </div>

      <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;">
        <span class="pdp-price">Rs. <?= number_format($price, 2) ?></span>
        <?php if ($off > 0): ?><span class="pdp-discount-badge">-<?= (int)$off ?>%</span><?php endif; ?>
        <span style="font-size:12px;color:var(--lgray);">Tax Inc.</span>
      </div>
      <?php if ($off > 0): ?>
        <div class="pdp-orig">Rs. <?= number_format($orig, 2) ?> <span style="color:var(--orange);text-decoration:none;font-weight:700;">Save Rs. <?= number_format($orig - $price, 2) ?></span></div>
      <?php endif; ?>
      <?php if ($countryOfOrigin !== ''): ?>
        <div style="font-size:13px;color:var(--gray);margin:10px 0;">Country of Origin: <strong style="color:var(--dark);"><?= htmlspecialchars($countryOfOrigin) ?></strong></div>
      <?php endif; ?>

      <?php if ($colors): ?>
      <div class="pdp-swatch-group">
        <label class="pdp-swatch-label">Color: <span id="pdpSelectedColor"><?= htmlspecialchars($colors[array_key_first($colors)]) ?></span></label>
        <div class="pdp-swatches">
          <?php foreach ($colors as $i => $color): ?>
            <button type="button" class="color-swatch<?= $i === 0 ? ' active' : '' ?>" style="background:<?= htmlspecialchars(colorToHex($color)) ?>" title="<?= htmlspecialchars($color) ?>" data-color="<?= htmlspecialchars($color) ?>" onclick="pdpSelectColor(this)"></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!$outOfStock): ?>
      <div class="pdp-qty-row">
        <label class="pdp-swatch-label">Quantity:</label>
        <div class="qty-selector">
          <button type="button" class="qty-btn" onclick="pdpChangeQty(-1)">−</button>
          <input type="text" class="qty-input" id="pdpQty" value="1" readonly/>
          <button type="button" class="qty-btn" onclick="pdpChangeQty(1)">+</button>
        </div>
      </div>
      <?php endif; ?>

      <div class="pdp-btns">
        <?php if ($outOfStock): ?>
          <button class="btn-orange btn-full" disabled style="opacity:0.5;cursor:not-allowed;">OUT OF STOCK</button>
        <?php else: ?>
          <button class="btn-orange btn-full" id="pdpAddBtn">ADD TO CART</button>
        <?php endif; ?>
        <a href="shop.html?cat=<?= urlencode($product['category']) ?>" class="btn-outline btn-full">← Back to <?= htmlspecialchars(ucfirst($product['category'])) ?></a>
      </div>

      <div class="pdp-trust-row">
        <div class="pdp-trust-item"><span>🛡️</span><div><div class="pdp-trust-title">100% Genuine</div><div class="pdp-trust-sub">Quality guaranteed</div></div></div>
        <div class="pdp-trust-item"><span>↩️</span><div><div class="pdp-trust-title">Easy Returns</div><div class="pdp-trust-sub">7-day hassle-free</div></div></div>
      </div>

      <div class="pdp-shipping-box">
        <div style="font-weight:800;font-size:14px;margin-bottom:10px;">Shipping</div>
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--gray);margin-bottom:8px;">
          <span>🕐 Estimated Delivery: 3–5 days</span><span>Rs. 100</span>
        </div>
        <div class="cart-note" style="margin:0;">🚚 Delivery: Rs. 100 within Kathmandu</div>
      </div>

      <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
        <div style="font-size:12px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Share this product</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn-outline" onclick="shareFacebook()">📘 Facebook</button>
          <button class="btn-outline" id="shareNativeBtn" style="display:none;" onclick="shareNative()">📤 Share to Instagram &amp; more</button>
          <button class="btn-outline" onclick="copyProductLink()">🔗 Copy Link</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const btn = document.getElementById('pdpAddBtn');
      if (btn) {
        btn.addEventListener('click', () => {
          const qtyEl = document.getElementById('pdpQty');
          const qty = qtyEl ? parseInt(qtyEl.value, 10) || 1 : 1;
          const colorEl = document.getElementById('pdpSelectedColor');
          const product = <?= json_encode([
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => $price,
            'img' => $product['image'],
            'cat' => $product['category'],
          ], JSON_UNESCAPED_SLASHES) ?>;
          if (colorEl) product.name += ' (' + colorEl.textContent + ')';
          addToCart(product, qty);
        });
      }

      const nativeBtn = document.getElementById('shareNativeBtn');
      if (nativeBtn && navigator.share) {
        nativeBtn.style.display = 'inline-flex';
      }
    });

    function pdpSwitchTab(btn) {
      const tab = btn.dataset.tab;
      document.querySelectorAll('.pdp-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.pdp-tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.querySelector(`.pdp-tab-panel[data-panel="${tab}"]`).classList.add('active');
    }

    function pdpSelectColor(btn) {
      document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
      btn.classList.add('active');
      const label = document.getElementById('pdpSelectedColor');
      if (label) label.textContent = btn.dataset.color;
    }

    function pdpChangeQty(delta) {
      const input = document.getElementById('pdpQty');
      if (!input) return;
      const next = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
      input.value = next;
    }

    function pdpSetImage(thumb) {
      document.getElementById('pdpMainImage').src = thumb.querySelector('img').src;
      document.querySelectorAll('.pdp-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    }

    function shareFacebook() {
      const url = encodeURIComponent(window.location.href);
      window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, 'fbshare', 'width=600,height=500');
    }

    function shareNative() {
      if (!navigator.share) return;
      navigator.share({
        title: document.title,
        text: <?= json_encode($product['name']) ?>,
        url: window.location.href
      }).catch(() => {});
    }

    function copyProductLink() {
      const url = window.location.href;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
          showToast('🔗 Link copied! Paste it into an Instagram Story, DM, or bio.');
        }).catch(() => {
          window.prompt('Copy this link:', url);
        });
      } else {
        window.prompt('Copy this link:', url);
      }
    }
  </script>

<?php endif; ?>

<footer class="site-footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="footer-logo"><span class="footer-logo-top">ALL IN ONE</span><span class="footer-logo-bottom">ABROAD</span></div>
        <p class="footer-desc">Your trusted companion for student travel essentials.</p>
        <div class="footer-social">
          <a href="https://www.facebook.com/people/Bags-And-Luggage/61582261618135/" title="Facebook" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
          <a href="https://www.instagram.com/bagandluggage2025/" title="Instagram" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
          <a href="https://www.tiktok.com/@bags_luggage_ktm" title="TikTok" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.82c-.99-.86-1.62-2.1-1.7-3.5h-3.05v13.9c0 1.65-1.35 3-3 3s-3-1.35-3-3 1.35-3 3-3c.28 0 .55.04.8.11V9.94a6.14 6.14 0 0 0-.8-.05c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6V9.01a9.16 9.16 0 0 0 4.75 1.33V7.29a5.86 5.86 0 0 1-2.99-1.47z"/></svg></a>
        </div>
      </div>
      <div class="footer-col"><h4>SHOP</h4><ul><li><a href="shop.html?cat=luggage">Luggage</a></li><li><a href="shop.html?cat=kitchen">Kitchen</a></li></ul></div>
      <div class="footer-col"><h4>SUPPORT</h4><ul><li><a href="contact.html">Contact</a></li><li><a href="#">Shipping</a></li><li><a href="#">Returns</a></li><li><a href="#">FAQ</a></li></ul></div>
      <div class="footer-col"><h4>LEGAL</h4><ul><li><a href="#">Privacy</a></li><li><a href="#">Terms</a></li></ul></div>
      <div class="footer-col"><h4>CONTACT</h4><ul><li class="footer-line">+977 9809497026</li><li class="footer-line">luvkushgupta37@gmail.com</li><li class="footer-line">Jorpati Chamunda Gate (Opp. Prabhu Bank), Kathmandu</li></ul></div>
    </div>
    <div class="footer-bottom"><p class="footer-copy">© 2026 All In One Abroad. All rights reserved.</p><div class="payment-icons"><span class="pay-badge">eSewa</span><span class="pay-badge">Khalti</span><span class="pay-badge">FonePay</span><span class="pay-badge">COD</span><a href="orders.php" class="pay-badge admin-badge">Admin Login</a></div></div>
  </div>
</footer>

<div class="drawer-overlay" id="drawerOverlay" onclick="closeAllDrawers()"></div>
<aside class="cart-drawer" id="cartDrawer" role="dialog" aria-modal="true" aria-label="Shopping cart">
  <div class="drawer-head"><h3>Shopping Cart <span id="cartLabel" class="cart-label">0</span></h3><button class="drawer-close" onclick="toggleCart()" aria-label="Close cart">×</button></div>
  <div class="drawer-body" id="cartBody"><div class="empty-state" id="cartEmpty"><div class="empty-icon">🛒</div><p>Your cart is empty</p></div><div id="cartItemsList"></div></div>
  <div class="drawer-footer" id="cartFooter" style="display:none;"><div class="cart-total-row"><span>Subtotal</span><span id="cartSubtotal">Rs. 0</span></div><div class="cart-note">🚚 Delivery: Rs. 100 within Kathmandu</div><a href="checkout.html" class="btn-orange btn-full">Proceed to Checkout →</a><button class="btn-outline btn-full mt-10" onclick="toggleCart()">Continue Shopping</button></div>
</aside>
<div class="toast" id="toast"></div>
<button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<script src="main.js?v=17"></script>
</body>
</html>
