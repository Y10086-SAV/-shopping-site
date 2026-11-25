<?php
/**
 * 待支付订单支付页
 * 展示二维码；确认支付后才生成正式订单并展示订单信息。
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/payments.php';

require_login();
$pdo = db();
$user = current_user();
$pendingId = (int)get('id', '0');

$stmt = $pdo->prepare(
    'SELECT * FROM `pending_orders` WHERE `id` = ? AND `user_id` = ? LIMIT 1'
);
$stmt->execute([$pendingId, (int)$user['id']]);
$pending = $stmt->fetch();

if (!$pending) {
    flash('danger', '待支付订单不存在或无权查看');
    redirect('cart.php');
}
if ($pending['status'] === 'paid' && (int)$pending['order_id'] > 0) {
    flash('success', '该订单已支付');
    redirect('order.php?id=' . (int)$pending['order_id']);
}
if ($pending['status'] !== 'pending') {
    flash('warning', '该待支付订单已失效，请重新提交订单');
    redirect('cart.php');
}

$errors = [];
if (is_post()) {
    csrf_verify();
    try {
        $result = pay_pending_order($pdo, $pendingId, (int)$user['id']);
        unset($_SESSION['cart']);
        flash('success', '支付成功，订单已生成：' . $result['order_no']);
        redirect('order.php?id=' . $result['order_id']);
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$items = json_decode((string)$pending['items_json'], true);
$itemCount = 0;
if (is_array($items)) {
    foreach ($items as $item) {
        $itemCount += max(0, (int)($item['quantity'] ?? 0));
    }
}

$pageTitle = '支付订单';
$active = '';
$bodyClass = 'pay-open';
require __DIR__ . '/includes/header.php';
?>

<div class="pay-page">
  <div class="pay-overlay">
    <section class="pay-modal" role="dialog" aria-modal="true" aria-labelledby="payTitle">
      <span class="pay-status">等待支付</span>
      <p class="pay-eyebrow">WECHAT PAY · 演示支付</p>
      <h1 id="payTitle">扫码支付</h1>
      <p class="pay-sub">
        订单 <?= e($pending['order_no']) ?><br />
        共 <?= $itemCount ?> 件商品
      </p>

      <div class="pay-amount">
        <small>应付金额</small>
        <strong>¥<?= e(format_price($pending['total'])) ?></strong>
      </div>

      <img class="pay-qr" src="<?= e(asset('img/payment-qr.png')) ?>" alt="微信支付二维码" />

      <p class="pay-note">
        请使用微信扫码支付。演示模式下无需真实付款，点击下方按钮即视为支付成功，不校验实际支付金额。
      </p>

      <?php if ($errors): ?>
        <div class="flash flash-danger" style="text-align:left;"><?= e($errors[0]) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= e(url('pay.php?id=' . $pendingId)) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-block btn-lg" type="submit">我已完成支付</button>
        <a class="btn btn-outline btn-block" href="<?= e(url('cart.php')) ?>">返回购物车</a>
      </form>

      <p class="pay-footnote">支付成功后将生成订单并扣减库存，之后可在「我的订单」查看。</p>
    </section>
  </div>
</div>

<script>
  document.body.style.overflow = 'hidden';
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
