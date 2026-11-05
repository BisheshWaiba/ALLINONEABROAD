<?php
require_once __DIR__ . '/config.php';

session_start();

if (empty($_SESSION['orders_admin'])) {
    header('Location: orders.php');
    exit;
}

$conn = connectDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['formAction'] ?? '';

    if ($formAction === 'add') {
        $message = trim((string)($_POST['message'] ?? ''));
        if ($message !== '') {
            $maxRow = $conn->query('SELECT MAX(sort_order) AS m FROM banner_messages')->fetch_assoc();
            $nextOrder = ((int)($maxRow['m'] ?? 0)) + 1;
            $stmt = $conn->prepare('INSERT INTO banner_messages (message, sort_order, active) VALUES (?, ?, 1)');
            $stmt->bind_param('si', $message, $nextOrder);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($formAction === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;
        if ($id > 0 && $message !== '') {
            $stmt = $conn->prepare('UPDATE banner_messages SET message = ?, active = ? WHERE id = ?');
            $stmt->bind_param('sii', $message, $active, $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($formAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM banner_messages WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($formAction === 'move') {
        $id = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        $messages = [];
        $result = $conn->query('SELECT id, sort_order FROM banner_messages ORDER BY sort_order ASC, id ASC');
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $index = null;
        foreach ($messages as $i => $row) {
            if ((int)$row['id'] === $id) {
                $index = $i;
                break;
            }
        }
        $swapWith = null;
        if ($index !== null) {
            if ($direction === 'up' && $index > 0) {
                $swapWith = $index - 1;
            } elseif ($direction === 'down' && $index < count($messages) - 1) {
                $swapWith = $index + 1;
            }
        }
        if ($swapWith !== null) {
            $a = $messages[$index];
            $b = $messages[$swapWith];
            $stmt = $conn->prepare('UPDATE banner_messages SET sort_order = ? WHERE id = ?');
            $stmt->bind_param('ii', $b['sort_order'], $a['id']);
            $stmt->execute();
            $stmt->bind_param('ii', $a['sort_order'], $b['id']);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($formAction === 'popup_save') {
        $linkUrl = trim((string)($_POST['link_url'] ?? ''));
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $allowedExt = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
        $maxBytes = 5 * 1024 * 1024;
        $popupError = null;
        $newImage = null;

        if (!empty($_FILES['popupImage']) && $_FILES['popupImage']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['popupImage']['error'] !== UPLOAD_ERR_OK) {
                $popupError = 'Image upload failed. Please try again.';
            } elseif ($_FILES['popupImage']['size'] > $maxBytes) {
                $popupError = 'Image is too large — please keep it under 5MB.';
            } else {
                $ext = strtolower((string)pathinfo($_FILES['popupImage']['name'], PATHINFO_EXTENSION));
                $imageInfo = @getimagesize($_FILES['popupImage']['tmp_name']);
                if (!isset($allowedExt[$ext]) || $imageInfo === false || $imageInfo['mime'] !== $allowedExt[$ext]) {
                    $popupError = 'Image must be a real JPG, PNG, WEBP, or GIF file.';
                } else {
                    $filename = 'popup-banner-' . substr(uniqid(), -8) . '.' . $ext;
                    $destPath = __DIR__ . '/images/products/' . $filename;
                    if (!move_uploaded_file($_FILES['popupImage']['tmp_name'], $destPath)) {
                        $popupError = 'Could not save the uploaded image.';
                    } else {
                        $newImage = 'images/products/' . $filename;
                    }
                }
            }
        }

        if ($popupError === null) {
            if ($newImage !== null) {
                $stmt = $conn->prepare('UPDATE popup_banner SET image = ?, link_url = ?, enabled = ? WHERE id = 1');
                $stmt->bind_param('ssi', $newImage, $linkUrl, $enabled);
            } else {
                $stmt = $conn->prepare('UPDATE popup_banner SET link_url = ?, enabled = ? WHERE id = 1');
                $stmt->bind_param('si', $linkUrl, $enabled);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            $_SESSION['popup_error'] = $popupError;
        }
    }

    header('Location: banner.php');
    exit;
}

$popupError = $_SESSION['popup_error'] ?? null;
unset($_SESSION['popup_error']);
$popup = $conn->query('SELECT image, link_url, enabled FROM popup_banner WHERE id = 1')->fetch_assoc();

$messages = [];
$result = $conn->query('SELECT id, message, sort_order, active FROM banner_messages ORDER BY sort_order ASC, id ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Banner — ALL IN ONE ABROAD</title>
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
    .logout-link { margin-left: auto; }

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

    .wrap { max-width: 900px; margin: 0 auto; padding: 28px; }

    .panel { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
    .panel-head { padding: 18px 22px; border-bottom: 1px solid #e5e7eb; }
    .panel-head h2 { font-size: 16px; font-weight: 800; }
    .panel-body { padding: 22px; }

    .btn { display: inline-flex; align-items: center; gap: 6px; background: #f97316; color: #fff; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; transition: background 0.15s; }
    .btn:hover { background: #ea6c0a; }
    .btn-outline { background: transparent; color: #111827; border: 1.5px solid #e5e7eb; }
    .btn-outline:hover { background: #f3f4f6; }
    .btn-danger { background: transparent; color: #dc2626; border: 1.5px solid #fecaca; }
    .btn-danger:hover { background: #fef2f2; }
    .btn-icon { padding: 6px 10px; font-size: 12px; }

    .add-row { display: flex; gap: 10px; }
    .add-row input[type="text"] { flex: 1; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; }
    .add-row input[type="text"]:focus { border-color: #f97316; }

    .msg-row { display: flex; align-items: center; gap: 10px; padding: 14px 22px; border-bottom: 1px solid #f3f4f6; }
    .msg-row:last-child { border-bottom: none; }
    .msg-row.inactive { opacity: 0.5; }
    .msg-text { flex: 1; }
    .msg-text input { width: 100%; padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 7px; font-size: 13px; font-family: inherit; outline: none; }
    .msg-text input:focus { border-color: #f97316; }
    .msg-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .msg-actions form { display: inline; }
    .active-label { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #6b7280; white-space: nowrap; }

    .help-text { font-size: 12.5px; color: #6b7280; margin-top: 14px; line-height: 1.6; }
    .empty { text-align: center; padding: 40px 24px; color: #6b7280; font-size: 13px; }

    .form-group { margin-bottom: 14px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #111827; display: block; margin-bottom: 6px; }
    .form-group input[type="text"], .form-group input[type="url"], .form-group input[type="file"] { width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; }
    .form-group input:focus { border-color: #f97316; }
    .popup-preview { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; padding: 12px; background: #f9fafb; border-radius: 10px; }
    .popup-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; background: #e5e7eb; }
    .popup-preview .status { font-size: 12px; color: #6b7280; }
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
    .alert-error { background: #fef2f2; color: #dc2626; }

    @media (max-width: 640px) {
      .wrap { padding: 16px; }
      .msg-row { flex-wrap: wrap; }
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
      <a href="orders.php" class="nav-link">Orders</a>
      <a href="products.php" class="nav-link">Products</a>
      <a href="banner.php" class="nav-link active">Banner</a>
      <a href="<?= htmlspecialchars($financeUrl) ?>" class="nav-link">Finance</a>
      <a href="orders.php?logout=1" class="nav-link logout-link">Log out</a>
    </nav>
  </div>

  <div class="sidebar-backdrop" id="navBackdrop"></div>
  <aside class="sidebar" id="navSidebar">
    <div class="sidebar-head">
      <div class="logo"><span class="logo-top">ALL IN ONE</span><span class="logo-bottom">ABROAD</span></div>
      <button type="button" class="sidebar-close" id="navSidebarClose" aria-label="Close menu">×</button>
    </div>
    <div class="sidebar-section-label">Admin</div>
    <a href="orders.php" class="sidebar-link">Orders</a>
    <a href="products.php" class="sidebar-link">Products</a>
    <a href="banner.php" class="sidebar-link active">Banner</a>
    <a href="<?= htmlspecialchars($financeUrl) ?>" class="sidebar-link">Finance</a>
    <a href="orders.php?logout=1" class="sidebar-link logout">Log out</a>
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

    <div class="panel">
      <div class="panel-head">
        <h2>Popup Banner Image</h2>
      </div>
      <div class="panel-body">
        <?php if ($popupError): ?>
          <div class="alert alert-error"><?= htmlspecialchars($popupError) ?></div>
        <?php endif; ?>

        <?php if ($popup && $popup['image']): ?>
          <div class="popup-preview">
            <img src="<?= htmlspecialchars($popup['image']) ?>" alt="Current popup banner"/>
            <div class="status">
              Current image is <strong><?= $popup['enabled'] ? 'ON — showing to visitors' : 'OFF — hidden from visitors' ?></strong><?= $popup['link_url'] ? '<br/>Links to: ' . htmlspecialchars($popup['link_url']) : '' ?>
            </div>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="formAction" value="popup_save"/>
          <div class="form-group">
            <label>Banner Image <?= ($popup && $popup['image']) ? '(optional — leave blank to keep current image)' : '(JPG, PNG, WEBP, or GIF — max 5MB)' ?></label>
            <input type="file" name="popupImage" accept=".jpg,.jpeg,.png,.webp,.gif" <?= ($popup && $popup['image']) ? '' : 'required' ?>/>
          </div>
          <div class="form-group">
            <label>Link when clicked (optional)</label>
            <input type="url" name="link_url" placeholder="e.g. shop.html?cat=luggage" value="<?= htmlspecialchars($popup['link_url'] ?? '') ?>"/>
          </div>
          <label class="active-label" style="margin-bottom:16px;"><input type="checkbox" name="enabled" <?= ($popup && $popup['enabled']) ? 'checked' : '' ?>/> Show this popup to visitors when the site loads</label>
          <br/>
          <button type="submit" class="btn">Save Popup Banner</button>
        </form>

        <div class="help-text">
          Shows once per visit as a closeable overlay when someone loads the site. Uncheck "Show this popup" to turn it off without losing the image — you can turn it back on later without re-uploading.
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Top Banner Messages</h2>
      </div>
      <div class="panel-body">
        <form method="post" class="add-row">
          <input type="hidden" name="formAction" value="add"/>
          <input type="text" name="message" maxlength="200" placeholder="e.g. 🎉 Diwali Sale — 30% Off Everything" required/>
          <button type="submit" class="btn">Add Message</button>
        </form>
        <div class="help-text">
          These messages scroll across the top of every page (Home, Shop, Checkout, Contact, and product pages). Reorder with the arrows, uncheck "Active" to hide one without deleting it, or remove it entirely. If every message is inactive or deleted, the banner disappears.
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h2>Current Messages (<?= count($messages) ?>)</h2>
      </div>
      <?php if (empty($messages)): ?>
        <div class="empty">No banner messages yet. Add one above.</div>
      <?php else: ?>
        <?php foreach ($messages as $i => $m): ?>
          <div class="msg-row <?= $m['active'] ? '' : 'inactive' ?>">
            <div class="msg-actions">
              <form method="post"><input type="hidden" name="formAction" value="move"/><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"/><input type="hidden" name="direction" value="up"/><button type="submit" class="btn btn-outline btn-icon" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
              <form method="post"><input type="hidden" name="formAction" value="move"/><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"/><input type="hidden" name="direction" value="down"/><button type="submit" class="btn btn-outline btn-icon" <?= $i === count($messages) - 1 ? 'disabled' : '' ?>>↓</button></form>
            </div>
            <form method="post" class="msg-text" style="display:flex;gap:10px;align-items:center;">
              <input type="hidden" name="formAction" value="update"/>
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>"/>
              <input type="text" name="message" maxlength="200" value="<?= htmlspecialchars($m['message']) ?>" required/>
              <label class="active-label"><input type="checkbox" name="active" <?= $m['active'] ? 'checked' : '' ?>/> Active</label>
              <button type="submit" class="btn btn-outline btn-icon">Save</button>
            </form>
            <form method="post" onsubmit="return confirm('Remove this banner message?');">
              <input type="hidden" name="formAction" value="delete"/>
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>"/>
              <button type="submit" class="btn btn-danger btn-icon">Remove</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
