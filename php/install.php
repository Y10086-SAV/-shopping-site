<?php
/**
 * 安装脚本：创建数据库、数据表并写入种子数据
 * 使用方式：
 *   - 浏览器访问 /php/install.php 点击“开始安装”
 *   - 或命令行执行：php php/install.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/categories.php';

/**
 * 执行安装
 * @return array{version:string, categories:int, products:int, users:int}
 */
function install_app(): array
{
    $sqlFile = __DIR__ . '/database/schema.sql';
    if (!is_file($sqlFile)) {
        throw new RuntimeException('未找到 database/schema.sql');
    }

    // 先连接服务器（不指定数据库），逐条执行建库/建表/种子 SQL
    $server = pdo_connect(null);
    $sql = file_get_contents($sqlFile);

    // 去掉整行 SQL 注释后再按分号拆分执行
    $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];
    $lines = array_filter($lines, static fn(string $line): bool => !str_starts_with(ltrim($line), '--'));
    $statements = preg_split('/;\s*(?:\r?\n|$)/', implode("\n", $lines)) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $server->exec($statement);
    }

    // 连接目标数据库，确保管理员账号存在
    $pdo = pdo_connect();
    $exists = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();
    if (!$exists) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(['admin', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    }

    // 为所有现有分类创建并回填承接表 products_cat_<分类ID>
    sync_all_category_tables($pdo);

    return [
        'version'    => (string)$server->query('SELECT VERSION()')->fetchColumn(),
        'categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'products'   => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'users'      => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    ];
}

// ---------- 命令行模式 ----------
if (PHP_SAPI === 'cli') {
    try {
        $stats = install_app();
        echo "安装完成！\n";
        echo "MySQL 版本：{$stats['version']}\n";
        echo "分类：{$stats['categories']}  商品：{$stats['products']}  用户：{$stats['users']}\n";
        echo "管理员账号：admin / admin123\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, '安装失败：' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

// ---------- 浏览器模式 ----------
$errors = [];
$stats = null;
$serverError = '';
$dbReady = false;

try {
    $probe = pdo_connect(null);
    $dbReady = true;
    try {
        $pdo = pdo_connect();
        $pdo->query('SELECT 1 FROM users LIMIT 1');
        $installed = true;
    } catch (Throwable $e) {
        $installed = false;
    }
} catch (Throwable $e) {
    $serverError = $e->getMessage();
    $installed = false;
}

if (is_post()) {
    csrf_verify();
    try {
        $stats = install_app();
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>安装 · <?= e(APP_NAME) ?> PHP 版</title>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/extra.css')) ?>" />
</head>
<body>
  <main class="container">
    <div class="form-card" style="max-width:640px;margin-top:56px;">
      <h2>优选商城 · PHP 版安装</h2>

      <?php if (!$dbReady): ?>
        <div class="flash flash-danger">无法连接 MySQL：<?= e($serverError) ?></div>
        <p class="form-tip">请确认 MySQL 已启动，并检查 config.php 中的数据库账号与密码。</p>
      <?php elseif ($stats): ?>
        <div class="flash flash-success">安装成功，数据库已就绪。</div>
        <div class="table-wrap" style="margin:18px 0;">
          <table>
            <tbody>
              <tr><th style="width:130px;">MySQL 版本</th><td><?= e($stats['version']) ?></td></tr>
              <tr><th>分类数量</th><td><?= (int)$stats['categories'] ?></td></tr>
              <tr><th>商品数量</th><td><?= (int)$stats['products'] ?></td></tr>
              <tr><th>用户数量</th><td><?= (int)$stats['users'] ?></td></tr>
              <tr><th>管理员账号</th><td><code>admin / admin123</code></td></tr>
            </tbody>
          </table>
        </div>
        <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">进入商城首页</a>
        <a href="<?= e(url('login.php')) ?>" class="btn btn-outline">去登录</a>
      <?php else: ?>
        <p>将创建数据库 <code><?= e(DB_NAME) ?></code>、5 张数据表（分类、用户、商品、订单、订单明细）并写入演示数据。</p>
        <?php if ($installed): ?>
          <div class="notice">检测到数据库已存在，重复执行不会清空已有数据。</div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
          <div class="flash flash-danger"><?= e($error) ?></div>
        <?php endforeach; ?>
        <form method="POST" style="margin-top:18px;">
          <?= csrf_field() ?>
          <button class="btn btn-primary" type="submit" style="width:100%;">开始安装</button>
        </form>
        <p class="form-tip" style="text-align:center;margin-top:14px;">
          数据库：<?= e(DB_HOST) ?>:<?= (int)DB_PORT ?> / <?= e(DB_NAME) ?> / 用户 <?= e(DB_USER) ?>
        </p>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
