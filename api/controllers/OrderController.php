<?php
require_once __DIR__.'/../services/OrderService.php';
require_once __DIR__.'/BaseController.php';

class OrderController extends BaseController {
    private OrderService $orderService;

    public function __construct() {
        $this->orderService = new OrderService();
    }

    public function getOrders() {
        try {
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
            $orders = $this->orderService->getAllOrders($page, $perPage);
            $this->sendJsonResponse($orders);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }

    public function getOrderById($id) {
        if (!is_numeric($id) || $id <= 0) {
            $this->sendErrorResponse('Invalid order ID', 400);
            return;
        }

        try {
            $order = $this->orderService->getOrderById($id);
            if ($order) {
                $this->sendJsonResponse($order);
            } else {
                $this->sendErrorResponse('Order not found', 404);
            }
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }

    public function createOrder() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse('Invalid JSON format.', 400);
            return;
        }

        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $this->sendErrorResponse('Missing or invalid user_id.', 400);
            return;
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            $this->sendErrorResponse('Missing or invalid items array.', 400);
            return;
        }

        try {
            $orderId = $this->orderService->createOrder($data);
            $this->sendJsonResponse(['message' => 'Order created successfully', 'order_id' => $orderId], 201);

        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred while creating the order.', 500);
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 422;
            error_log('Create Order Error: '.$e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $statusCode);
        }
    }

    public function updateOrderStatus($id) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse('Invalid JSON format.', 400);
            return;
        }

        if (empty($data['status'])) {
            $this->sendErrorResponse('Missing status field.', 400);
            return;
        }

        if (!is_string($data['status'])) {
            $this->sendErrorResponse('Status must be a string.', 400);
            return;
        }

        try {
            $affectedRows = $this->orderService->updateOrderStatus($id, $data['status']);
            if ($affectedRows > 0) {
                $this->sendJsonResponse(['message' => 'Order status updated successfully.']);
            } else {
                $this->sendErrorResponse('Order not found or status unchanged.', 404);
            }
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }

    public function getOrderStats() {
        try {
            $stats = $this->orderService->getOrderStats();
            $this->sendJsonResponse($stats);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }
}