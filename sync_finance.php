<?php
// Pulls products from the Finance app (Supabase) into the website's products table.
//
// Finance is the source of truth for PRICE and STOCK only. Everything else on a
// website product (photo, description, badge, category once set) is never overwritten.
//
// Run it from the admin Products page ("Sync from Finance" button, POST) or from a
// Hostinger cron job:  curl -s "https://yourdomain/sync_finance.php?token=FINANCE_SYNC_TOKEN"
require_once __DIR__ . '/config.php';

session_start();

$isAdmin = !empty($_SESSION['orders_admin']);
$tokenOk = $financeSyncToken !== null
    && isset($_GET['token'])
    && hash_equals($financeSyncToken, (string)$_GET['token']);

if (!$isAdmin && !$tokenOk) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
// A logged-in admin must press the button (POST); opening the URL by hand does nothing.
if (!$tokenOk && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

const SYNC_ALLOWED_CATEGORIES = ['luggage', 'kitchen', 'appliances', 'grocery'];
const SYNC_DEFAULT_CATEGORY = 'grocery';
const SYNC_PLACEHOLDER_IMAGE = 'allinonelogo.png';

/** One JSON request to Supabase. Never put credentials or tokens in an error message. */
function supabaseRequest(string $method, string $url, array $headers, ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP curl extension is not enabled on this server.');
    }
    $ch = curl_init($url);
    $headers[] = 'Accept: application/json';
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Could not reach the Finance server: ' . $err);
    }
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$status, json_decode((string)$raw, true)];
}

function syncFromFinance(mysqli $conn, string $baseUrl, string $anonKey, string $email, string $password): array
{
    $baseUrl = rtrim($baseUrl, '/');

    [$status, $auth] = supabaseRequest(
        'POST',
        $baseUrl . '/auth/v1/token?grant_type=password',
        ['apikey: ' . $anonKey],
        ['email' => $email, 'password' => $password]
    );
    if ($status !== 200 || empty($auth['access_token'])) {
        throw new RuntimeException('Finance login failed — check FINANCE_EMAIL and FINANCE_PASSWORD in config.local.php.');
    }
    $authHeaders = ['apikey: ' . $anonKey, 'Authorization: Bearer ' . $auth['access_token']];

    // Page through the products (Supabase returns at most 1000 rows per request).
    $finance = [];
    for ($offset = 0; ; $offset += 1000) {
        [$status, $rows] = supabaseRequest(
            'GET',
            $baseUrl . '/rest/v1/products?select=name,category,price,stock_level,is_active&order=name&limit=1000&offset=' . $offset,
            $authHeaders
        );
        if ($status !== 200 || !is_array($rows)) {
            throw new RuntimeException('Could not read products from Finance (HTTP ' . $status . ').');
        }
        $finance = array_merge($finance, $rows);
        if (count($rows) < 1000) {
            break;
        }
    }

    $upsert = $conn->prepare(
        'INSERT INTO products (name, category, price, stock, image, rating, reviews) VALUES (?,?,?,?,?,5,0)
         ON DUPLICATE KEY UPDATE price = VALUES(price), stock = VALUES(stock)'
    );
    $hide = $conn->prepare('UPDATE products SET stock = 0 WHERE name = ? AND stock <> 0');

    $result = ['total' => count($finance), 'added' => 0, 'updated' => 0, 'unchanged' => 0, 'out_of_stock' => 0, 'skipped' => []];

    foreach ($finance as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        // Inactive in Finance: keep it on the site but mark it out of stock.
        if (empty($row['is_active'])) {
            $hide->bind_param('s', $name);
            $hide->execute();
            $result['out_of_stock'] += $hide->affected_rows > 0 ? 1 : 0;
            continue;
        }

        $price = round((float)($row['price'] ?? 0), 2);
        if ($price <= 0) {
            $result['skipped'][] = "$name (price is 0 in Finance)";
            continue;
        }
        if (mb_strlen($name) > 150) {
            $result['skipped'][] = mb_substr($name, 0, 40) . '… (name longer than 150 characters)';
            continue;
        }

        $stock = max(0, (int)floor((float)($row['stock_level'] ?? 0)));
        $category = strtolower(trim((string)($row['category'] ?? '')));
        if (!in_array($category, SYNC_ALLOWED_CATEGORIES, true)) {
            $category = SYNC_DEFAULT_CATEGORY;
        }
        $image = SYNC_PLACEHOLDER_IMAGE;

        $upsert->bind_param('ssdis', $name, $category, $price, $stock, $image);
        $upsert->execute();
        // MySQL: 1 = inserted, 2 = existing row changed, 0 = existing row already identical.
        match ($upsert->affected_rows) {
            1 => $result['added']++,
            2 => $result['updated']++,
            default => $result['unchanged']++,
        };
    }

    return $result;
}

$summary = null;
$errorMessage = null;
try {
    if (!$financeSupabaseUrl || !$financeSupabaseKey || !$financeEmail || !$financePassword) {
        throw new RuntimeException('Finance sync is not configured. Set FINANCE_SUPABASE_URL, FINANCE_SUPABASE_ANON_KEY, FINANCE_EMAIL and FINANCE_PASSWORD in config.local.php.');
    }
    $summary = syncFromFinance(connectDb(), $financeSupabaseUrl, $financeSupabaseKey, $financeEmail, $financePassword);
} catch (Throwable $e) {
    error_log('sync_finance: ' . $e->getMessage());
    $errorMessage = $e instanceof RuntimeException ? $e->getMessage() : 'Sync failed unexpectedly. See the server error log.';
}

if ($tokenOk) {
    header('Content-Type: application/json; charset=utf-8');
    if ($errorMessage !== null) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } else {
        echo json_encode(['success' => true] + $summary);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sync from Finance — ALL IN ONE ABROAD</title>
  <style>
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; color: #111827; margin: 0; padding: 40px 16px; }
    .card { max-width: 560px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; }
    h1 { font-size: 20px; margin: 0 0 16px; }
    .ok { color: #166534; background: #dcfce7; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-weight: 600; }
    .err { color: #991b1b; background: #fee2e2; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-weight: 600; }
    ul { margin: 0 0 18px 18px; padding: 0; line-height: 1.7; }
    a.btn { display: inline-block; background: #f97316; color: #fff; text-decoration: none; font-weight: 700; padding: 10px 18px; border-radius: 8px; }
    .note { color: #6b7280; font-size: 13px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Sync from Finance</h1>
    <?php if ($errorMessage !== null): ?>
      <div class="err"><?= htmlspecialchars($errorMessage) ?></div>
    <?php else: ?>
      <div class="ok">Sync finished.</div>
      <ul>
        <li><?= (int)$summary['total'] ?> product(s) found in Finance</li>
        <li><?= (int)$summary['added'] ?> added to the website</li>
        <li><?= (int)$summary['updated'] ?> updated (price or stock changed)</li>
        <li><?= (int)$summary['unchanged'] ?> already up to date</li>
        <li><?= (int)$summary['out_of_stock'] ?> set to out of stock (inactive in Finance)</li>
        <?php foreach ($summary['skipped'] as $s): ?>
          <li>Skipped: <?= htmlspecialchars($s) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="note">New products use the logo as a placeholder photo until you set a real one.</p>
    <?php endif; ?>
    <p><a class="btn" href="products.php">Back to Products</a></p>
  </div>
</body>
</html>
