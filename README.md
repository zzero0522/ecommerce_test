# eCommerce API 專案

本專案為一個簡易的電商後端 API，使用 PHP 原生實作，支援訂單與商品管理，並附有 Postman 測試集與 SQL 建表腳本。

## 目錄結構

```
ecommerce_test/
├── api/
│   ├── controllers/
│   │   ├── BaseController.php
│   │   ├── OrderController.php
│   │   └── ProductController.php
│   ├── services/
│   │   ├── BaseService.php
│   │   ├── OrderService.php
│   │   └── ProductService.php
│   ├── Database.php
│   └── index.php
├── postman/
│   └── Backend 筆試 API 測試.postman_collection.json
├── sql/
│   └── 後端 database_setup.sql
└── README.md
```

## 快速啟動

1. **安裝 PHP 8 以上版本與 MySQL 資料庫**
2. 匯入資料庫：
   - 你可以用任何圖形化工具（如 DBeaver、phpMyAdmin、Sequel Ace 等）開啟 `sql/後端 database_setup.sql`，直接執行 SQL 腳本。
   - 或者使用 CLI：
     ```sh
     mysql -u <user> -p < sql/後端\ database_setup.sql
     ```
3. 調整 `api/Database.php` 內的資料庫連線設定（帳號、密碼、資料庫名稱）
4. 啟動 PHP 內建伺服器：
   ```sh
   cd api
   php -S localhost:8080
   ```
5. 使用 Postman 匯入 `postman/Backend 筆試 API 測試.postman_collection.json` 測試 API

## 主要 API 一覽

### 訂單管理
- `GET    /api/orders`                查詢訂單列表（分頁、篩選、排序）
- `GET    /api/orders/{id}`           查詢單一訂單
- `POST   /api/orders`                建立新訂單
- `PUT    /api/orders/{id}/status`    更新訂單狀態
- `GET    /api/orders/stats`          查詢訂單統計資訊
- `GET    /api/users/{user_id}/orders?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD` 查詢指定使用者區間訂單

### 商品管理
- `GET    /api/products`              查詢商品列表（分頁、價格篩選、排序）
- `POST   /api/products`              新增商品

## 其他
- 所有 API 回應皆為 JSON 格式
- 詳細參數與範例請參考 Postman 測試集

---

如有問題請聯絡專案負責人。
