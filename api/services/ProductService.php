<?php
require_once __DIR__.'/BaseService.php';

/**
 * ProductService
 *
 * 提供商品相關的資料存取與商業邏輯，包括查詢、建立、更新等。
 */
class ProductService extends BaseService {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 查詢商品列表，支援分頁、篩選、排序
     * @param array $options 查詢選項 (page, limit, filters, sort)
     * @return array 商品資料陣列
     */
    public function getAllProducts($options = []) {
        // Extract options with defaults
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 20;
        $filters = $options['filters'] ?? [];
        $sort = $options['sort'] ?? ['by' => 'created_at', 'order' => 'desc'];

        $offset = ($page - 1) * $limit;

        // Base query
        $sql = "SELECT id, name, price, stock, description, created_at, updated_at FROM products";
        $whereClauses = [];
        $params = [];

        // Filtering
        if (isset($filters['min_price'])) {
            $whereClauses[] = "price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        if (isset($filters['max_price'])) {
            $whereClauses[] = "price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE ".implode(" AND ", $whereClauses);
        }

        // Sorting
        $allowedSortBy = ['price', 'created_at', 'id'];
        $sortBy = in_array($sort['by'], $allowedSortBy) ? $sort['by'] : 'created_at';
        $sortOrder = strtolower($sort['order']) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$sortBy} {$sortOrder}";

        // Pagination
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 新增商品
     * @param array $data
     * @return int|false 新商品ID或失敗回傳false
     */
    public function createProduct($data) {
        $stmt = $this->pdo->prepare("INSERT INTO products (name, price, stock, description, created_at, updated_at) VALUES (:name, :price, :stock, :description, NOW(), NOW())");
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':price', $data['price'], PDO::PARAM_INT);
        $stmt->bindValue(':stock', $data['stock'], PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description']);
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
}