<?php
/**
 * 分类与“分类承接表”辅助函数
 *
 * 设计说明：
 * - products 仍是全站商品的唯一主表，前台查询、购物车、订单都依赖它；
 * - 每个分类额外对应一张 products_cat_<分类ID> 表，用于承接该分类的商品；
 * - 承接表以 products.id 作为主键，与 products 保持 1:1，并通过外键级联删除；
 * - 新增分类、保存商品、扣减库存、删除商品时都会同步对应承接表。
 */
declare(strict_types=1);

/** 由分类 ID 生成安全的承接表名（表名只包含整数 ID，避免注入） */
function category_table_name(int $categoryId): string
{
    return 'products_cat_' . $categoryId;
}

/** 判断某个分类的承接表是否已存在 */
function category_table_exists(PDO $pdo, int $categoryId): bool
{
    if ($categoryId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([category_table_name($categoryId)]);
    return (int)$stmt->fetchColumn() > 0;
}

/** 把某分类已有的全部商品回填到承接表（可重复执行） */
function backfill_category_table(PDO $pdo, int $categoryId): void
{
    if ($categoryId <= 0 || !category_table_exists($pdo, $categoryId)) {
        return;
    }
    $table = category_table_name($categoryId);
    $stmt = $pdo->prepare(
        "INSERT INTO `{$table}` (`id`, `name`, `price`, `stock`, `image`, `description`)
         SELECT `id`, `name`, `price`, `stock`, `image`, `description`
         FROM `products` WHERE `category_id` = ?
         ON DUPLICATE KEY UPDATE
           `name` = VALUES(`name`),
           `price` = VALUES(`price`),
           `stock` = VALUES(`stock`),
           `image` = VALUES(`image`),
           `description` = VALUES(`description`)"
    );
    $stmt->execute([$categoryId]);
}

/**
 * 确保分类承接表存在；不存在时创建并回填该分类现有商品。
 *
 * @param bool $forceBackfill 表已存在时是否也重新回填（安装/手动同步时使用）
 */
function ensure_category_table(PDO $pdo, int $categoryId, string $categoryName, bool $forceBackfill = false): string
{
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('无效的分类 ID');
    }

    $table = category_table_name($categoryId);
    if (strlen($table) > 64) {
        throw new RuntimeException('分类承接表名过长');
    }

    $existed = category_table_exists($pdo, $categoryId);
    $comment = $pdo->quote('分类「' . $categoryName . '」商品承接表');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `{$table}` (
          `id`          INT UNSIGNED   NOT NULL                COMMENT '对应 products.id',
          `name`        VARCHAR(120)   NOT NULL                COMMENT '商品名称',
          `price`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00   COMMENT '价格（元）',
          `stock`       INT UNSIGNED   NOT NULL DEFAULT 0      COMMENT '库存',
          `image`       VARCHAR(255)   DEFAULT NULL            COMMENT '图片地址',
          `description` TEXT                                   COMMENT '商品描述',
          `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
          PRIMARY KEY (`id`),
          KEY `idx_{$table}_name` (`name`),
          CONSTRAINT `fk_{$table}`
            FOREIGN KEY (`id`) REFERENCES `products` (`id`)
            ON UPDATE CASCADE ON DELETE CASCADE,
          CONSTRAINT `chk_{$table}_price` CHECK (`price` >= 0),
          CONSTRAINT `chk_{$table}_stock` CHECK (`stock` >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT={$comment}"
    );

    if (!$existed || $forceBackfill) {
        backfill_category_table($pdo, $categoryId);
    }

    return $table;
}

/** 让承接表中的单条商品与 products 保持同步（只做 DML，可在事务中调用） */
function sync_category_product(PDO $pdo, int $categoryId, int $productId): bool
{
    if ($categoryId <= 0 || $productId <= 0 || !category_table_exists($pdo, $categoryId)) {
        return false;
    }
    $table = category_table_name($categoryId);
    $stmt = $pdo->prepare(
        "INSERT INTO `{$table}` (`id`, `name`, `price`, `stock`, `image`, `description`)
         SELECT `id`, `name`, `price`, `stock`, `image`, `description`
         FROM `products` WHERE `id` = ? AND `category_id` = ?
         ON DUPLICATE KEY UPDATE
           `name` = VALUES(`name`),
           `price` = VALUES(`price`),
           `stock` = VALUES(`stock`),
           `image` = VALUES(`image`),
           `description` = VALUES(`description`)"
    );
    $stmt->execute([$productId, $categoryId]);
    return true;
}

/** 从承接表移除一条商品（移动分类或删除商品时使用） */
function remove_category_product(PDO $pdo, int $categoryId, int $productId): void
{
    if ($categoryId <= 0 || $productId <= 0 || !category_table_exists($pdo, $categoryId)) {
        return;
    }
    $table = category_table_name($categoryId);
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `id` = ?");
    $stmt->execute([$productId]);
}

/**
 * 创建分类；若同名分类已存在则直接复用。
 * 新分类会自动创建 products_cat_<ID> 承接表。
 *
 * @return array{id:int,name:string,table:string,created:bool}
 */
function create_category(PDO $pdo, string $name): array
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name) > 50) {
        throw new InvalidArgumentException('分类名称需为 1-50 个字符');
    }
    if (preg_match('/[\x00-\x1F\x7F]/u', $name)) {
        throw new InvalidArgumentException('分类名称不能包含控制字符');
    }

    $stmt = $pdo->prepare('SELECT `id`, `name` FROM `categories` WHERE `name` = ? LIMIT 1');
    $stmt->execute([$name]);
    $existing = $stmt->fetch();
    if ($existing) {
        $table = ensure_category_table($pdo, (int)$existing['id'], (string)$existing['name']);
        return [
            'id'      => (int)$existing['id'],
            'name'    => (string)$existing['name'],
            'table'   => $table,
            'created' => false,
        ];
    }

    $sortOrder = (int)$pdo->query('SELECT COALESCE(MAX(`sort_order`), 0) + 10 FROM `categories`')->fetchColumn();
    try {
        $insert = $pdo->prepare('INSERT INTO `categories` (`name`, `sort_order`) VALUES (?, ?)');
        $insert->execute([$name, $sortOrder]);
    } catch (PDOException $e) {
        // 并发下同名分类可能刚被创建，复用已存在分类
        if ((string)$e->getCode() === '23000') {
            $stmt->execute([$name]);
            $existing = $stmt->fetch();
            if ($existing) {
                $table = ensure_category_table($pdo, (int)$existing['id'], (string)$existing['name']);
                return [
                    'id'      => (int)$existing['id'],
                    'name'    => (string)$existing['name'],
                    'table'   => $table,
                    'created' => false,
                ];
            }
        }
        throw $e;
    }

    $id = (int)$pdo->lastInsertId();
    try {
        $table = ensure_category_table($pdo, $id, $name);
    } catch (Throwable $e) {
        // 建表失败时清理刚创建的分类，避免出现“有分类但没有承接表”
        if (category_table_exists($pdo, $id)) {
            $pdo->exec('DROP TABLE IF EXISTS `' . category_table_name($id) . '`');
        }
        $pdo->prepare('DELETE FROM `categories` WHERE `id` = ?')->execute([$id]);
        throw $e;
    }

    return [
        'id'      => $id,
        'name'    => $name,
        'table'   => $table,
        'created' => true,
    ];
}

/** 为全部现有分类创建并回填承接表（安装、手动同步时使用） */
function sync_all_category_tables(PDO $pdo): array
{
    $categories = $pdo->query('SELECT `id`, `name` FROM `categories` ORDER BY `id`')->fetchAll();
    $tables = [];
    foreach ($categories as $category) {
        $tables[] = ensure_category_table(
            $pdo,
            (int)$category['id'],
            (string)$category['name'],
            true
        );
    }
    return $tables;
}
