<?php
/**
 * 用户注册：表单校验、验证码、密码加密存储
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('index.php');
}

$pdo = db();
$errors = [];
$form = [
    'username' => post('username'),
    'email'    => post('email'),
];

if (is_post()) {
    csrf_verify();

    if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]{3,20}$/u', $form['username'])) {
        $errors['username'] = '用户名需为 3-20 位字母、数字、下划线或中文';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 120) {
        $errors['email'] = '请填写正确的邮箱地址';
    }
    $password = post('password');
    if (mb_strlen($password) < 6 || mb_strlen($password) > 64) {
        $errors['password'] = '密码长度需为 6-64 位';
    }
    if ($password !== post('confirm')) {
        $errors['confirm'] = '两次输入的密码不一致';
    }
    if (!validate_captcha(post('captcha'))) {
        $errors['captcha'] = '验证码错误或已过期';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $check->execute([$form['username'], $form['email']]);
        if ($check->fetchColumn()) {
            $errors['username'] = '用户名或邮箱已被注册';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['username'],
            $form['email'],
            password_hash($password, PASSWORD_DEFAULT),
            'customer',
        ]);
        flash('success', '注册成功，请登录');
        redirect('login.php');
    }
}

$pageTitle = '注册';
$active = '';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <aside class="auth-brand">
      <div class="auth-brand-inner">
        <div class="auth-logo">优选商城</div>
        <p class="auth-jp">初次见面，请多关照</p>
        <h2>加入优选</h2>
        <p>注册会员，开启你的春日好物清单，让每一次逛街都像翻开新的一页。</p>
        <ul class="auth-points">
          <li><span>✓</span>精选好物，一站购齐</li>
          <li><span>✓</span>订单状态实时可查</li>
          <li><span>✓</span>专属客服贴心服务</li>
        </ul>
      </div>
    </aside>

    <div class="auth-form">
      <h1>创建账号</h1>
      <p class="auth-sub">已有账号？<a href="<?= e(url('login.php')) ?>">去登录</a></p>

      <?php if ($errors): ?>
        <div class="flash flash-danger">请检查表单中的提示信息</div>
      <?php endif; ?>

      <form action="<?= e(url('register.php')) ?>" method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>用户名</label>
          <input type="text" name="username" value="<?= e($form['username']) ?>" maxlength="20" placeholder="3-20 位字母、数字或中文" required />
          <?php if (isset($errors['username'])): ?><div class="form-error"><?= e($errors['username']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>邮箱</label>
          <input type="email" name="email" value="<?= e($form['email']) ?>" maxlength="120" placeholder="name@example.com" required />
          <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>密码</label>
          <input type="password" name="password" minlength="6" maxlength="64" placeholder="至少 6 位字符" required />
          <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>确认密码</label>
          <input type="password" name="confirm" minlength="6" maxlength="64" placeholder="再次输入密码" required />
          <?php if (isset($errors['confirm'])): ?><div class="form-error"><?= e($errors['confirm']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label>验证码</label>
          <div class="captcha-row">
            <input type="text" name="captcha" maxlength="4" autocomplete="off" placeholder="输入右侧 4 位字符" required />
            <img class="captcha-img" src="<?= e(url('captcha.php')) ?>" alt="验证码" title="点击刷新"
                 onclick="this.src='<?= e(url('captcha.php')) ?>?t=' + Date.now();" />
          </div>
          <?php if (isset($errors['captcha'])): ?><div class="form-error"><?= e($errors['captcha']) ?></div><?php endif; ?>
          <div class="captcha-hint">看不清？点击图片刷新</div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">注册</button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
