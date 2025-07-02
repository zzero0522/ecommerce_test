<?php
require_once __DIR__.'/../services/ProductService.php';
require_once __DIR__.'/BaseController.php';

class ProductController extends BaseController {
    private ProductService $productService;

    public function __construct() {
        $this->productService = new ProductService();
    }

    public function getProducts() {
        try {
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
            $products = $this->productService->getAllProducts($page, $perPage);
            $this->sendJsonResponse($products);
        } catch (PDOException $e) {
            error_log('Database Error: '.$e->getMessage());
            $this->sendErrorResponse('A database error occurred.', 500);
        } catch (Exception $e) {
            error_log('General Error: '.$e->getMessage());
            $this->sendErrorResponse('An internal server error occurred.', 500);
        }
    }
}
