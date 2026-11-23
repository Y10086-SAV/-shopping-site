<?php
/**
 * 数据库连接（PDO）
 * - pdo_connect()：可指定数据库名，供安装脚本在库尚未创建时使用
 * - db()：应用统一入口，连接失败时给出友好提示
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * 建立 PDO 连接
 */
function pdo_connect(?string $dbName = DB_NAME, array $extraOptions = []): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    if ($dbName !== null && $dbName !== '') {
        $dsn .= ';dbname=' . $dbName;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, array_replace($options, $extraOptions));
}

/**
 * 获取应用数据库连接（单例）
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = pdo_connect();
    } catch (PDOException $e) {
        http_response_code(500);
        $message = '数据库连接失败，请先访问 install.php 完成初始化，或检查 config.php 中的连接参数。';
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL . $e->getMessage() . PHP_EOL);
            exit(1);
        }
        echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>数据库连接失败</title>'
            . '<body style="font-family:system-ui,sans-serif;padding:48px;max-width:720px;margin:0 auto">'
            . '<h1 style="color:#e74c3c">数据库连接失败</h1><p>' . htmlspecialchars($message) . '</p>'
            . '<p style="color:#888;font-size:14px">' . htmlspecialchars($e->getMessage()) . '</p>'
            . '<p><a href="install.php">前往安装</a></p></body></html>';
        exit;
    }

    return $pdo;
}
