<?php
/**
 * 待支付订单 → 正式订单
 *
 * 提交结算时只写入 pending_orders；
 * 用户在支付页确认后，才创建 orders / order_items、扣减库存、同步分类承接表。
 */
declare(strict_types=1);

require_once __DIR__ . '/categories.php';

/**
 * 确认支付并生成正式订单。
 *
 * @return array{order_id:int,order_no:string,already_paid:bool,total:float}
 */
function pay_pending_order(PDO $pdo, int $pendingId, int $userId): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT * FROM `pending_orders` WHERE `id` = ? AND `user_id` = ? FOR UPDATE'
        );
        $stmt->execute([$pendingId, $userId]);
        $pending = $stmt->fetch();

        if (!$pending) {
            throw new RuntimeException('待支付订单不存在');
        }
        if ($pending['status'] === 'paid' && (int)$pending['order_id'] > 0) {
            $pdo->commit();
            return [
                'order_id'     => (int)$pending['order_id'],
                'order_no'     => (string)$pending['order_no'],
                'already_paid' => true,
                'total'        => (float)$pending['total'],
            ];
        }
        if ($pending['status'] !== 'pending') {
            throw new RuntimeException('该订单已失效，请返回购物车重新提交');
        }

        $items = json_decode((string)$pending['items_json'], true);
        if (!is_array($items) || !$items) {
            throw new RuntimeException('待支付订单商品数据异常');
        }

        $orderItems = [];
        $serverTotal = 0.0;
        $lockStmt = $pdo->prepare(
            'SELECT `id`, `category_id`, `name`, `price`, `stock`
             FROM `products` WHERE `id` = ? FOR UPDATE'
        );

        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $name = trim((string)($item['name'] ?? ''));

            if ($productId <= 0 || $quantity <= 0 || $price < 0) {
                throw new RuntimeException('待支付订单商品数据异常');
            }

            $lockStmt->execute([$productId]);
            $row = $lockStmt->fetch();
            if (!$row) {
                throw new RuntimeException('商品“' . $name . '”已下架，无法完成支付');
            }
            if ((int)$row['stock'] < $quantity) {
                throw new RuntimeException('商品“' . $row['name'] . '”库存不足，仅剩 ' . (int)$row['stock'] . ' 件');
            }

            $orderItems[] = [
                'product_id'  => $productId,
                'category_id' => (int)$row['category_id'],
                'name'        => $name !== '' ? $name : (string)$row['name'],
                'price'       => $price,
                'quantity'    => $quantity,
            ];
            $serverTotal += $price * $quantity;
        }

        $insertOrder = $pdo->prepare(
            'INSERT INTO `orders` (`order_no`, `user_id`, `receiver`, `phone`, `address`, `total`, `status`)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insertOrder->execute([
            (string)$pending['order_no'],
            $userId,
            (string)$pending['receiver'],
            (string)$pending['phone'],
            (string)$pending['address'],
            $serverTotal,
            '待发货',
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $insertItem = $pdo->prepare(
            'INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `quantity`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $updateStock = $pdo->prepare(
            'UPDATE `products` SET `stock` = `stock` - ? WHERE `id` = ? AND `stock` >= ?'
        );

        foreach ($orderItems as $item) {
            $insertItem->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
            ]);

            $updateStock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            if ($updateStock->rowCount() !== 1) {
                throw new RuntimeException('商品库存更新失败，请重试');
            }

            sync_category_product($pdo, $item['category_id'], $item['product_id']);
        }

        $updatePending = $pdo->prepare(
            "UPDATE `pending_orders`
             SET `status` = 'paid', `order_id` = ?, `paid_at` = NOW()
             WHERE `id` = ? AND `status` = 'pending'"
        );
        $updatePending->execute([$orderId, $pendingId]);
        if ($updatePending->rowCount() !== 1) {
            throw new RuntimeException('支付状态更新失败，请重试');
        }

        $pdo->commit();

        return [
            'order_id'     => $orderId,
            'order_no'     => (string)$pending['order_no'],
            'already_paid' => false,
            'total'        => $serverTotal,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
