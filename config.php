<?php
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocalEnv = $httpHost === '' || strpos($httpHost, 'localhost') !== false || strpos($httpHost, '127.0.0.1') !== false;

error_reporting(E_ALL);
ini_set('display_errors', $isLocalEnv ? '1' : '0');
ini_set('log_errors', '1');

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'allinoneabroad';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$adminPassword = getenv('ADMIN_PASSWORD') ?: null;
// Where the admin "Finance" menu link goes. Set FINANCE_APP_URL in config.local.php
// to send it to the external Finance app; unset, it uses the built-in finance.php.
$financeUrl = getenv('FINANCE_APP_URL') ?: 'finance.php';

// Finance -> website product sync (sync_finance.php). Credentials belong in config.local.php.
$financeSupabaseUrl = getenv('FINANCE_SUPABASE_URL') ?: null;
$financeSupabaseKey = getenv('FINANCE_SUPABASE_ANON_KEY') ?: null;
$financeEmail = getenv('FINANCE_EMAIL') ?: null;
$financePassword = getenv('FINANCE_PASSWORD') ?: null;
$financeSyncToken = getenv('FINANCE_SYNC_TOKEN') ?: null; // lets a cron job call sync_finance.php

function connectDb(): mysqli
{
    global $dbHost, $dbName, $dbUser, $dbPass;

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// Bump this whenever product images are re-uploaded, to bypass Hostinger's
// CDN caching a stale response (e.g. a 404 from before an image was uploaded).
const IMG_ASSET_VERSION = 5;

function imgUrl(string $path): string
{
    if ($path === '') {
        return $path;
    }
    $sep = strpos($path, '?') !== false ? '&' : '?';
    return $path . $sep . 'v=' . IMG_ASSET_VERSION;
}

function colorToHex(string $name): string
{
    $map = [
        'sky blue' => '#7ec8e3', 'ocean blue' => '#2563eb', 'navy' => '#1e3a5f',
        'blue' => '#3b82f6', 'light green' => '#86efac', 'green' => '#16a34a',
        'grey' => '#9ca3af', 'gray' => '#9ca3af', 'pink' => '#f9a8d4',
        'purple' => '#a855f7', 'khaki' => '#c3b091', 'black' => '#111827',
        'white' => '#f9fafb', 'red' => '#dc2626', 'orange' => '#f97316',
        'yellow' => '#facc15', 'mint' => '#a7f3d0', 'beige' => '#e8dcc8',
        'cream' => '#f5ead6', 'brown' => '#78350f', 'maroon' => '#7f1d1d',
        'gold' => '#d4af37', 'silver' => '#c0c0c0',
    ];
    $key = strtolower(trim($name));
    return $map[$key] ?? '#d1d5db';
}
