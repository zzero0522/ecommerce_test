<?php
require_once __DIR__.'/../services/ProductService.php';
require_once __DIR__.'/BaseController.php';

/**
 * ProductController
 *
 * 處理商品相關 API 請求，包括查詢、建立等。
 *
 * - GET  /api/products         查詢商品列表（支援分頁、篩選、排序）
 * - POST /api/products        新增商品
 */
class ProductController extends BaseController {
    private ProductService $productService;

    public function __construct() {
        $this->productService = new ProductService();
    }

    /**
     * 查詢商品列表，支援分頁、篩選、排序
     * GET /api/products
     *
     * Query params:
     *   - page: int (分頁頁數，預設 1)
     *   - limit: int (每頁筆數，預設 20)
     *   - min_price: int (最低價格)
     *   - max_price: int (最高價格)
     *   - sort_by: string (排序欄位)
     *   - sort_order: string (asc/desc)
     *
     * @return void 回傳 JSON 陣列
     */
    public function getProducts() {
        try {
            // Pagination
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

            // Filtering and Sorting options
            $options = [
                'page' => $page,
                'limit' => $limit,
                'filters' => [
                    'min_price' => isset($_GET['min_price']) ? (int)$_GET['min_price'] : null,
                    'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : null,
                ],
                'sort' => [
                    'by' => $_GET['sort_by'] ?? 'created_at',
                    'order' => $_GET['sort_order'] ?? 'desc',
                ]
            ];

            $products = $this->productService->getAllProducts($options);
            $this->sendJsonResponse($products);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }

    /**
     * 新增商品
     * POST /api/products
     *
     * Body:
     *   - name: string (商品名稱)
     *   - price: int (價格)
     *   - stock: int (庫存)
     *   - description: string|null (商品描述)
     *
     * @return void 回傳 JSON 物件（含新商品 ID）
     */
    public function createProduct() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse('Invalid JSON format.', 400);
            return;
        }

        if (empty($data['name']) || !is_string($data['name'])) {
            $this->sendErrorResponse('Missing or invalid name.', 400);
            return;
        }
        if (!isset($data['price']) || !is_numeric($data['price'])) {
            $this->sendErrorResponse('Missing or invalid price.', 400);
            return;
        }
        if (!isset($data['stock']) || !is_numeric($data['stock'])) {
            $this->sendErrorResponse('Missing or invalid stock.', 400);
            return;
        }
        // 允許 description 為空字串或 null，僅檢查是否有傳入 description 欄位
        if (!isset($data['description'])) {
            $this->sendErrorResponse('Missing description.', 400);
            return;
        }
        $data['description'] = $data['description'] === null ? null : (string)$data['description'];

        try {
            $productId = $this->productService->createProduct($data);
            $this->sendJsonResponse(['message' => 'Product created successfully', 'product_id' => $productId], 201);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred while creating the product.', 500);
        } catch (Exception $e) {
            error_log('Create Product Error: '.$e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 422);
        }
    }
}
