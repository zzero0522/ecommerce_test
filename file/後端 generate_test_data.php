<?php
/**
 * 電商訂單管理系統 - 測試資料生成腳本
 *
 * 使用方法：
 * php generate_test_data.php
 *
 * 此腳本會生成：
 * - 200 個用戶
 * - 100 個商品
 * - 1000 筆訂單
 * - 約 2000 筆訂單明細
 */

// 資料庫連線設定
$host = 'localhost';
$dbname = 'ecommerce_test';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "資料庫連線成功\n";
} catch (PDOException $e) {
    die("資料庫連線失敗: ".$e->getMessage()."\n");
}

// 清空現有資料
echo "清空現有資料...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE order_items");
$pdo->exec("TRUNCATE TABLE orders");
$pdo->exec("TRUNCATE TABLE products");
$pdo->exec("TRUNCATE TABLE users");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// 生成用戶資料
echo "生成用戶資料...\n";
generateUsers($pdo);

// 生成商品資料
echo "生成商品資料...\n";
generateProducts($pdo);

// 生成訂單資料
echo "生成訂單和訂單明細資料...\n";
generateOrders($pdo);

echo "測試資料生成完成！\n";
showStatistics($pdo);

/**
 * 生成用戶資料
 */
function generateUsers($pdo) {
    $firstNames = ['張', '李', '王', '陳', '林', '黃', '周', '吳', '徐', '朱', '馬', '胡', '郭', '何', '高'];
    $lastNames = ['志明', '美華', '小明', '雅婷', '建國', '淑芬', '俊傑', '麗華', '文雄', '秀英', '偉強', '玉蘭', '明智', '惠美', '國強'];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, created_at, updated_at) VALUES (?, ?, ?, ?)");

    for ($i = 1; $i <= 200; $i++) {
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $name = $firstName.$lastName;
        $email = "user{$i}@example.com";
        $timestamp = '2024-01-01 10:00:00';

        $stmt->execute([$name, $email, $timestamp, $timestamp]);
    }

    echo "已生成 200 個用戶\n";
}

/**
 * 生成商品資料
 */
function generateProducts($pdo) {
    $products = [
        ['iPhone 15 Pro', 35900, 150],
        ['Samsung Galaxy S24', 28900, 200],
        ['MacBook Pro 14吋', 68900, 80],
        ['Dell XPS 13', 45900, 120],
        ['Sony WH-1000XM5 耳機', 9900, 300],
        ['AirPods Pro', 7490, 250],
        ['Nike Air Max 90', 3200, 180],
        ['Adidas Ultraboost 22', 5400, 160],
        ['Uniqlo 純棉T恤', 590, 500],
        ['Zara 休閒外套', 1990, 220],
        ['H&M 牛仔褲', 1290, 180],
        ['阿里山高山茶', 1200, 300],
        ['藍山咖啡豆', 800, 150],
        ['鐵觀音茶葉', 650, 280],
        ['哥倫比亞咖啡豆', 720, 200],
        ['SK-II 神仙水', 8900, 100],
        ['蘭蔻小黑瓶', 3200, 150],
        ['資生堂洗髮精', 650, 300],
        ['Oral-B 電動牙刷', 2890, 120],
        ['Chanel No.5 香水', 4200, 80],
        ['羅技 MX Master 3 滑鼠', 2990, 200],
        ['Cherry MX 機械鍵盤', 4500, 100],
        ['LG 27吋 4K 顯示器', 12900, 60],
        ['SanDisk 256GB 隨身碟', 890, 400],
        ['Anker 20000mAh 行動電源', 1590, 250]
    ];

    // 生成更多商品達到100個
    $categories = ['手機', '筆電', '耳機', '服飾', '咖啡', '茶葉', '護膚品', '3C周邊', '食品', '生活用品'];

    $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");

    // 插入預定義商品
    foreach ($products as $product) {
        $timestamp = '2024-01-01 10:00:00';
        $stmt->execute([$product[0], $product[1], $product[2], $timestamp, $timestamp]);
    }

    // 生成剩餘商品
    for ($i = count($products) + 1; $i <= 100; $i++) {
        $category = $categories[array_rand($categories)];
        $name = "{$category}商品-{$i}";
        $price = rand(100, 10000);
        $stock = rand(50, 500);
        $timestamp = '2024-01-01 10:00:00';

        $stmt->execute([$name, $price, $stock, $timestamp, $timestamp]);
    }

    echo "已生成 100 個商品\n";
}

/**
 * 生成訂單資料
 */
function generateOrders($pdo) {
    $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $statusWeights = [0.1, 0.15, 0.2, 0.5, 0.05]; // delivered 最多

    $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, status, total_amount, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");

    for ($i = 1; $i <= 1000; $i++) {
        $userId = rand(1, 200);

        // 根據權重選擇狀態
        $status = getWeightedStatus($statuses, $statusWeights);

        // 生成訂單日期
        $orderDate = generateOrderDate($i);

        // 訂單編號
        $orderNumber = 'ORD'.str_pad($i, 6, '0', STR_PAD_LEFT);

        // 生成訂單項目
        $itemCount = rand(1, 3); // 1-3個商品
        $selectedProducts = [];
        $totalAmount = 0;

        // 選擇不重複的商品
        while (count($selectedProducts) < $itemCount) {
            $productId = rand(1, 100);
            if (!in_array($productId, $selectedProducts)) {
                $selectedProducts[] = $productId;
            }
        }

        // 插入訂單
        $orderStmt->execute([$userId, $orderNumber, $status, 0, $orderDate, $orderDate]);
        $orderId = $pdo->lastInsertId();

        // 插入訂單明細
        foreach ($selectedProducts as $productId) {
            $quantity = rand(1, 3);

            // 從資料庫獲取商品價格
            $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $priceStmt->execute([$productId]);
            $unitPrice = $priceStmt->fetchColumn();

            $subtotal = $unitPrice * $quantity;
            $totalAmount += $subtotal;

            $itemStmt->execute([$orderId, $productId, $quantity, $unitPrice, $subtotal]);
        }

        // 更新訂單總金額
        $updateStmt = $pdo->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
        $updateStmt->execute([$totalAmount, $orderId]);

        if ($i % 100 == 0) {
            echo "已生成 {$i} 筆訂單\n";
        }
    }

    echo "已生成 1000 筆訂單和對應明細\n";
}

/**
 * 根據權重選擇狀態
 */
function getWeightedStatus($statuses, $weights) {
    $rand = mt_rand() / mt_getrandmax();
    $cumulative = 0;

    for ($i = 0; $i < count($weights); $i++) {
        $cumulative += $weights[$i];
        if ($rand <= $cumulative) {
            return $statuses[$i];
        }
    }

    return $statuses[count($statuses) - 1];
}

/**
 * 生成訂單日期
 */
function generateOrderDate($orderIndex) {
    // 最後50筆訂單中，30%是今天的
    if ($orderIndex > 950 && rand(1, 100) <= 30) {
        $hour = rand(8, 22);
        $minute = rand(0, 59);
        $second = rand(0, 59);
        return "2025-06-05 ".sprintf("%02d:%02d:%02d", $hour, $minute, $second);
    }

    // 其他訂單在過去1年內
    $startTime = strtotime('2024-06-01');
    $endTime = strtotime('2025-06-04');
    $randomTime = rand($startTime, $endTime);

    return date('Y-m-d H:i:s', $randomTime);
}

/**
 * 顯示統計資訊
 */
function showStatistics($pdo) {
    echo "\n=== 測試資料統計 ===\n";

    // 總訂單數
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "總訂單數: {$totalOrders}\n";

    // 總明細數
    $totalItems = $pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
    echo "總明細數: {$totalItems}\n";

    // 狀態分佈
    $statusResult = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    echo "訂單狀態分佈:\n";
    while ($row = $statusResult->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['status']}: {$row['count']}\n";
    }

    // 今天的訂單
    $todayResult = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM orders WHERE DATE(created_at) = '2025-06-05'");
    $today = $todayResult->fetch(PDO::FETCH_ASSOC);
    echo "今天訂單: {$today['count']} 筆, 金額: $".number_format($today['amount'])."\n";

    // 總金額
    $totalAmount = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders")->fetchColumn();
    echo "訂單總金額: $".number_format($totalAmount)."\n";

    echo "\n測試資料生成完成！可以開始測試 API 了。\n";
}
?>