<?php
/**
 * 后台删除商品（POST + CSRF）
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

if (!is_post()) {
    redirect('admin/products.php');
}
csrf_verify();

$pdo = db();
$id = (int)post('id', '0');

$stmt = $pdo->prepare('SELECT id, name, image FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flash('danger', '商品不存在或已被删除');
    redirect('admin/products.php');
}

$delete = $pdo->prepare('DELETE FROM products WHERE id = ?');
$delete->execute([$id]);

delete_local_upload($product['image']);

flash('success', '商品已删除：' . $product['name']);
redirect('admin/products.php');
