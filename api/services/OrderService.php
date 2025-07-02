<?php
require_once __DIR__.'/BaseService.php';

class OrderService extends BaseService {
    public function __construct() {
        parent::__construct();
    }

    public function getAllOrders($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT id, user_id, order_number, status, total_amount, created_at, updated_at FROM orders ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderById($id) {
        $stmt = $this->pdo->prepare("SELECT id, user_id, order_number, status, total_amount, created_at, updated_at FROM orders WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createOrder($data) {
        $userId = $data['user_id'];
        $items = $data['items'];

        $this->pdo->beginTransaction();

        try {
            // 1. Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            if ($stmt->fetch() === false) {
                throw new Exception("User not found.", 404);
            }

            // 2. Get all product details in one query for efficiency
            $productIds = array_map(fn($item) => $item['product_id'], $items);
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stmt = $this->pdo->prepare("SELECT id, price, stock FROM products WHERE id IN ($placeholders)");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $productsById = array_column($products, null, 'id');

            $totalAmount = 0;

            // 3. Validate products and calculate total amount
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                if (!isset($productsById[$productId])) {
                    throw new Exception("Product with ID $productId not found.", 422);
                }

                $product = $productsById[$productId];
                if ($product['stock'] < $quantity) {
                    throw new Exception("Insufficient stock for product ID $productId.", 422);
                }
                $totalAmount += $product['price'] * $quantity;
            }

            // 4. Create the order (Phase 1: Insert with a temporary value)
            $stmt = $this->pdo->prepare(
                "INSERT INTO orders (user_id, order_number, status, total_amount, created_at, updated_at) VALUES (:user_id, :order_number, :status, :total_amount, NOW(), NOW())"
            );
            $stmt->execute([
                ':user_id' => $userId,
                // Using a temporary unique value that will be updated immediately.
                ':order_number' => 'TEMP-' . uniqid(),
                ':status' => 'pending',
                ':total_amount' => $totalAmount
            ]);
            $orderId = $this->pdo->lastInsertId();

            // 4.1. Generate the final order number and update the record
            $finalOrderNumber = 'ORD' . $orderId;
            $updateStmt = $this->pdo->prepare("UPDATE orders SET order_number = :order_number WHERE id = :id");
            $updateStmt->execute([
                ':order_number' => $finalOrderNumber,
                ':id' => $orderId
            ]);

            // 5. Create order items and update stock
            $itemStmt = $this->pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal)"
            );
            $stockStmt = $this->pdo->prepare(
                "UPDATE products SET stock = stock - :quantity WHERE id = :product_id"
            );

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $product = $productsById[$productId];
                $subtotal = $product['price'] * $quantity;

                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':quantity' => $quantity,
                    ':unit_price' => $product['price'],
                    ':subtotal' => $subtotal
                ]);

                $stockStmt->execute([
                    ':quantity' => $quantity,
                    ':product_id' => $productId
                ]);
            }

            $this->pdo->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateOrderStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
        return $stmt->rowCount();
    }

    public function getOrderStats() {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
             FROM orders"
        );
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_orders' => (int) $stats['total_orders'],
            'total_revenue' => (float) ($stats['total_revenue'] ?? 0),
            'today_orders' => (int) $stats['today_orders'],
            'today_revenue' => (float) ($stats['today_revenue'] ?? 0)
        ];
    }
}