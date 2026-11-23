<?php
/**
 * 应用配置
 * - 数据库连接参数可通过 config.local.php 或环境变量（SHOP_DB_*）覆盖
 * - config.local.php 已加入 .gitignore，用于保存本机私有账号密码
 */
declare(strict_types=1);

// ---------- 数据库 ----------
// 本机私有账号密码放在 config.local.php（已加入 .gitignore，不会提交到 GitHub）
$local = [];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $loaded = require $localFile;
    if (is_array($loaded)) {
        $local = $loaded;
    }
}

define('DB_HOST', getenv('SHOP_DB_HOST') ?: ($local['host'] ?? '127.0.0.1'));
define('DB_PORT', (int)(getenv('SHOP_DB_PORT') ?: ($local['port'] ?? 3306)));
define('DB_NAME', getenv('SHOP_DB_NAME') ?: ($local['name'] ?? 'shopping_php'));
define('DB_USER', getenv('SHOP_DB_USER') ?: ($local['user'] ?? 'root'));
define('DB_PASS', getenv('SHOP_DB_PASS') ?: ($local['pass'] ?? ''));

// ---------- 应用 ----------
define('APP_NAME', '优选商城');
define('APP_ROOT', __DIR__);
define('UPLOAD_DIR', APP_ROOT . '/uploads/products');
define('UPLOAD_URL', 'uploads/products');

/**
 * 计算应用在站点中的基础路径：
 * - 直接以 php/ 为站点根目录：APP_BASE = ''
 * - 以项目根目录为站点根目录：APP_BASE = '/php'
 */
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
$appRootReal = realpath(__DIR__);
$base = '';
if ($docRoot && $appRootReal) {
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    $appRootReal = rtrim(str_replace('\\', '/', $appRootReal), '/');
    if (str_starts_with($appRootReal, $docRoot)) {
        $base = substr($appRootReal, strlen($docRoot));
    }
}
define('APP_BASE', $base === '/' ? '' : $base);

// ---------- 运行环境 ----------
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------- 会话（Session） ----------
if (session_status() === PHP_SESSION_NONE) {
    session_name('SHOPSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => APP_BASE === '' ? '/' : APP_BASE . '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
