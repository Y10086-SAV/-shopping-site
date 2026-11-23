<?php
/**
 * 图形验证码（GD 生成，验证码保存在 Session）
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('imagecreatetruecolor')) {
    http_response_code(500);
    exit('GD 扩展未启用，无法生成验证码');
}

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

$_SESSION['captcha_code'] = $code;
$_SESSION['captcha_time'] = time();

$width = 120;
$height = 44;
$image = imagecreatetruecolor($width, $height);
imageantialias($image, true);

$bg = imagecolorallocate($image, 250, 245, 240);
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// 背景噪点
for ($i = 0; $i < 80; $i++) {
    $color = imagecolorallocate($image, random_int(200, 245), random_int(200, 245), random_int(200, 245));
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $color);
}

// 干扰线
for ($i = 0; $i < 4; $i++) {
    $color = imagecolorallocate($image, random_int(140, 220), random_int(140, 220), random_int(140, 220));
    imageline($image, 0, random_int(0, $height), $width, random_int(0, $height), $color);
}

$fontFile = null;
foreach (['C:/Windows/Fonts/arialbd.ttf', 'C:/Windows/Fonts/arial.ttf'] as $candidate) {
    if (is_file($candidate)) {
        $fontFile = $candidate;
        break;
    }
}

for ($i = 0; $i < 4; $i++) {
    $color = imagecolorallocate($image, random_int(30, 120), random_int(30, 120), random_int(30, 120));
    $char = $code[$i];
    if ($fontFile) {
        $angle = random_int(-18, 18);
        $x = 12 + $i * 26;
        $y = random_int(28, 36);
        imagettftext($image, random_int(17, 21), $angle, $x, $y, $color, $fontFile, $char);
    } else {
        imagestring($image, 5, 12 + $i * 26, random_int(12, 20), $char, $color);
    }
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
imagepng($image);
imagedestroy($image);
