<?php
/**
 * 购物车（数据保存在 Session 中）
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

// ---------- 处理购物车操作 ----------
if (is_post()) {
    csrf_verify();
    $action = post('action');

    if ($action === 'clear') {
        unset($_SESSION['cart']);
        flash('success', '购物车已清空');
        redirect('cart.php');
    }

    $id = (int)post('id', '0');
    $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        flash('danger', '商品不存在或已下架');
        redirect('cart.php');
    }

    if ($action === 'add' || $action === 'update') {
        $qty = max(1, (int)post('qty', '1'));
        $stock = (int)$product['stock'];
        if ($stock <= 0) {
            flash('danger', '该商品已售罄');
            redirect('cart.php');
        }
        $qty = min($qty, $stock, 99);

        if ($action === 'add') {
            $current = (int)($_SESSION['cart'][$id] ?? 0);
            $qty = min($current + $qty, $stock, 99);
            $_SESSION['cart'][$id] = $qty;
            flash('success', '已加入购物车：' . $product['name']);
        } else {
            $_SESSION['cart'][$id] = $qty;
            flash('success', '数量已更新');
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
        flash('success', '已移出购物车：' . $product['name']);
    }

    redirect('cart.php');
}

$items = cart_items($pdo);
$total = cart_total($items);
$count = cart_count();

$pageTitle = '购物车';
$active = '';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">购物车</h1>
<p class="page-sub">共 <?= $count ?> 件商品</p>

<?php if (!$items): ?>
  <div class="empty">
    <div class="icon">🛒</div>
    <h3>购物车还是空的</h3>
    <p>去挑选几件心仪的商品吧</p>
    <div class="empty-actions">
      <a href="<?= e(url('index.php')) ?>" class="btn btn-primary">去逛逛</a>
    </div>
  </div>
<?php else: ?>
  <div class="cart-layout">
    <div class="table-wrap" style="padding:8px 22px 18px;">
      <?php foreach ($items as $item): ?>
        <div class="cart-item">
          <a href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>">
            <img class="cart-thumb" src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['name']) ?>" />
          </a>
          <div class="cart-info">
            <a class="cart-name" href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>"><?= e($item['name']) ?></a>
            <div class="meta" style="color:var(--muted);font-size:13px;">库存 <?= (int)$item['stock'] ?> 件</div>
          </div>
          <div class="cart-price">单价 <?= e(format_price($item['price'])) ?></div>
          <form action="<?= e(url('cart.php')) ?>" method="POST" class="qty-form" style="display:flex;align-items:center;gap:8px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input class="qty-input" type="number" name="qty" value="<?= (int)$item['quantity'] ?>"
                   min="1" max="<?= (int)$item['stock'] ?>" style="width:72px;" />
            <button class="btn btn-outline btn-sm" type="submit">更新</button>
          </form>
          <div class="cart-subtotal price" style="font-size:16px;"><?= e(format_price($item['subtotal'])) ?></div>
          <form action="<?= e(url('cart.php')) ?>" method="POST" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <button class="btn btn-danger btn-sm" type="submit">删除</button>
          </form>
        </div>
      <?php endforeach; ?>

      <div class="cart-actions">
        <a class="btn btn-outline" href="<?= e(url('index.php')) ?>">继续购物</a>
        <form action="<?= e(url('cart.php')) ?>" method="POST" style="margin-left:auto;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="clear" />
          <button class="btn btn-ghost" type="submit" onclick="return confirm('确定清空购物车？');">清空购物车</button>
        </form>
      </div>
    </div>

    <aside class="summary-card">
      <h3>订单摘要</h3>
      <div class="summary-row"><span>商品件数</span><strong><?= $count ?> 件</strong></div>
      <div class="summary-row"><span>商品金额</span><strong>¥<?= e(format_price($total)) ?></strong></div>
      <div class="summary-row"><span>运费</span><strong>免运费</strong></div>
      <div class="summary-total">
        <span>应付合计</span>
        <span class="price"><?= e(format_price($total)) ?></span>
      </div>
      <?php if (current_user()): ?>
        <a class="btn btn-primary btn-block btn-lg" href="<?= e(url('checkout.php')) ?>" style="margin-top:16px;">去结算</a>
      <?php else: ?>
        <a class="btn btn-primary btn-block btn-lg" href="<?= e(url('login.php')) ?>" style="margin-top:16px;">登录后结算</a>
      <?php endif; ?>
    </aside>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
