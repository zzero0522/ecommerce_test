<?php

require_once __DIR__.'/controllers/OrderController.php';
require_once __DIR__.'/controllers/ProductController.php';

$path = rtrim($_GET['path']) ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($path === '' || $path === '/') {
    http_response_code(400);
    echo json_encode(['error' => 'No API path specified']);
    exit;
}

switch (true) {
    /**
     * GET /api/orders
     * 查詢訂單列表，支援分頁、篩選、排序
     * Query params: page, limit, user_id, status, order_number, start_date, end_date, sort_by, sort_order
     */
    case preg_match('#^/api/orders/?$#', $path) && $method === 'GET':
        $controller = new OrderController();
        $controller->getOrders();
        break;
    /**
     * GET /api/orders/{id}
     * 查詢單一訂單
     * Path param: id
     */
    case preg_match('#^/api/orders/(\d+)/?$#', $path, $matches) && $method === 'GET':
        $controller = new OrderController();
        $orderId = (int)$matches[1];
        $controller->getOrderById($orderId);
        break;
    /**
     * POST /api/orders
     * 建立新訂單
     * Body: { user_id, items: [...] }
     */
    case preg_match('#^/api/orders/?$#', $path) && $method === 'POST':
        $controller = new OrderController();
        $controller->createOrder();
        break;
    /**
     * PUT /api/orders/{id}/status
     * 更新訂單狀態
     * Path param: id, Body: { status }
     */
    case preg_match('#^/api/orders/(\d+)/status/?$#', $path, $matches) && $method === 'PUT':
        $controller = new OrderController();
        $orderId = (int)$matches[1];
        $controller->updateOrderStatus($orderId);
        break;
    /**
     * GET /api/orders/stats
     * 查詢訂單統計資訊
     */
    case preg_match('#^/api/orders/stats/?$#', $path) && $method === 'GET':
        $controller = new OrderController();
        $controller->getOrderStats();
        break;
    /**
     * GET /api/users/{user_id}/orders?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     * 查詢指定使用者在指定時間區間內的訂單
     * Query params: start_date, end_date
     */
    case preg_match('#^/api/users/(\d+)/orders/?$#', $path, $matches) && $method === 'GET':
        $controller = new OrderController();
        $userId = (int)$matches[1];
        $controller->getUserOrdersByPeriod($userId);
        break;
    /**
     * GET /api/products
     * 查詢商品列表，支援分頁、篩選、排序
     * Query params: page, limit, ...
     */
    case preg_match('#^/api/products/?$#', $path) && $method === 'GET':
        $controller = new ProductController();
        $controller->getProducts();
        break;
    /**
     * POST /api/products
     * 新增商品
     * Body: { name, price, stock, description }
     */
    case preg_match('#^/api/products/?$#', $path) && $method === 'POST':
        $controller = new ProductController();
        $controller->createProduct();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'API not found', 'path' => $path]);
}