<?php
require_once __DIR__.'/../services/OrderService.php';
require_once __DIR__.'/BaseController.php';

/**
 * OrderController
 *
 * 處理訂單相關 API 請求，包括查詢、建立、更新、統計等。
 *
 * - GET    /api/orders                查詢訂單列表（支援分頁、篩選、排序）
 * - GET    /api/orders/{id}           查詢單一訂單
 * - POST   /api/orders                建立新訂單
 * - PUT    /api/orders/{id}/status    更新訂單狀態
 * - GET    /api/orders/stats          查詢訂單統計資訊
 * - GET    /api/users/{user_id}/orders?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD 查詢指定使用者在指定時間區間內的訂單
 */
class OrderController extends BaseController {
    private OrderService $orderService;

    public function __construct() {
        $this->orderService = new OrderService();
    }

    /**
     * 查詢訂單列表，支援分頁、篩選、排序
     * GET /api/orders
     *
     * Query params:
     *   - page: int (分頁頁數，預設 1)
     *   - limit: int (每頁筆數，預設 20)
     *   - user_id: int (使用者 ID)
     *   - status: string (訂單狀態)
     *   - order_number: string (訂單編號)
     *   - start_date: string (起始日期 YYYY-MM-DD)
     *   - end_date: string (結束日期 YYYY-MM-DD)
     *   - sort_by: string (排序欄位)
     *   - sort_order: string (asc/desc)
     *
     * @return void 回傳 JSON 陣列
     */
    public function getOrders() {
        try {
            // Pagination
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

            // Filtering and Sorting options
            $options = [
                'page' => $page,
                'limit' => $limit,
                'filters' => [
                    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
                    'status' => $_GET['status'] ?? null,
                    'order_number' => $_GET['order_number'] ?? null,
                    'start_date' => $_GET['start_date'] ?? null,
                    'end_date' => $_GET['end_date'] ?? null,
                ],
                'sort' => [
                    'by' => $_GET['sort_by'] ?? 'created_at',
                    'order' => $_GET['sort_order'] ?? 'desc',
                ]
            ];

            $orders = $this->orderService->getAllOrders($options);
            $this->sendJsonResponse($orders);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }

    /**
     * 查詢單一訂單
     * GET /api/orders/{id}
     *
     * @param int $id 訂單 ID
     * @return void 回傳 JSON 物件
     */
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

    /**
     * 建立新訂單
     * POST /api/orders
     *
     * Body:
     *   - user_id: int (使用者 ID)
     *   - items: array (商品明細)
     *
     * @return void 回傳 JSON 物件（含新訂單 ID）
     */
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

    /**
     * 更新訂單狀態
     * PUT /api/orders/{id}/status
     *
     * @param int $id 訂單 ID
     * Body:
     *   - status: string (新狀態)
     *
     * @return void 回傳 JSON 物件
     */
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

    /**
     * 查詢訂單統計資訊
     * GET /api/orders/stats
     *
     * @return void 回傳 JSON 物件
     */
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

    /**
     * 查詢指定使用者在指定時間區間內的訂單
     * GET /api/users/{user_id}/orders?start_date=...&end_date=...
     *
     * @param int $userId 使用者 ID
     * @return void 回傳 JSON 陣列
     */
    public function getUserOrdersByPeriod($userId) {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        if (!$startDate || !$endDate) {
            $this->sendErrorResponse('Missing start_date or end_date.', 400);
            return;
        }
        try {
            $orders = $this->orderService->getUserOrdersByPeriod($userId, $startDate, $endDate);
            $this->sendJsonResponse($orders);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }
}