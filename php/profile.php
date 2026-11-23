<?php
/**
 * 个人资料：编辑用户名 / 邮箱、修改密码
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$pdo = db();
$current = current_user();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$current['id']]);
$user = $stmt->fetch();

if (!$user) {
    logout_user();
    redirect('login.php');
}

$errors = [];

if (is_post()) {
    csrf_verify();
    $action = post('action');

    if ($action === 'password') {
        $oldPassword = post('current_password');
        $newPassword = post('new_password');

        if (!password_verify($oldPassword, $user['password_hash'])) {
            $errors[] = '当前密码不正确';
        }
        if (mb_strlen($newPassword) < 6 || mb_strlen($newPassword) > 64) {
            $errors[] = '新密码长度需为 6-64 位';
        }
        if ($newPassword !== post('confirm_password')) {
            $errors[] = '两次输入的新密码不一致';
        }

        if (!$errors) {
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$user['id']]);
            session_regenerate_id(true);
            flash('success', '密码修改成功');
            redirect('profile.php');
        }
    } else {
        $username = post('username');
        $email = post('email');

        if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]{3,20}$/u', $username)) {
            $errors[] = '用户名需为 3-20 位字母、数字、下划线或中文';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
            $errors[] = '请填写正确的邮箱地址';
        }
        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
            $check->execute([$username, $email, (int)$user['id']]);
            if ($check->fetchColumn()) {
                $errors[] = '用户名或邮箱已被其他账号使用';
            }
        }

        if (!$errors) {
            $update = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
            $update->execute([$username, $email, (int)$user['id']]);

            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;

            if (remembered_username() !== '' && remembered_username() === $user['username']) {
                remember_username($username);
            }

            flash('success', '个人资料已更新');
            redirect('profile.php');
        }
    }

    // 更新为数据库中的最新值用于展示
    $stmt->execute([(int)$user['id']]);
    $user = $stmt->fetch();
}

$pageTitle = '个人资料';
$active = '';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">个人资料</h1>
<p class="page-sub">管理你的账号信息与登录密码</p>

<?php if ($errors): ?>
  <div class="flash flash-danger"><?= e($errors[0]) ?></div>
<?php endif; ?>

<div class="cart-layout">
  <div>
    <div class="form-card" style="max-width:none;margin:0 0 22px;">
      <h2 style="text-align:left;">基本信息</h2>
      <form method="POST" action="<?= e(url('profile.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile" />
        <div class="form-group">
          <label>用户名</label>
          <input type="text" name="username" value="<?= e($user['username']) ?>" maxlength="20" required />
        </div>
        <div class="form-group">
          <label>邮箱</label>
          <input type="email" name="email" value="<?= e($user['email']) ?>" maxlength="120" required />
        </div>
        <button class="btn btn-primary" type="submit">保存资料</button>
      </form>
    </div>

    <div class="form-card" style="max-width:none;margin:0;">
      <h2 style="text-align:left;">修改密码</h2>
      <form method="POST" action="<?= e(url('profile.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password" />
        <div class="form-group">
          <label>当前密码</label>
          <input type="password" name="current_password" maxlength="64" required />
        </div>
        <div class="form-group">
          <label>新密码</label>
          <input type="password" name="new_password" minlength="6" maxlength="64" required />
        </div>
        <div class="form-group">
          <label>确认新密码</label>
          <input type="password" name="confirm_password" minlength="6" maxlength="64" required />
        </div>
        <button class="btn btn-outline" type="submit">更新密码</button>
      </form>
    </div>
  </div>

  <aside class="summary-card">
    <h3>账号信息</h3>
    <div class="summary-row"><span>用户 ID</span><strong><?= (int)$user['id'] ?></strong></div>
    <div class="summary-row"><span>角色</span>
      <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer' ?>">
        <?= $user['role'] === 'admin' ? '管理员' : '顾客' ?>
      </span>
    </div>
    <div class="summary-row"><span>注册时间</span><strong><?= e(format_datetime($user['created_at'])) ?></strong></div>
    <div class="summary-row"><span>最后登录</span><strong><?= e(last_login_cookie() ?: '本次首次登录') ?></strong></div>
    <p class="form-tip">
      “最后登录”与“记住用户名”均通过 Cookie 保存，密码使用 password_hash() 加密后存入 MySQL。
    </p>
  </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
