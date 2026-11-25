<?php
/**
 * 商城首页 / 商品列表
 * 功能：浏览全部商品、关键词搜索、分类筛选、价格排序、分页
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

$page    = max(1, (int)get('page', '1'));
$perPage = 6;
$keyword = get('q');
$categoryId = (int)get('category', '0');
$sort    = get('sort', 'newest');

// ---------- 查询条件 ----------
$where = [];
$params = [];
if ($keyword !== '') {
    $where[] = '(p.name LIKE :kw1 OR p.description LIKE :kw2)';
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category';
    $params[':category'] = $categoryId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------- 排序白名单 ----------
$orderMap = [
    'newest'     => 'p.created_at DESC, p.id DESC',
    'price_asc'  => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
    'stock'      => 'p.stock DESC, p.id DESC',
];
$orderBy = $orderMap[$sort] ?? $orderMap['newest'];

// ---------- 总数与分页 ----------
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     $whereSql
     ORDER BY $orderBy
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();

$pageTitle = '首页';
$active = 'home';
$queryBase = array_filter([
    'q'        => $keyword,
    'category' => $categoryId ?: '',
    'sort'     => $sort,
], static fn($v) => $v !== '' && $v !== null);
$categoryUrl = static function (int $id) use ($queryBase): string {
    $params = $queryBase;
    if ($id > 0) {
        $params['category'] = $id;
    } else {
        unset($params['category']);
    }
    return url('index.php') . ($params ? '?' . http_build_query($params) : '') . '#products';
};

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-content">
    <span class="hero-tag">🌸 春日新品 · PHP + MySQL 动态商城</span>
    <p class="hero-jp">今天，也遇见好东西。</p>
    <h1>好物优选<br><em>一站购齐</em></h1>
    <p>像翻开一本春日画册：精选数码、家电、服饰与美妆，把心动好物轻轻带回家。</p>
    <div class="hero-actions">
      <a href="#products" class="btn btn-light btn-lg">立即选购</a>
      <?php if (!current_user()): ?>
        <a href="<?= e(url('register.php')) ?>" class="btn btn-outline btn-lg">免费注册</a>
      <?php endif; ?>
    </div>
    <div class="hero-float-card">
      <span class="hero-avatar"><img src="<?= e(asset('img/mascot-clerk.png')) ?>" alt="" /></span>
      <div>
        <strong>今日推荐</strong>
        <small>精选 12 件好物</small>
      </div>
    </div>
  </div>
  <div class="hero-art" aria-hidden="true"></div>
</section>

<div class="feature-strip">
  <div class="feature-item">
    <span style="font-size:22px">🚚</span>
    <div><strong>极速发货</strong><small>下单后 24 小时内出库</small></div>
  </div>
  <div class="feature-item">
    <span style="font-size:22px">🛡️</span>
    <div><strong>正品保障</strong><small>全场商品品质保证</small></div>
  </div>
  <div class="feature-item">
    <span style="font-size:22px">💬</span>
    <div><strong>贴心服务</strong><small>7 天无理由退换</small></div>
  </div>
</div>

<div class="page-head" id="products">
  <div>
    <h1 class="page-title">全部商品</h1>
    <p class="page-sub">共 <?= $total ?> 件商品，支持关键词搜索与分页浏览</p>
  </div>
</div>

<div class="filter-bar">
  <form method="GET" action="<?= e(url('index.php')) ?>" class="filter-form">
    <input class="search-input" type="search" name="q" value="<?= e($keyword) ?>" placeholder="搜索商品名称或描述…" />
    <select class="select" name="category">
      <option value="">全部分类</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
          <?= e($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select class="select" name="sort">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>最新上架</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>价格从低到高</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>价格从高到低</option>
      <option value="stock" <?= $sort === 'stock' ? 'selected' : '' ?>>库存优先</option>
    </select>
    <button class="btn btn-primary" type="submit">搜索</button>
    <?php if ($keyword !== '' || $categoryId > 0 || $sort !== 'newest'): ?>
      <a href="<?= e(url('index.php')) ?>" class="btn btn-outline">重置</a>
    <?php endif; ?>
  </form>
</div>

<div class="category-tabs" style="margin-bottom:18px;">
  <a class="category-tab <?= $categoryId === 0 ? 'active' : '' ?>" href="<?= e($categoryUrl(0)) ?>">全部</a>
  <?php foreach ($categories as $cat): ?>
    <a class="category-tab <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>"
       href="<?= e($categoryUrl((int)$cat['id'])) ?>"><?= e($cat['name']) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$products): ?>
  <div class="empty">
    <div class="icon">🔍</div>
    <h3>没有找到相关商品</h3>
    <p>换个关键词或分类再试试吧</p>
    <div class="empty-actions">
      <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">查看全部商品</a>
    </div>
  </div>
<?php else: ?>
  <div class="product-grid">
    <?php foreach ($products as $product): ?>
      <article class="product-card">
        <a class="thumb" href="<?= e(url('product.php?id=' . (int)$product['id'])) ?>">
          <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy" />
        </a>
        <div class="info">
          <a class="name" href="<?= e(url('product.php?id=' . (int)$product['id'])) ?>"><?= e($product['name']) ?></a>
          <div class="meta">
            <?= e($product['category_name']) ?> · 库存 <?= (int)$product['stock'] ?>
          </div>
          <div class="bottom">
            <span class="price"><?= e(format_price($product['price'])) ?></span>
            <form action="<?= e(url('cart.php')) ?>" method="POST" class="inline-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add" />
              <input type="hidden" name="id" value="<?= (int)$product['id'] ?>" />
              <input type="hidden" name="qty" value="1" />
              <button class="btn btn-primary btn-sm" type="submit" <?= (int)$product['stock'] <= 0 ? 'disabled' : '' ?>>
                加入购物车
              </button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?= pagination($page, $totalPages, $queryBase) ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
