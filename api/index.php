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
    case preg_match('#^/api/orders/?$#', $path) && $method === 'GET':
        $controller = new OrderController();
        $controller->getOrders();
        break;
    case preg_match('#^/api/orders/(\d+)/?$#', $path, $matches) && $method === 'GET':
        $controller = new OrderController();
        $orderId = (int)$matches[1];
        $controller->getOrderById($orderId);
        break;
    case preg_match('#^/api/orders/?$#', $path) && $method === 'POST':
        $controller = new OrderController();
        $controller->createOrder();
        break;
    case preg_match('#^/api/orders/(\d+)/status/?$#', $path, $matches) && $method === 'PUT':
        $controller = new OrderController();
        $orderId = (int)$matches[1];
        $controller->updateOrderStatus($orderId);
        break;
    case preg_match('#^/api/orders/stats/?$#', $path) && $method === 'GET':
        $controller = new OrderController();
        $controller->getOrderStats();
        break;
    case preg_match('#^/api/products/?$#', $path) && $method === 'GET':
        $controller = new ProductController();
        $controller->getProducts();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'API not found', 'path' => $path]);
}