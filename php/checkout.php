<?php
/**
 * 结算下单：写入订单、订单明细并扣减库存（事务）
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$pdo = db();
$user = current_user();

$items = cart_items($pdo);
if (!$items) {
    flash('warning', '购物车为空，请先选择商品');
    redirect('cart.php');
}
$total = cart_total($items);

$errors = [];
$form = [
    'receiver' => post('receiver', $user['username']),
    'phone'    => post('phone'),
    'address'  => post('address'),
];

if (is_post()) {
    csrf_verify();

    if (mb_strlen($form['receiver']) < 2 || mb_strlen($form['receiver']) > 50) {
        $errors[] = '请填写 2-50 个字符的收货人姓名';
    }
    if (!preg_match('/^1[3-9]\d{9}$/', $form['phone'])) {
        $errors[] = '请填写正确的 11 位手机号码';
    }
    if (mb_strlen($form['address']) < 5 || mb_strlen($form['address']) > 255) {
        $errors[] = '请填写 5-255 个字符的收货地址';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // 锁定商品行，校验库存并重新计算金额
            $lockStmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE');
            $orderItems = [];
            $serverTotal = 0.0;
            foreach ($items as $item) {
                $lockStmt->execute([(int)$item['id']]);
                $row = $lockStmt->fetch();
                $qty = (int)$item['quantity'];
                if (!$row) {
                    throw new RuntimeException('商品“' . $item['name'] . '”已下架，请返回购物车调整');
                }
                if ((int)$row['stock'] < $qty) {
                    throw new RuntimeException('商品“' . $row['name'] . '”库存不足，仅剩 ' . (int)$row['stock'] . ' 件');
                }
                $orderItems[] = [
                    'product_id' => (int)$row['id'],
                    'name'       => $row['name'],
                    'price'      => (float)$row['price'],
                    'quantity'   => $qty,
                ];
                $serverTotal += (float)$row['price'] * $qty;
            }

            $orderNo = generate_order_no();
            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (order_no, user_id, receiver, phone, address, total, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                $orderNo,
                (int)$user['id'],
                $form['receiver'],
                $form['phone'],
                $form['address'],
                $serverTotal,
                '待发货',
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $updateStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($orderItems as $item) {
                $insertItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                ]);
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);
            flash('success', '下单成功，订单号：' . $orderNo);
            redirect('order.php?id=' . $orderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = '结算';
$active = '';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">确认订单</h1>
<p class="page-sub">请核对商品与收货信息后提交订单</p>

<?php if ($errors): ?>
  <div class="flash flash-danger"><?= e($errors[0]) ?></div>
<?php endif; ?>

<div class="cart-layout">
  <div class="form-card" style="max-width:none;margin:0;">
    <h2 style="text-align:left;">收货信息</h2>
    <form method="POST" action="<?= e(url('checkout.php')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>收货人</label>
        <input type="text" name="receiver" value="<?= e($form['receiver']) ?>" maxlength="50" required />
      </div>
      <div class="form-group">
        <label>手机号码</label>
        <input type="tel" name="phone" value="<?= e($form['phone']) ?>" maxlength="11" placeholder="11 位手机号" required />
      </div>
      <div class="form-group">
        <label>收货地址</label>
        <textarea name="address" rows="3" maxlength="255" placeholder="省 / 市 / 区 / 详细地址" required><?= e($form['address']) ?></textarea>
      </div>
      <button class="btn btn-primary btn-lg" type="submit">提交订单</button>
      <a class="btn btn-outline" href="<?= e(url('cart.php')) ?>">返回购物车</a>
    </form>
  </div>

  <aside class="summary-card">
    <h3>商品清单</h3>
    <?php foreach ($items as $item): ?>
      <div class="summary-row">
        <span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span>
        <strong>¥<?= e(format_price($item['subtotal'])) ?></strong>
      </div>
    <?php endforeach; ?>
    <div class="summary-total">
      <span>应付合计</span>
      <span class="price"><?= e(format_price($total)) ?></span>
    </div>
    <p class="form-tip">提交订单后将自动扣减商品库存。</p>
  </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
