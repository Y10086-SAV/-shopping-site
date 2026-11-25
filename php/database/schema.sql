-- =============================================================
-- 优选商城 PHP 版 · 数据库设计脚本
-- 数据库：MySQL 8.0+  字符集：utf8mb4
-- 说明：包含数据库、数据表（主键 / 外键 / 唯一 / 检查约束）与种子数据
-- 可执行方式：
--   1) 浏览器访问 php/install.php 自动安装
--   2) 或命令行：mysql -u root -p < php/database/schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS `shopping_php`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `shopping_php`;

-- -------------------------------------------------------------
-- 1. 商品分类表
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类主键',
  `name`       VARCHAR(50)  NOT NULL                COMMENT '分类名称',
  `sort_order` INT          NOT NULL DEFAULT 0      COMMENT '排序值（越小越靠前）',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品分类表';

-- -------------------------------------------------------------
-- 2. 用户表
--    密码使用 password_hash() 加密存储，不保存明文
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户主键',
  `username`      VARCHAR(30)  NOT NULL                COMMENT '用户名（唯一）',
  `email`         VARCHAR(120) NOT NULL                COMMENT '邮箱（唯一）',
  `password_hash` VARCHAR(255) NOT NULL                COMMENT '密码哈希',
  `role`          ENUM('admin','customer') NOT NULL DEFAULT 'customer' COMMENT '角色',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- -------------------------------------------------------------
-- 3. 商品表
--    外键：category_id -> categories.id
--    规则：删除分类时若仍有商品则拒绝（RESTRICT）
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT COMMENT '商品主键',
  `category_id` INT UNSIGNED   NOT NULL                COMMENT '所属分类',
  `name`        VARCHAR(120)   NOT NULL                COMMENT '商品名称',
  `price`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00   COMMENT '价格（元）',
  `stock`       INT UNSIGNED   NOT NULL DEFAULT 0      COMMENT '库存',
  `image`       VARCHAR(255)   DEFAULT NULL            COMMENT '图片地址（URL 或本站相对路径）',
  `description` TEXT                                   COMMENT '商品描述',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_name` (`name`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_products_price` CHECK (`price` >= 0),
  CONSTRAINT `chk_products_stock` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';

-- -------------------------------------------------------------
-- 分类承接表（应用自动维护，不在本脚本中固定创建）
-- 新增分类时，后台会自动创建 products_cat_<分类ID>：
--   - 字段与 products 对齐，id 对应 products.id，一分类一表；
--   - 外键指向 products.id，商品删除时级联删除；
--   - 新增/编辑商品、下单扣库存时同步更新对应承接表；
--   - 安装脚本会为现有分类统一创建并回填承接表。
-- -------------------------------------------------------------

-- -------------------------------------------------------------
-- 4. 订单表
--    外键：user_id -> users.id（用户注销时级联删除其订单）
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '订单主键',
  `order_no`   VARCHAR(32)   NOT NULL                COMMENT '订单编号（唯一）',
  `user_id`    INT UNSIGNED  NOT NULL                COMMENT '下单用户',
  `receiver`   VARCHAR(50)   NOT NULL                COMMENT '收货人',
  `phone`      VARCHAR(20)   NOT NULL                COMMENT '联系电话',
  `address`    VARCHAR(255)  NOT NULL                COMMENT '收货地址',
  `total`      DECIMAL(10,2) NOT NULL DEFAULT 0.00   COMMENT '订单总额',
  `status`     ENUM('待发货','已发货','已完成') NOT NULL DEFAULT '待发货' COMMENT '订单状态',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下单时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_no` (`order_no`),
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `chk_orders_total` CHECK (`total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表';

-- -------------------------------------------------------------
-- 5. 订单明细表
--    外键：order_id -> orders.id（删除订单级联删除明细）
--          product_id -> products.id（删除商品后置空，保留历史快照）
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '明细主键',
  `order_id`     INT UNSIGNED  NOT NULL                COMMENT '所属订单',
  `product_id`   INT UNSIGNED  DEFAULT NULL            COMMENT '商品（可能已删除）',
  `product_name` VARCHAR(120)  NOT NULL                COMMENT '下单时的商品名称快照',
  `price`        DECIMAL(10,2) NOT NULL                COMMENT '下单时的单价快照',
  `quantity`     INT UNSIGNED  NOT NULL                COMMENT '购买数量',
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `fk_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `chk_items_price` CHECK (`price` >= 0),
  CONSTRAINT `chk_items_qty` CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单明细表';

-- -------------------------------------------------------------
-- 6. 待支付订单表
--    提交结算后先写入本表，支付成功后才生成正式 orders / order_items
--    外键：user_id -> users.id；order_id -> orders.id（支付后回填）
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pending_orders` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '待支付订单主键',
  `order_no`   VARCHAR(32)   NOT NULL                COMMENT '订单编号（支付后复用）',
  `user_id`    INT UNSIGNED  NOT NULL                COMMENT '下单用户',
  `receiver`   VARCHAR(50)   NOT NULL                COMMENT '收货人',
  `phone`      VARCHAR(20)   NOT NULL                COMMENT '联系电话',
  `address`    VARCHAR(255)  NOT NULL                COMMENT '收货地址',
  `total`      DECIMAL(10,2) NOT NULL DEFAULT 0.00   COMMENT '待支付金额',
  `items_json` TEXT          NOT NULL                COMMENT '商品快照 JSON',
  `status`     ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending' COMMENT '支付状态',
  `order_id`   INT UNSIGNED  DEFAULT NULL            COMMENT '支付后生成的正式订单',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `paid_at`    DATETIME DEFAULT NULL                 COMMENT '支付时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pending_orders_no` (`order_no`),
  KEY `idx_pending_orders_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_pending_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_pending_orders_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `chk_pending_orders_total` CHECK (`total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='待支付订单表';

-- -------------------------------------------------------------
-- 种子数据：分类
-- -------------------------------------------------------------
INSERT IGNORE INTO `categories` (`id`, `name`, `sort_order`) VALUES
  (1, '手机数码', 10),
  (2, '电脑办公', 20),
  (3, '家用电器', 30),
  (4, '服饰鞋包', 40),
  (5, '美妆个护', 50),
  (6, '食品生鲜', 60);

-- -------------------------------------------------------------
-- 种子数据：默认管理员 admin / admin123
-- -------------------------------------------------------------
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
  ('admin', 'admin@example.com', '$2y$12$B.YuQ4cOG8vlHI4qXAoUM.iLPsgw2w56vxNlbdLD2viaEoDlukewm', 'admin');

-- -------------------------------------------------------------
-- 种子数据：商品
-- -------------------------------------------------------------
INSERT IGNORE INTO `products` (`id`, `category_id`, `name`, `price`, `stock`, `image`, `description`) VALUES
  (1, 1, '无线蓝牙耳机 Pro', 299.00, 120, 'https://picsum.photos/seed/earbuds/600/600', '主动降噪，30 小时续航，支持快充，佩戴舒适。'),
  (2, 1, '智能手表 S8', 1299.00, 60, 'https://picsum.photos/seed/watch/600/600', '全天候健康监测，血氧/心率/睡眠，5ATM 防水。'),
  (3, 1, '旗舰智能手机 X5', 4599.00, 40, 'https://picsum.photos/seed/phone/600/600', '1 亿像素主摄，6.7 英寸 OLED，5000mAh 大电池。'),
  (4, 2, '轻薄笔记本电脑 14', 5499.00, 30, 'https://picsum.photos/seed/laptop/600/600', '2.8K 高刷屏，16G 内存 + 512G 固态，1.4kg 轻至随身。'),
  (5, 2, '机械键盘 87 键', 399.00, 200, 'https://picsum.photos/seed/keyboard/600/600', '热插拔轴体，RGB 背光，全键无冲。'),
  (6, 2, '4K 显示器 27 英寸', 1899.00, 45, 'https://picsum.photos/seed/monitor/600/600', 'IPS 面板，99% sRGB，Type-C 一线连。'),
  (7, 3, '扫地机器人', 2199.00, 55, 'https://picsum.photos/seed/vacuum/600/600', '激光导航，自动集尘，扫拖一体。'),
  (8, 3, '空气净化器', 899.00, 70, 'https://picsum.photos/seed/airpurifier/600/600', '除醛除菌，CADR 500，静音运行。'),
  (9, 4, '纯棉 T 恤', 79.00, 500, 'https://picsum.photos/seed/tshirt/600/600', '100% 纯棉，透气亲肤，多色可选。'),
  (10, 4, '经典休闲运动鞋', 349.00, 150, 'https://picsum.photos/seed/shoes/600/600', '缓震鞋底，轻便透气，百搭款式。'),
  (11, 5, '保湿面霜 50g', 159.00, 300, 'https://picsum.photos/seed/cream/600/600', '深层锁水，修护屏障，敏感肌可用。'),
  (12, 6, '有机坚果礼盒', 129.00, 400, 'https://picsum.photos/seed/nuts/600/600', '每日坚果 30 袋装，多种坚果科学配比。');
