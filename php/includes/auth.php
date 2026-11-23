<?php
/**
 * 认证与授权：Session 登录态、Cookie 记忆用户名、权限校验
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** 当前登录用户（来自 Session），未登录返回 null */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

/** 写入登录态，并刷新 Session ID 防止会话固定攻击 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => (int)$user['id'],
        'username' => (string)$user['username'],
        'email'    => (string)$user['email'],
        'role'     => (string)$user['role'],
    ];

    // Cookie 会话技术①：记录最后登录时间（30 天）
    setcookie('last_login_at', date('Y-m-d H:i:s'), [
        'expires'  => time() + 30 * 86400,
        'path'     => cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** 退出登录：清空 Session，保留“记住用户名”Cookie */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/** 记住用户名（Cookie 会话技术②） */
function remember_username(string $username): void
{
    setcookie('remember_username', $username, [
        'expires'  => time() + 30 * 86400,
        'path'     => cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function forget_username(): void
{
    setcookie('remember_username', '', [
        'expires'  => time() - 3600,
        'path'     => cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** 读取 Cookie 中记住的用户名 */
function remembered_username(): string
{
    return isset($_COOKIE['remember_username']) ? (string)$_COOKIE['remember_username'] : '';
}

/** 读取 Cookie 中记录的最后登录时间 */
function last_login_cookie(): string
{
    return isset($_COOKIE['last_login_at']) ? (string)$_COOKIE['last_login_at'] : '';
}

/** 要求登录 */
function require_login(): void
{
    if (current_user() === null) {
        flash('warning', '请先登录后再操作');
        redirect('login.php');
    }
}

/** 要求管理员权限 */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash('danger', '没有管理员权限');
        redirect('index.php');
    }
}
