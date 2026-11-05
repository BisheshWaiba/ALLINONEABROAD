<?php
require_once __DIR__ . '/config.php';

session_start();

function renderLoginForm(?string $error = null): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Admin Login — ALL IN ONE ABROAD</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f3f4f6; }
        .card { background: #fff; padding: 40px 36px; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,0.1); width: 100%; max-width: 340px; }
        .logo { text-align: center; margin-bottom: 24px; line-height: 1; }
        .logo-top { display: block; font-size: 10px; font-weight: 800; letter-spacing: 0.18em; color: #6b7280; text-transform: uppercase; }
        .logo-bottom { display: block; font-size: 22px; font-weight: 900; letter-spacing: 0.04em; color: #f97316; margin-top: 4px; }
        h1 { font-size: 15px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 22px; }
        input { width: 100%; padding: 11px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px; font-size: 14px; font-family: inherit; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #f97316; }
        button { width: 100%; padding: 11px; border: none; border-radius: 8px; background: #f97316; color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; transition: background 0.15s; }
        button:hover { background: #ea6c0a; }
        .error { background: #fef2f2; color: #dc2626; font-size: 12.5px; font-weight: 600; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
      </style>
    </head>
    <body>
      <form class="card" method="post">
        <div class="logo"><span class="logo-top">ALL IN ONE</span><span class="logo-bottom">ABROAD</span></div>
        <h1>Orders Dashboard Login</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <input type="password" name="password" placeholder="Admin password" autofocus required/>
        <button type="submit">Sign In</button>
      </form>
    </body>
    </html>
    <?php
}

if (!$adminPassword) {
    http_response_code(503);
    echo '<h1>Orders dashboard not configured</h1><p>Set ADMIN_PASSWORD in config.local.php to enable access.</p>';
    exit;
}

if (isset($_POST['password'])) {
    if (hash_equals($adminPassword, (string)$_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['orders_admin'] = true;
        header('Location: orders.php');
        exit;
    } else {
        renderLoginForm('Incorrect password.');
        exit;
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    renderLoginForm();
    exit;
}

if (empty($_SESSION['orders_admin'])) {
    renderLoginForm();
    exit;
}

try {
    $conn = connectDb();
    $result = $conn->query('SELECT id, customer_name, email, phone, shipping_address, city, state, pincode, payment_method, items, total_amount, created_at FROM orders ORDER BY id DESC');
} catch (Throwable $e) {
    echo '<h1>Database unavailable</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$totalOrders = count($orders);
$totalRevenue = array_sum(array_column($orders, 'total_amount'));
$avgOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$today = date('Y-m-d');
$todaysOrders = count(array_filter($orders, fn($o) => substr((string)$o['created_at'], 0, 10) === $today));

$paymentBadgeColors = [
    'eSewa' => '#16a34a',
    'Khalti' => '#7c3aed',
    'FonePay' => '#2563eb',
    'Cash on Delivery (COD)' => '#6b7280',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Orders Dashboard — ALL IN ONE ABROAD</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111827; background: #f3f4f6; }

    .topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 16px 28px; display: flex; align-items: center; gap: 16px; }
    .logo { line-height: 1; }
    .logo-top { display: block; font-size: 9px; font-weight: 800; letter-spacing: 0.18em; color: #6b7280; text-transform: uppercase; }
    .logo-bottom { display: block; font-size: 17px; font-weight: 900; letter-spacing: 0.04em; color: #f97316; margin-top: 2px; }
    .topbar-title { font-size: 14px; font-weight: 600; color: #6b7280; padding-left: 16px; border-left: 1px solid #e5e7eb; }
    .nav-link { font-size: 13px; font-weight: 600; color: #6b7280; padding: 8px 14px; border-radius: 8px; border: 1.5px solid #e5e7eb; transition: background 0.15s; text-decoration: none; }
    .nav-link:hover { background: #f3f4f6; }
    .nav-link.active { background: #111827; color: #fff; border-color: #111827; }
    .logout-link { margin-left: auto; font-size: 13px; font-weight: 600; color: #6b7280; padding: 8px 14px; border-radius: 8px; border: 1.5px solid #e5e7eb; transition: background 0.15s; text-decoration: none; }
    .logout-link:hover { background: #f3f4f6; }

    .topbar-links { display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0; }
    .menu-btn { display: none; flex: none; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 8px; border: 1.5px solid #e5e7eb; background: #fff; font-size: 17px; color: #374151; cursor: pointer; }

    .sidebar-backdrop { position: fixed; inset: 0; background: rgba(17,24,39,0.45); opacity: 0; pointer-events: none; transition: opacity 0.2s ease; z-index: 40; }
    .sidebar-backdrop.open { opacity: 1; pointer-events: auto; }
    .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 260px; max-width: 82vw; background: #fff; z-index: 50; transform: translateX(-100%); transition: transform 0.25s ease; overflow-y: auto; box-shadow: 6px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; padding-bottom: 12px; }
    .sidebar.open { transform: translateX(0); }
    .sidebar-head { display: flex; align-items: center; justify-content: space-between; padding: 18px; border-bottom: 1px solid #e5e7eb; margin-bottom: 6px; }
    .sidebar-close { background: none; border: none; font-size: 22px; line-height: 1; color: #6b7280; cursor: pointer; padding: 4px; }
    .sidebar-section-label { font-size: 10.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #9ca3af; padding: 14px 18px 6px; }
    .sidebar-link { display: block; padding: 11px 18px; font-size: 14px; font-weight: 600; color: #374151; text-decoration: none; }
    .sidebar-link:hover { background: #f3f4f6; }
    .sidebar-link.active { background: #111827; color: #fff; }
    .sidebar-link.logout { margin-top: auto; color: #dc2626; border-top: 1px solid #f3f4f6; padding-top: 14px; }

    .wrap { max-width: 1200px; margin: 0 auto; padding: 28px; }

    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 18px 20px; }
    .stat-label { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #9ca3af; margin-bottom: 8px; }
    .stat-value { font-size: 24px; font-weight: 800; color: #111827; }
    .stat-value.orange { color: #f97316; }

    .panel { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .panel-head { padding: 18px 22px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .panel-head h2 { font-size: 16px; font-weight: 800; }
    .search-box { position: relative; }
    .search-box input { padding: 9px 14px 9px 34px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; outline: none; width: 240px; transition: border-color 0.2s; }
    .search-box input:focus { border-color: #f97316; }
    .search-box svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #9ca3af; pointer-events: none; }

    .table-scroll { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 900px; }
    th { background: #f9fafb; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #6b7280; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: top; }
    tbody tr:hover { background: #fafafa; }
    tbody tr:last-child td { border-bottom: none; }
    .order-id { font-weight: 700; color: #6b7280; }
    .cust-name { font-weight: 700; color: #111827; }
    .cust-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .addr { color: #6b7280; max-width: 220px; }
    .pay-badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 100px; color: #fff; white-space: nowrap; }
    .amount { font-weight: 800; color: #111827; }
    .created { color: #9ca3af; font-size: 12px; white-space: nowrap; }

    .empty { text-align: center; padding: 64px 24px; color: #6b7280; }
    .empty-icon { font-size: 40px; margin-bottom: 12px; }

    @media (max-width: 768px) {
      .stats { grid-template-columns: repeat(2, 1fr); }
      .wrap { padding: 16px; }
      .search-box input { width: 160px; }
      .menu-btn { display: flex; }
      .topbar-links { display: none; }
      .topbar { padding: 14px 16px; gap: 12px; }
    }
  </style>
</head>
<body>

  <div class="topbar">
    <button type="button" class="menu-btn" id="navMenuBtn" aria-label="Open menu">☰</button>
    <div class="logo"><span class="logo-top">ALL IN ONE</span><span class="logo-bottom">ABROAD</span></div>
    <div class="topbar-title">Admin</div>
    <nav class="topbar-links">
      <a href="orders.php" class="nav-link active">Orders</a>
      <a href="products.php" class="nav-link">Products</a>
      <a href="banner.php" class="nav-link">Banner</a>
      <a href="<?= htmlspecialchars($financeUrl) ?>" class="nav-link">Finance</a>
      <a href="?logout=1" class="logout-link">Log out</a>
    </nav>
  </div>

  <div class="sidebar-backdrop" id="navBackdrop"></div>
  <aside class="sidebar" id="navSidebar">
    <div class="sidebar-head">
      <div class="logo"><span class="logo-top">ALL IN ONE</span><span class="logo-bottom">ABROAD</span></div>
      <button type="button" class="sidebar-close" id="navSidebarClose" aria-label="Close menu">×</button>
    </div>
    <div class="sidebar-section-label">Admin</div>
    <a href="orders.php" class="sidebar-link active">Orders</a>
    <a href="products.php" class="sidebar-link">Products</a>
    <a href="banner.php" class="sidebar-link">Banner</a>
    <a href="<?= htmlspecialchars($financeUrl) ?>" class="sidebar-link">Finance</a>
    <a href="?logout=1" class="sidebar-link logout">Log out</a>
  </aside>
  <script>
    (function () {
      var btn = document.getElementById('navMenuBtn');
      var sidebar = document.getElementById('navSidebar');
      var backdrop = document.getElementById('navBackdrop');
      var closeBtn = document.getElementById('navSidebarClose');
      if (!btn || !sidebar || !backdrop) return;
      function openNav() { sidebar.classList.add('open'); backdrop.classList.add('open'); }
      function closeNav() { sidebar.classList.remove('open'); backdrop.classList.remove('open'); }
      btn.addEventListener('click', openNav);
      backdrop.addEventListener('click', closeNav);
      if (closeBtn) closeBtn.addEventListener('click', closeNav);
    })();
  </script>

  <div class="wrap">
    <div class="stats">
      <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $totalOrders ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Today's Orders</div>
        <div class="stat-value"><?= $todaysOrders ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value orange">Rs. <?= number_format($totalRevenue, 0) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Avg. Order Value</div>
        <div class="stat-value">Rs. <?= number_format($avgOrder, 0) ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>All Orders</h2>
        <?php if ($totalOrders > 0): ?>
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" id="orderSearch" placeholder="Search name, email, phone…" oninput="filterOrders(this.value)"/>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($totalOrders === 0): ?>
        <div class="empty">
          <div class="empty-icon">📦</div>
          <p>No orders yet. Once a customer places an order, it will appear here.</p>
        </div>
      <?php else: ?>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Shipping Address</th>
              <th>Payment</th>
              <th>Total</th>
              <th>Placed</th>
            </tr>
          </thead>
          <tbody id="ordersBody">
            <?php foreach ($orders as $row): ?>
              <?php $payColor = $paymentBadgeColors[$row['payment_method']] ?? '#6b7280'; ?>
              <tr data-search="<?= htmlspecialchars(strtolower($row['customer_name'] . ' ' . $row['email'] . ' ' . $row['phone'])) ?>">
                <td class="order-id">#<?= (int)$row['id'] ?></td>
                <td>
                  <div class="cust-name"><?= htmlspecialchars($row['customer_name']) ?></div>
                  <div class="cust-sub"><?= htmlspecialchars($row['email']) ?></div>
                  <div class="cust-sub"><?= htmlspecialchars($row['phone']) ?></div>
                </td>
                <td class="addr"><?= htmlspecialchars($row['shipping_address'] . ', ' . $row['city'] . ', ' . $row['state'] . ' - ' . $row['pincode']) ?></td>
                <td><span class="pay-badge" style="background:<?= $payColor ?>"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                <td class="amount">Rs. <?= number_format((float)$row['total_amount'], 2) ?></td>
                <td class="created"><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function filterOrders(query) {
      const q = query.trim().toLowerCase();
      document.querySelectorAll('#ordersBody tr').forEach(function (tr) {
        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
      });
    }
  </script>
</body>
</html>
