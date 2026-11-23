<?php
/**
 * 商品详情页
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$id = (int)get('id', '0');

$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flash('danger', '商品不存在或已下架');
    redirect('index.php');
}

$relatedStmt = $pdo->prepare(
    'SELECT id, name, price, image, stock
     FROM products
     WHERE category_id = ? AND id <> ?
     ORDER BY created_at DESC LIMIT 4'
);
$relatedStmt->execute([(int)$product['category_id'], $id]);
$related = $relatedStmt->fetchAll();

$pageTitle = $product['name'];
$active = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
  <a href="<?= e(url('index.php')) ?>">首页</a>
  <span>/</span>
  <a href="<?= e(url('index.php?category=' . (int)$product['category_id'] . '#products')) ?>"><?= e($product['category_name']) ?></a>
  <span>/</span>
  <span><?= e($product['name']) ?></span>
</div>

<div class="detail-wrap">
  <div class="detail-image">
    <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>"
         style="width:100%;height:100%;object-fit:cover;aspect-ratio:1;" />
  </div>
  <div class="detail-info">
    <h1><?= e($product['name']) ?></h1>
    <p class="meta" style="color:var(--muted);font-size:14px;">
      分类：<?= e($product['category_name']) ?> · 上架时间：<?= e(format_datetime($product['created_at'])) ?>
    </p>
    <div class="detail-price" style="margin:20px 0;"><?= e(format_price($product['price'])) ?></div>
    <p class="detail-desc"><?= nl2br(e($product['description'])) ?></p>
    <p class="meta" style="color:var(--muted);font-size:14px;">库存：<?= (int)$product['stock'] ?> 件</p>

    <?php if ((int)$product['stock'] > 0): ?>
      <form action="<?= e(url('cart.php')) ?>" method="POST" class="detail-actions">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add" />
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>" />
        <label for="qty" style="font-size:14px;font-weight:600;">数量</label>
        <input class="qty-input" id="qty" type="number" name="qty" value="1" min="1"
               max="<?= (int)$product['stock'] ?>" />
        <button class="btn btn-primary btn-lg" type="submit">加入购物车</button>
        <a class="btn btn-outline btn-lg" href="<?= e(url('cart.php')) ?>">去购物车结算</a>
      </form>
    <?php else: ?>
      <div class="notice" style="margin-top:22px;">该商品暂时缺货，请稍后再来看看。</div>
    <?php endif; ?>
  </div>
</div>

<?php if ($related): ?>
  <div class="page-head" style="margin-top:36px;">
    <h2 class="page-title" style="font-size:22px;">同类推荐</h2>
  </div>
  <div class="product-grid">
    <?php foreach ($related as $item): ?>
      <article class="product-card">
        <a class="thumb" href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>">
          <img src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy" />
        </a>
        <div class="info">
          <a class="name" href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>"><?= e($item['name']) ?></a>
          <div class="bottom">
            <span class="price"><?= e(format_price($item['price'])) ?></span>
            <a class="btn btn-outline btn-sm" href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>">查看</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
