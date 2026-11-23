<?php
/**
 * 后台商品管理：搜索、分页、编辑、删除入口
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
$categoryId = (int)get('category', '0');

$where = [];
$params = [];
if ($keyword !== '') {
    $where[] = 'p.name LIKE :kw';
    $params[':kw'] = '%' . $keyword . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category';
    $params[':category'] = $categoryId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     $whereSql ORDER BY p.id DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$queryBase = array_filter([
    'q'        => $keyword,
    'category' => $categoryId ?: '',
], static fn($v) => $v !== '' && $v !== null);

$pageTitle = '商品管理';
$active = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">商品管理</h1>

<div class="admin-nav">
  <a href="<?= e(url('admin/index.php')) ?>">仪表盘</a>
  <a href="<?= e(url('admin/products.php')) ?>" class="active">商品管理</a>
  <a href="<?= e(url('admin/orders.php')) ?>">订单管理</a>
  <a href="<?= e(url('admin/users.php')) ?>">用户管理</a>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= e(url('admin/products.php')) ?>" class="filter-form">
    <input class="search-input" type="search" name="q" value="<?= e($keyword) ?>" placeholder="搜索商品名称…" />
    <select class="select" name="category">
      <option value="">全部分类</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
          <?= e($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">查询</button>
    <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-outline">重置</a>
  </form>
</div>

<div class="list-meta">
  <span>共 <?= $total ?> 件商品，第 <?= $page ?> / <?= $totalPages ?> 页</span>
  <a href="<?= e(url('admin/product_form.php')) ?>" class="btn btn-primary">＋ 新增商品</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>图片</th><th>名称</th><th>分类</th><th>价格</th><th>库存</th><th>更新时间</th><th>操作</th></tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);">暂无商品</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $product): ?>
        <tr>
          <td><img class="thumb-sm" src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>" /></td>
          <td><?= e($product['name']) ?></td>
          <td><?= e($product['category_name']) ?></td>
          <td><span class="price"><?= e(format_price($product['price'])) ?></span></td>
          <td><?= (int)$product['stock'] ?></td>
          <td><?= e(format_datetime($product['updated_at'])) ?></td>
          <td>
            <div class="admin-actions">
              <a class="btn btn-outline btn-sm" href="<?= e(url('admin/product_form.php?id=' . (int)$product['id'])) ?>">编辑</a>
              <form action="<?= e(url('admin/product_delete.php')) ?>" method="POST" style="display:inline"
                    onsubmit="return confirm('确定删除该商品？删除后不可恢复。');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>" />
                <button class="btn btn-danger btn-sm" type="submit">删除</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= pagination($page, $totalPages, $queryBase) ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
