<?php
/**
 * 后台用户管理：注册用户列表与消费统计
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

$page = max(1, (int)get('page', '1'));
$perPage = 10;
$keyword = get('q');

$where = '';
$params = [];
if ($keyword !== '') {
    $where = 'WHERE username LIKE :kw1 OR email LIKE :kw2';
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.email, u.role, u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total), 0) AS total_spent
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     $where
     GROUP BY u.id
     ORDER BY u.created_at DESC, u.id DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$queryBase = $keyword !== '' ? ['q' => $keyword] : [];

$pageTitle = '用户管理';
$active = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">用户管理</h1>

<div class="admin-nav">
  <a href="<?= e(url('admin/index.php')) ?>">仪表盘</a>
  <a href="<?= e(url('admin/products.php')) ?>">商品管理</a>
  <a href="<?= e(url('admin/orders.php')) ?>">订单管理</a>
  <a href="<?= e(url('admin/users.php')) ?>" class="active">用户管理</a>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= e(url('admin/users.php')) ?>" class="filter-form">
    <input class="search-input" type="search" name="q" value="<?= e($keyword) ?>" placeholder="搜索用户名或邮箱…" />
    <button class="btn btn-primary" type="submit">搜索</button>
    <a href="<?= e(url('admin/users.php')) ?>" class="btn btn-outline">重置</a>
  </form>
</div>

<div class="list-meta">
  <span>共 <?= $total ?> 位用户，第 <?= $page ?> / <?= $totalPages ?> 页</span>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>订单数</th><th>消费金额</th><th>注册时间</th></tr>
    </thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);">暂无用户</td></tr>
      <?php endif; ?>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= (int)$user['id'] ?></td>
          <td><?= e($user['username']) ?></td>
          <td><?= e($user['email']) ?></td>
          <td><span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer' ?>"><?= $user['role'] === 'admin' ? '管理员' : '顾客' ?></span></td>
          <td><?= (int)$user['order_count'] ?></td>
          <td><span class="price"><?= e(format_price($user['total_spent'])) ?></span></td>
          <td><?= e(format_datetime($user['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= pagination($page, $totalPages, $queryBase) ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
