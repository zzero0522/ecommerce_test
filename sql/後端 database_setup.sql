-- E-commerce Order Management System - Database Setup
-- This file only creates the database, table structure should be designed by candidate

-- Create database
CREATE DATABASE IF NOT EXISTS ecommerce_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use database
USE ecommerce_test;

-- Please create your designed table structure here
-- Suggested tables: users, products, orders, order_items
--
-- Example:
-- CREATE TABLE users (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     -- Please design fields according to requirements...
-- );

-- users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '客戶姓名',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT '客戶電子郵件',
    created_at DATETIME NOT NULL COMMENT '建立時間',
    updated_at DATETIME NOT NULL COMMENT '更新時間'
);

-- products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名稱',
    price INT NOT NULL COMMENT '商品價格',
    stock INT NOT NULL COMMENT '商品庫存',
    description TEXT NULL DEFAULT NULL COMMENT '商品描述',
    created_at DATETIME NOT NULL COMMENT '建立時間',
    updated_at DATETIME NOT NULL COMMENT '更新時間'
);

-- orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '客戶ID',
    order_number VARCHAR(32) NOT NULL UNIQUE COMMENT '訂單編號',
    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL COMMENT '訂單狀態',
    total_amount INT NOT NULL COMMENT '訂單總金額',
    created_at DATETIME NOT NULL COMMENT '建立時間',
    updated_at DATETIME NOT NULL COMMENT '更新時間',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- order_items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL COMMENT '訂單ID',
    product_id INT NOT NULL COMMENT '商品ID',
    quantity INT NOT NULL COMMENT '商品數量',
    unit_price INT NOT NULL COMMENT '商品單價',
    subtotal INT NOT NULL COMMENT '小計',
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- index
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_userid_createdat ON orders(user_id, created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_created_at ON products(created_at);

-- Notes:
-- 1. Please ensure table structure is compatible with generate_test_data.php script
-- 2. Recommend designing appropriate indexes to improve query performance
-- 3. Please consider foreign key constraints to maintain data integrity
-- 4. Order status suggestions: pending, processing, shipped, delivered, cancelled