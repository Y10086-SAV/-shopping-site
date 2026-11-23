<?php
/**
 * 订单详情（本人或管理员可查看）
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$pdo = db();
$user = current_user();
$id = (int)get('id', '0');

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order || (!is_admin() && (int)$order['user_id'] !== (int)$user['id'])) {
    flash('danger', '订单不存在或无权查看');
    redirect(is_admin() ? 'admin/orders.php' : 'orders.php');
}

$itemStmt = $pdo->prepare(
    'SELECT oi.*, p.image
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ? ORDER BY oi.id'
);
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

$pageTitle = '订单详情';
$active = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
  <a href="<?= e(url('orders.php')) ?>">我的订单</a>
  <span>/</span>
  <span>订单详情</span>
</div>

<div class="order-card">
  <div class="order-head" style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:center;">
    <div>
      <h2 style="font-size:20px;">订单号 <code><?= e($order['order_no']) ?></code></h2>
      <p class="meta">下单时间：<?= e(format_datetime($order['created_at'])) ?></p>
    </div>
    <span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e($order['status']) ?></span>
  </div>

  <div class="table-wrap" style="box-shadow:none;margin-top:18px;">
    <table>
      <thead>
        <tr><th>商品</th><th>单价</th><th>数量</th><th>小计</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= e($item['product_name']) ?></td>
            <td>¥<?= e(format_price($item['price'])) ?></td>
            <td><?= (int)$item['quantity'] ?></td>
            <td><span class="price"><?= e(format_price((float)$item['price'] * (int)$item['quantity'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:22px;">
    <div>
      <h3 style="font-size:15px;margin-bottom:8px;">收货信息</h3>
      <p style="font-size:14px;line-height:1.9;">
        收货人：<?= e($order['receiver']) ?><br />
        联系电话：<?= e($order['phone']) ?><br />
        收货地址：<?= e($order['address']) ?>
      </p>
    </div>
    <div class="order-total" style="align-self:end;">
      <span>订单总额</span>
      <span class="price"><?= e(format_price($order['total'])) ?></span>
    </div>
  </div>

  <div class="cart-actions">
    <a class="btn btn-outline" href="<?= e(url(is_admin() ? 'admin/orders.php' : 'orders.php')) ?>">返回订单列表</a>
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">继续购物</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
