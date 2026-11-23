<?php
/**
 * 退出登录（仅接受 POST，防止 CSRF 强制退出）
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    redirect('index.php');
}

csrf_verify();
logout_user();

// 重建一个空会话用于展示退出提示
session_start();
session_regenerate_id(true);
flash('success', '已安全退出');
redirect('index.php');
