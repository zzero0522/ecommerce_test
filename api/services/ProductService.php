<?php
require_once __DIR__.'/BaseService.php';

class ProductService extends BaseService {
    public function __construct() {
        parent::__construct();
    }

    public function getAllProducts($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT id, name, price, stock, created_at, updated_at FROM products ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}