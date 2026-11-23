<?php
/**
 * 用户登录：验证码 + 密码校验 + Session 登录态 + 记住用户名 Cookie
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('index.php');
}

$pdo = db();
$error = '';
$form = [
    'username' => post('username', remembered_username()),
];

if (is_post()) {
    csrf_verify();

    $password = post('password');
    $captchaOk = validate_captcha(post('captcha'));

    if (!$captchaOk) {
        $error = '验证码错误或已过期';
    } elseif ($form['username'] === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$form['username'], $form['username']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = '用户名或密码错误';
        } else {
            login_user($user);
            if (post('remember') === '1') {
                remember_username($user['username']);
            } else {
                forget_username();
            }
            flash('success', '欢迎回来，' . $user['username']);
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
    }
}

$lastLogin = last_login_cookie();
$pageTitle = '登录';
$active = '';
require __DIR__ . '/includes/header.php';
?>

<div class="form-card">
  <h2>登录</h2>

  <?php if ($lastLogin !== ''): ?>
    <div class="notice">上次登录时间：<?= e($lastLogin) ?>（来自 Cookie）</div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="flash flash-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <form action="<?= e(url('login.php')) ?>" method="POST">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>用户名 / 邮箱</label>
      <input type="text" name="username" value="<?= e($form['username']) ?>" maxlength="120" required />
    </div>
    <div class="form-group">
      <label>密码</label>
      <input type="password" name="password" maxlength="64" required />
    </div>
    <div class="form-group">
      <label>验证码</label>
      <div class="captcha-row">
        <input type="text" name="captcha" maxlength="4" autocomplete="off" placeholder="输入右侧 4 位字符" required />
        <img class="captcha-img" src="<?= e(url('captcha.php')) ?>" alt="验证码" title="点击刷新"
             onclick="this.src='<?= e(url('captcha.php')) ?>?t=' + Date.now();" />
      </div>
      <div class="captcha-hint">看不清？点击图片刷新</div>
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" id="remember" name="remember" value="1"
             style="width:auto;" <?= remembered_username() !== '' ? 'checked' : '' ?> />
      <label for="remember" style="margin:0;font-weight:400;">记住用户名（Cookie 保存 30 天）</label>
    </div>
    <button class="btn btn-primary" type="submit" style="width:100%;">登录</button>
  </form>
  <div class="form-footer">还没有账号？<a href="<?= e(url('register.php')) ?>">立即注册</a></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
