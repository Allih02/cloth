-- Clothing Shop Management System Database Schema
-- Run this SQL script to create the complete database structure

CREATE DATABASE IF NOT EXISTS clothing_shop_management;
USE clothing_shop_management;

-- Users table for authentication
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Suppliers table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    size VARCHAR(10),
    color VARCHAR(30),
    brand VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    supplier_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL
);

-- Sales table
CREATE TABLE sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);

-- Stock movements table (optional for tracking)
CREATE TABLE stock_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@clothingshop.com');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Men\'s Clothing', 'Clothing items for men'),
('Women\'s Clothing', 'Clothing items for women'),
('Children\'s Clothing', 'Clothing items for children'),
('Accessories', 'Fashion accessories'),
('Footwear', 'Shoes and sandals');

-- Insert sample suppliers
INSERT INTO suppliers (name, contact, email, address) VALUES 
('Fashion Forward Ltd', '+1-555-0101', 'contact@fashionforward.com', '123 Fashion Street, NY'),
('Style Source Inc', '+1-555-0102', 'sales@stylesource.com', '456 Trend Avenue, CA'),
('Clothing Co', '+1-555-0103', 'info@clothingco.com', '789 Apparel Road, TX');

-- Insert sample products
INSERT INTO products (name, category_id, size, color, brand, price, stock_quantity, supplier_id) VALUES 
('Men\'s Cotton T-Shirt', 1, 'L', 'Blue', 'BasicWear', 19.99, 50, 1),
('Women\'s Denim Jeans', 2, 'M', 'Dark Blue', 'DenimCo', 59.99, 30, 2),
('Kids Polo Shirt', 3, 'S', 'Red', 'KidsStyle', 24.99, 25, 1),
('Leather Wallet', 4, 'One Size', 'Brown', 'LeatherCraft', 39.99, 15, 3),
('Running Shoes', 5, '10', 'Black', 'SportMax', 89.99, 20, 2),
('Women\'s Blouse', 2, 'M', 'White', 'ElegantWear', 45.99, 35, 1),
('Men\'s Formal Shirt', 1, 'XL', 'White', 'BusinessPro', 49.99, 40, 3),
('Summer Dress', 2, 'S', 'Floral', 'SummerVibes', 69.99, 22, 2);

-- Create indexes for better performance
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_supplier ON products(supplier_id);
CREATE INDEX idx_sales_product ON sales(product_id);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);