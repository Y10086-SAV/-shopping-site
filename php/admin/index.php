<?php
/**
 * 后台仪表盘：数据统计与最近订单
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

$stats = [
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders'   => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'users'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'revenue'  => (float)$pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn(),
    'pending'  => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = '待发货'")->fetchColumn(),
];

$recentOrders = $pdo->query(
    'SELECT o.id, o.order_no, o.receiver, o.total, o.status, o.created_at, u.username
     FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC LIMIT 5'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT id, name, stock, price FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5'
)->fetchAll();

$pageTitle = '后台管理';
$active = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">后台管理</h1>

<div class="admin-nav">
  <a href="<?= e(url('admin/index.php')) ?>" class="active">仪表盘</a>
  <a href="<?= e(url('admin/products.php')) ?>">商品管理</a>
  <a href="<?= e(url('admin/orders.php')) ?>">订单管理</a>
  <a href="<?= e(url('admin/users.php')) ?>">用户管理</a>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="label">商品总数</div><div class="value"><?= $stats['products'] ?></div></div>
  <div class="stat-card"><div class="label">订单总数</div><div class="value"><?= $stats['orders'] ?></div></div>
  <div class="stat-card"><div class="label">注册顾客</div><div class="value"><?= $stats['users'] ?></div></div>
  <div class="stat-card"><div class="label">总营收</div><div class="value price"><?= e(format_price($stats['revenue'])) ?></div></div>
</div>

<?php if ($stats['pending'] > 0): ?>
  <div class="notice">当前有 <?= $stats['pending'] ?> 个待发货订单，请及时处理。</div>
<?php endif; ?>

<div class="cart-layout" style="grid-template-columns:1fr 380px;">
  <div>
    <div class="page-head" style="margin-bottom:12px;">
      <h2 style="font-size:18px;">最近订单</h2>
      <a class="btn btn-outline btn-sm" href="<?= e(url('admin/orders.php')) ?>">查看全部</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>订单号</th><th>用户</th><th>金额</th><th>状态</th><th>时间</th></tr></thead>
        <tbody>
          <?php if (!$recentOrders): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);">暂无订单</td></tr>
          <?php endif; ?>
          <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><a href="<?= e(url('order.php?id=' . (int)$order['id'])) ?>"><code><?= e($order['order_no']) ?></code></a></td>
              <td><?= e($order['username']) ?></td>
              <td><span class="price"><?= e(format_price($order['total'])) ?></span></td>
              <td><span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e($order['status']) ?></span></td>
              <td><?= e(format_datetime($order['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="page-head" style="margin-bottom:12px;">
      <h2 style="font-size:18px;">库存预警</h2>
      <a class="btn btn-outline btn-sm" href="<?= e(url('admin/products.php')) ?>">管理商品</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>商品</th><th>库存</th></tr></thead>
        <tbody>
          <?php if (!$lowStock): ?>
            <tr><td colspan="2" style="text-align:center;color:var(--muted);">库存充足</td></tr>
          <?php endif; ?>
          <?php foreach ($lowStock as $product): ?>
            <tr class="low-stock">
              <td><a href="<?= e(url('admin/product_form.php?id=' . (int)$product['id'])) ?>"><?= e($product['name']) ?></a></td>
              <td><?= (int)$product['stock'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
