<?php
/** 公共头部：导航、登录态、闪存消息 */
if (!defined('APP_BASE')) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? APP_NAME;
$active = $active ?? '';
$user = current_user();
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/extra.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/anime.css')) ?>" />
</head>
<body<?= isset($bodyClass) && $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
  <div class="anime-sky" aria-hidden="true">
    <span class="cloud c1"></span>
    <span class="cloud c2"></span>
    <span class="cloud c3"></span>
    <span class="cloud c4"></span>
    <span class="cloud c5"></span>
  </div>
  <div class="sakura-layer" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
  </div>

  <nav class="navbar">
    <div class="container navbar-inner">
      <a href="<?= e(url('index.php')) ?>" class="logo"><?= e(APP_NAME) ?></a>
      <button class="nav-toggle" id="navToggle" type="button" aria-label="展开菜单">
        <span></span><span></span><span></span>
      </button>
      <div class="nav-links nav-collapse" id="navCollapse">
        <a href="<?= e(url('index.php')) ?>" class="<?= $active === 'home' ? 'active' : '' ?>">首页</a>
        <a href="<?= e(url('index.php#products')) ?>" class="<?= $active === 'products' ? 'active' : '' ?>">全部商品</a>
        <a href="<?= e(url('orders.php')) ?>" class="<?= $active === 'orders' ? 'active' : '' ?>">我的订单</a>
        <?php if ($user && $user['role'] === 'admin'): ?>
          <a href="<?= e(url('admin/index.php')) ?>" class="<?= $active === 'admin' ? 'active' : '' ?>">后台管理</a>
        <?php endif; ?>
      </div>
      <div class="nav-actions">
        <a href="<?= e(url('cart.php')) ?>" class="cart-link" title="购物车">
          🛒<span class="cart-badge"><?= cart_count() ?></span>
        </a>
        <?php if ($user): ?>
          <span class="nav-user">你好，<?= e($user['username']) ?></span>
          <a href="<?= e(url('profile.php')) ?>" class="btn btn-outline btn-sm">个人资料</a>
          <form action="<?= e(url('logout.php')) ?>" method="POST" style="display:inline">
            <?= csrf_field() ?>
            <button class="btn btn-outline btn-sm" type="submit">退出</button>
          </form>
        <?php else: ?>
          <a href="<?= e(url('login.php')) ?>" class="btn btn-outline btn-sm">登录</a>
          <a href="<?= e(url('register.php')) ?>" class="btn btn-primary btn-sm">注册</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="container">
    <?php foreach ($flashes as $item): ?>
      <div class="flash flash-<?= e($item['type']) ?>"><?= e($item['message']) ?></div>
    <?php endforeach; ?>
