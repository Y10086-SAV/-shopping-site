<?php
/**
 * 我的订单列表
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$pdo = db();
$user = current_user();

$stmt = $pdo->prepare(
    'SELECT id, order_no, total, status, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC'
);
$stmt->execute([(int)$user['id']]);
$orders = $stmt->fetchAll();

$itemsByOrder = [];
if ($orders) {
    $ids = array_map(static fn($o) => (int)$o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $itemStmt = $pdo->prepare(
        "SELECT oi.order_id, oi.product_name, oi.price, oi.quantity, p.image
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.id"
    );
    $itemStmt->execute($ids);
    foreach ($itemStmt->fetchAll() as $row) {
        $itemsByOrder[(int)$row['order_id']][] = $row;
    }
}

$pageTitle = '我的订单';
$active = 'orders';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">我的订单</h1>
<p class="page-sub">共 <?= count($orders) ?> 个订单</p>

<?php if (!$orders): ?>
  <div class="empty">
    <div class="icon">📦</div>
    <h3>还没有订单</h3>
    <p>选购商品并提交订单后，可在这里查看订单状态</p>
    <div class="empty-actions">
      <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">去购物</a>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($orders as $order): ?>
    <div class="order-card">
      <div class="order-card-head">
        <div>
          <code><?= e($order['order_no']) ?></code>
          <div class="meta">下单时间：<?= e(format_datetime($order['created_at'])) ?></div>
        </div>
        <div class="admin-actions">
          <span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e($order['status']) ?></span>
          <a class="btn btn-outline btn-sm" href="<?= e(url('order.php?id=' . (int)$order['id'])) ?>">查看详情</a>
        </div>
      </div>

      <?php foreach ($itemsByOrder[(int)$order['id']] ?? [] as $item): ?>
        <div class="order-line">
          <span style="font-size:22px;">🛍️</span>
          <span class="name"><?= e($item['product_name']) ?></span>
          <span class="qty">¥<?= e(format_price($item['price'])) ?> × <?= (int)$item['quantity'] ?></span>
        </div>
      <?php endforeach; ?>

      <div class="order-total-line">
        <span>订单金额</span>
        <span class="price"><?= e(format_price($order['total'])) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
