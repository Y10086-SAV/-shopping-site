<?php
/**
 * 后台订单管理：列表、状态流转
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

$statuses = ['待发货', '已发货', '已完成'];

if (is_post()) {
    csrf_verify();
    $id = (int)post('id', '0');
    $status = post('status');

    if (!in_array($status, $statuses, true)) {
        flash('danger', '无效的订单状态');
    } else {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        flash('success', '订单状态已更新为“' . $status . '”');
    }
    redirect('admin/orders.php');
}

$page = max(1, (int)get('page', '1'));
$perPage = 10;
$filterStatus = get('status');
$where = '';
$params = [];
if (in_array($filterStatus, $statuses, true)) {
    $where = 'WHERE o.status = :status';
    $params[':status'] = $filterStatus;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT o.*, u.username
     FROM orders o JOIN users u ON u.id = o.user_id
     $where ORDER BY o.created_at DESC, o.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$queryBase = $filterStatus !== '' ? ['status' => $filterStatus] : [];

$pageTitle = '订单管理';
$active = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">订单管理</h1>

<div class="admin-nav">
  <a href="<?= e(url('admin/index.php')) ?>">仪表盘</a>
  <a href="<?= e(url('admin/products.php')) ?>">商品管理</a>
  <a href="<?= e(url('admin/orders.php')) ?>" class="active">订单管理</a>
  <a href="<?= e(url('admin/users.php')) ?>">用户管理</a>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= e(url('admin/orders.php')) ?>" class="filter-form">
    <span class="filter-label">订单状态</span>
    <select class="select" name="status">
      <option value="">全部</option>
      <?php foreach ($statuses as $status): ?>
        <option value="<?= e($status) ?>" <?= $filterStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">筛选</button>
    <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-outline">重置</a>
  </form>
</div>

<div class="list-meta">
  <span>共 <?= $total ?> 个订单，第 <?= $page ?> / <?= $totalPages ?> 页</span>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>订单号</th><th>用户</th><th>收货人</th><th>金额</th><th>下单时间</th><th>状态</th><th>操作</th></tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);">暂无订单</td></tr>
      <?php endif; ?>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><a href="<?= e(url('order.php?id=' . (int)$order['id'])) ?>"><code><?= e($order['order_no']) ?></code></a></td>
          <td><?= e($order['username']) ?></td>
          <td><?= e($order['receiver']) ?><br /><span class="meta"><?= e($order['phone']) ?></span></td>
          <td><span class="price"><?= e(format_price($order['total'])) ?></span></td>
          <td><?= e(format_datetime($order['created_at'])) ?></td>
          <td><span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e($order['status']) ?></span></td>
          <td>
            <form action="<?= e(url('admin/orders.php')) ?>" method="POST" class="admin-actions">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$order['id'] ?>" />
              <select class="select" name="status" style="padding:6px 10px;">
                <?php foreach ($statuses as $status): ?>
                  <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-outline btn-sm" type="submit">更新</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= pagination($page, $totalPages, $queryBase) ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
