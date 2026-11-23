<?php
/**
 * 后台商品新增 / 编辑（含图片文件上传）
 */
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

$id = (int)get('id', '0');
$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        flash('danger', '商品不存在');
        redirect('admin/products.php');
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$currentImage = $product['image'] ?? '';

$form = [
    'name'        => $product['name'] ?? '',
    'category_id' => (int)($product['category_id'] ?? ($categories[0]['id'] ?? 0)),
    'price'       => $product['price'] ?? '',
    'stock'       => $product['stock'] ?? 100,
    'image_url'   => ($currentImage && preg_match('#^https?://#i', $currentImage)) ? $currentImage : '',
    'description' => $product['description'] ?? '',
];
$errors = [];
$preview = $currentImage;

if (is_post()) {
    csrf_verify();

    $form = [
        'name'        => post('name'),
        'category_id' => (int)post('category_id', '0'),
        'price'       => post('price'),
        'stock'       => post('stock'),
        'image_url'   => post('image_url'),
        'description' => post('description'),
    ];

    if ($form['name'] === '' || mb_strlen($form['name']) > 120) {
        $errors[] = '商品名称不能为空且不超过 120 个字符';
    }
    $categoryOk = false;
    foreach ($categories as $cat) {
        if ((int)$cat['id'] === $form['category_id']) {
            $categoryOk = true;
            break;
        }
    }
    if (!$categoryOk) {
        $errors[] = '请选择有效的商品分类';
    }
    if (!is_numeric($form['price']) || (float)$form['price'] < 0 || (float)$form['price'] > 99999999.99) {
        $errors[] = '价格必须为 0 - 99999999.99 之间的数字';
    }
    $stock = filter_var($form['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
    if ($stock === false) {
        $errors[] = '库存必须为 0 - 1000000 之间的整数';
    }

    // 处理图片上传（上传文件优先于 URL）
    $uploadedImage = null;
    if (($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $uploadedImage = save_uploaded_image($_FILES['image_file']);
            $preview = $uploadedImage;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $image = $uploadedImage;
        if ($image === null && $form['image_url'] !== '') {
            $image = $form['image_url'];
        }
        if ($image === null) {
            $image = $currentImage !== '' ? $currentImage : null;
        }

        if ($product) {
            $stmt = $pdo->prepare(
                'UPDATE products SET category_id = ?, name = ?, price = ?, stock = ?, image = ?, description = ? WHERE id = ?'
            );
            $stmt->execute([
                $form['category_id'],
                $form['name'],
                (float)$form['price'],
                (int)$stock,
                $image,
                $form['description'],
                $id,
            ]);
            if ($uploadedImage !== null && $currentImage !== '' && $uploadedImage !== $currentImage) {
                delete_local_upload($currentImage);
            }
            flash('success', '商品已更新');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, name, price, stock, image, description) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $form['category_id'],
                $form['name'],
                (float)$form['price'],
                (int)$stock,
                $image,
                $form['description'],
            ]);
            flash('success', '商品已新增');
        }
        redirect('admin/products.php');
    } elseif ($uploadedImage !== null) {
        // 校验未通过时清理刚上传的文件，并恢复原图预览
        delete_local_upload($uploadedImage);
        $preview = $currentImage;
    }
}

$pageTitle = $product ? '编辑商品' : '新增商品';
$active = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title"><?= e($pageTitle) ?></h1>

<div class="admin-nav">
  <a href="<?= e(url('admin/index.php')) ?>">仪表盘</a>
  <a href="<?= e(url('admin/products.php')) ?>" class="active">商品管理</a>
  <a href="<?= e(url('admin/orders.php')) ?>">订单管理</a>
  <a href="<?= e(url('admin/users.php')) ?>">用户管理</a>
</div>

<?php if ($errors): ?>
  <div class="flash flash-danger"><?= e($errors[0]) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:640px;margin:0;">
  <form action="<?= e(url('admin/product_form.php' . ($product ? '?id=' . $id : ''))) ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>商品名称</label>
      <input type="text" name="name" value="<?= e($form['name']) ?>" maxlength="120" required />
    </div>
    <div class="form-group">
      <label>分类</label>
      <select name="category_id" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= (int)$form['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group">
        <label>价格（元）</label>
        <input type="number" name="price" step="0.01" min="0" value="<?= e($form['price']) ?>" required />
      </div>
      <div class="form-group">
        <label>库存</label>
        <input type="number" name="stock" min="0" value="<?= e($form['stock']) ?>" required />
      </div>
    </div>
    <div class="form-group">
      <label>上传商品图片</label>
      <input type="file" id="imageFile" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" />
      <div class="form-tip">支持 JPG / PNG / GIF / WEBP，大小不超过 2MB；上传后自动保存到 uploads/products/。</div>
    </div>
    <div class="form-group">
      <label>或填写图片 URL</label>
      <input type="text" name="image_url" value="<?= e($form['image_url']) ?>" placeholder="https://…" />
      <div class="form-tip">留空则保留原有图片；上传文件优先于 URL。</div>
    </div>
    <div class="form-group">
      <label>当前图片预览</label>
      <div class="upload-preview" id="preview">
        <?php if ($preview): ?>
          <img src="<?= e(product_image($preview)) ?>" alt="商品图片预览" />
        <?php else: ?>
          暂无图片
        <?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label>商品描述</label>
      <textarea name="description" rows="4" maxlength="2000"><?= e($form['description']) ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">保存</button>
    <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-outline">取消</a>
  </form>
</div>

<script>
  // 选择图片后即时预览
  document.getElementById('imageFile')?.addEventListener('change', function (event) {
    var file = event.target.files && event.target.files[0];
    if (!file) return;
    var box = document.getElementById('preview');
    box.innerHTML = '';
    var img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = '待上传图片预览';
    box.appendChild(img);
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
