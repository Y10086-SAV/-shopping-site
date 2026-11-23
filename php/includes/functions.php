<?php
/**
 * 公共函数：输出转义、URL、闪存消息、CSRF、分页、购物车、图片上传等
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/** HTML 转义 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 生成站内 URL */
function url(string $path = ''): string
{
    return APP_BASE . '/' . ltrim($path, '/');
}

/** 生成静态资源 URL */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** 302 跳转并结束请求 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Cookie 作用路径 */
function cookie_path(): string
{
    return APP_BASE === '' ? '/' : APP_BASE . '/';
}

/** 是否为 POST 请求 */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** 读取 POST 字段并去除首尾空白 */
function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** 读取 GET 字段并去除首尾空白 */
function get(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

// ---------------- 闪存消息（Session 一次性提示） ----------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** 取出并清空全部闪存消息 */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------------- CSRF ----------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** 校验 POST 请求的 CSRF Token */
function csrf_verify(): void
{
    if (!is_post()) {
        return;
    }
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('页面已过期，请返回刷新后重新提交。');
    }
}

// ---------------- 验证码 ----------------

/** 校验验证码（不区分大小写，5 分钟有效） */
function validate_captcha(string $input): bool
{
    $code = $_SESSION['captcha_code'] ?? '';
    $time = (int)($_SESSION['captcha_time'] ?? 0);
    unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

    if ($code === '' || $input === '' || time() - $time > 300) {
        return false;
    }
    return strcasecmp($code, trim($input)) === 0;
}

// ---------------- 展示辅助 ----------------

function format_price(mixed $price): string
{
    return number_format((float)$price, 2, '.', ',');
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts ? date('Y-m-d H:i', $ts) : $datetime;
}

/** 商品图片地址：支持完整 URL、本站相对路径 */
function product_image(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return 'https://picsum.photos/seed/shopping-default/600/600';
    }
    if (preg_match('#^https?://#i', $image) || str_starts_with($image, 'data:')) {
        return $image;
    }
    return url($image);
}

function status_badge_class(string $status): string
{
    return match ($status) {
        '已发货' => 'badge-shipped',
        '已完成' => 'badge-done',
        default  => 'badge-pending',
    };
}

/** 生成订单编号 */
function generate_order_no(): string
{
    return date('YmdHis') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

// ---------------- 分页 ----------------

/**
 * 渲染分页导航
 * @param int   $page       当前页
 * @param int   $totalPages 总页数
 * @param array $query      需要保留的查询参数（如 q / category / sort）
 */
function pagination(int $page, int $totalPages, array $query = []): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $makeUrl = static function (int $p) use ($script, $query): string {
        return $script . '?' . http_build_query(array_merge($query, ['page' => $p]));
    };

    $html = '<nav class="pagination" aria-label="分页">';
    $prevDisabled = $page <= 1;
    $html .= '<a class="page-link' . ($prevDisabled ? ' disabled' : '') . '" href="'
        . ($prevDisabled ? '#' : e($makeUrl($page - 1))) . '">上一页</a>';

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) {
        $html .= '<a class="page-link" href="' . e($makeUrl(1)) . '">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-ellipsis">…</span>';
        }
    }
    for ($p = $start; $p <= $end; $p++) {
        $html .= '<a class="page-link' . ($p === $page ? ' active' : '') . '" href="'
            . e($makeUrl($p)) . '">' . $p . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-ellipsis">…</span>';
        }
        $html .= '<a class="page-link" href="' . e($makeUrl($totalPages)) . '">' . $totalPages . '</a>';
    }

    $nextDisabled = $page >= $totalPages;
    $html .= '<a class="page-link' . ($nextDisabled ? ' disabled' : '') . '" href="'
        . ($nextDisabled ? '#' : e($makeUrl($page + 1))) . '">下一页</a>';
    $html .= '</nav>';

    return $html;
}

// ---------------- 购物车（Session 存储） ----------------

/** 购物车商品数量 */
function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

/** 读取购物车明细（关联商品表，自动忽略已下架商品） */
function cart_items(PDO $pdo): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT id, name, price, stock, image, description
         FROM products WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[(int)$row['id']] = $row;
    }

    $items = [];
    foreach ($cart as $id => $qty) {
        $id = (int)$id;
        if (!isset($products[$id])) {
            unset($_SESSION['cart'][$id]);
            continue;
        }
        $row = $products[$id];
        $quantity = max(1, (int)$qty);
        $row['quantity'] = $quantity;
        $row['subtotal'] = (float)$row['price'] * $quantity;
        $items[] = $row;
    }

    return $items;
}

function cart_total(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += (float)$item['subtotal'];
    }
    return $total;
}

// ---------------- 文件上传 ----------------

/**
 * 保存商品图片
 * @return string 本站相对路径，如 uploads/products/xxx.jpg
 * @throws RuntimeException 校验失败或保存失败
 */
function save_uploaded_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('图片上传失败，错误码：' . (int)($file['error'] ?? -1));
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('图片大小不能超过 2MB');
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('无效的上传文件');
    }

    $info = @getimagesize($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('仅支持 JPG / PNG / GIF / WEBP 格式的图片');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0777, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('上传目录创建失败');
    }

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('图片保存失败');
    }

    return UPLOAD_URL . '/' . $filename;
}

/** 删除本站上传的旧图片（外部 URL 不处理） */
function delete_local_upload(?string $path): void
{
    $path = (string)$path;
    if ($path === '' || !str_starts_with($path, UPLOAD_URL . '/')) {
        return;
    }
    $file = APP_ROOT . '/' . $path;
    if (is_file($file)) {
        @unlink($file);
    }
}
