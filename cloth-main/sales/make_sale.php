<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$success_message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval(str_replace(',', '', $_POST['unit_price']));
    $payment_method = trim($_POST['payment_method']);
    
    // Validation
    if ($product_id <= 0) $errors[] = "Please select a product";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0";
    if ($unit_price <= 0) $errors[] = "Unit price must be greater than 0";
    if (empty($payment_method)) $errors[] = "Please select a payment method";
    
    $allowed_payment_methods = ['LIPA NUMBER', 'CASH', 'CRDB BANK'];
    if (!in_array($payment_method, $allowed_payment_methods)) {
        $errors[] = "Invalid payment method selected";
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("SELECT name, price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("Not enough stock available. Current stock: " . number_format($product['stock_quantity']));
            }
            
            $total_price = $unit_price * $quantity;
            
            $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $product_id, $quantity, $total_price, $payment_method);
            $stmt->execute();
            $stmt->close();
            
            $new_stock = $product['stock_quantity'] - $quantity;
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $success_message = "Sale completed successfully! Product: " . htmlspecialchars($product['name']) . 
                             ", Quantity: " . number_format($quantity) . 
                             ", Total: " . number_format($total_price) . " TZS";
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get products with stock
$products = $conn->query("
    SELECT p.product_id, p.name, p.price, p.stock_quantity, c.name as category_name, p.size, p.color, p.brand
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock_quantity > 0 
    ORDER BY c.name, p.name
");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-shopping-cart"></i> Make a Sale</h2>

<!-- Today's Sales Statistics -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value">
                <?php
                $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()");
                echo number_format($stmt->fetch_row()[0]);
                ?>
            </div>
            <div class="stat-label">Sales Today</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value">
                <?php
                $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE()");
                echo number_format($stmt->fetch_row()[0] ?? 0);
                ?> TZS
            </div>
            <div class="stat-label">Revenue</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value">
                <?php
                $stmt = $conn->query("SELECT SUM(quantity) FROM sales WHERE DATE(sale_date) = CURDATE()");
                echo number_format($stmt->fetch_row()[0] ?? 0);
                ?>
            </div>
            <div class="stat-label">Items Sold</div>
        </div>
    </div>
</div>

<!-- Main Sales Form -->
<div class="sales-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form id="sales-form" method="POST" action="">
        <div class="form-grid">
            <!-- Left Column: Product Selection & Details -->
            <div class="form-section">
                <!-- Product Search -->
                <div class="form-group">
                    <label for="product-search">
                        <i class="fas fa-search"></i> Search Product
                    </label>
                    <div class="product-search-container">
                        <input type="text" 
                               id="product-search" 
                               class="form-control search-input" 
                               placeholder="Type to search jerseys, caps, shorts..." 
                               autocomplete="off">
                        <i class="fas fa-chevron-down search-toggle" id="toggle-dropdown"></i>
                        
                        <div class="product-dropdown" id="product-dropdown">
                            <div class="dropdown-content">
                                <div id="product-list">
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                        <div class="product-item" 
                                             data-id="<?php echo $product['product_id']; ?>"
                                             data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                             data-price="<?php echo $product['price']; ?>"
                                             data-stock="<?php echo $product['stock_quantity']; ?>"
                                             data-search="<?php echo strtolower($product['name'] . ' ' . $product['brand'] . ' ' . $product['color'] . ' ' . $product['size'] . ' ' . $product['category_name']); ?>">
                                            <div class="product-info">
                                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="product-meta">
                                                    <span class="brand"><?php echo htmlspecialchars($product['brand']); ?></span>
                                                    <span class="size">Size: <?php echo htmlspecialchars($product['size']); ?></span>
                                                    <span class="color"><?php echo htmlspecialchars($product['color']); ?></span>
                                                </div>
                                            </div>
                                            <div class="product-details">
                                                <div class="product-price"><?php echo number_format($product['price']); ?> TZS</div>
                                                <div class="stock-info <?php 
                                                    echo $product['stock_quantity'] > 20 ? 'stock-high' : 
                                                        ($product['stock_quantity'] > 5 ? 'stock-medium' : 'stock-low'); 
                                                ?>">
                                                    <?php echo $product['stock_quantity']; ?> in stock
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="no-results" id="no-results" style="display: none;">
                                    <i class="fas fa-search"></i>
                                    <p>No products found</p>
                                    <small>Try different search terms</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden select for form submission -->
                        <select id="product_id" name="product_id" style="display: none;" required>
                            <option value="">Select Product</option>
                            <?php 
                            $products->data_seek(0);
                            while ($product = $products->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $product['product_id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="form-group">
                    <label for="quantity">
                        <i class="fas fa-sort-numeric-up"></i> Quantity
                    </label>
                    <div class="quantity-control">
                        <button type="button" class="qty-btn qty-minus" id="qty-minus">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               class="form-control qty-input" 
                               min="1" 
                               required 
                               placeholder="0">
                        <button type="button" class="qty-btn qty-plus" id="qty-plus">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Unit Price -->
                <div class="form-group">
                    <label for="unit_price">
                        <i class="fas fa-money-bill-wave"></i> Unit Price (TZS)
                    </label>
                    <input type="text" 
                           id="unit_price" 
                           name="unit_price" 
                           class="form-control price-input" 
                           required 
                           placeholder="0">
                </div>
            </div>

            <!-- Right Column: Payment & Total -->
            <div class="form-section">
                <!-- Payment Method -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-credit-card"></i> Payment Method
                    </label>
                    <div class="payment-grid">
                        <div class="payment-option">
                            <input type="radio" id="cash" name="payment_method" value="CASH" required>
                            <label for="cash" class="payment-label">
                                <div class="payment-icon cash">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <span>CASH</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="mobile" name="payment_method" value="LIPA NUMBER" required>
                            <label for="mobile" class="payment-label">
                                <div class="payment-icon mobile">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <span>MOBILE</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="bank" name="payment_method" value="CRDB BANK" required>
                            <label for="bank" class="payment-label">
                                <div class="payment-icon bank">
                                    <i class="fas fa-university"></i>
                                </div>
                                <span>BANK</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Total Display -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-calculator"></i> Total Amount
                    </label>
                    <div class="total-display">
                        <div class="total-amount" id="total-amount">0</div>
                        <div class="total-currency">TZS</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success btn-large" id="submit-btn" disabled>
                        <i class="fas fa-shopping-cart"></i> Complete Sale
                    </button>
                    <button type="button" class="btn btn-secondary btn-large" id="reset-btn">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Responsive Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

/* Main Sales Container */
.sales-container {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

/* Product Search Styles */
.product-search-container {
    position: relative;
}

.search-input {
    position: relative;
    padding-right: 3rem;
}

.search-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6b7280;
    transition: transform 0.3s ease, color 0.3s ease;
}

.search-toggle:hover {
    color: #374151;
}

.search-toggle.open {
    transform: translateY(-50%) rotate(180deg);
    color: #667eea;
}

.product-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 300px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.product-dropdown.show {
    display: block;
    animation: dropdownSlide 0.3s ease;
}

@keyframes dropdownSlide {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-content {
    max-height: 300px;
    overflow-y: auto;
}

.product-item {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
}

.product-item:hover,
.product-item.focused {
    background: #f8fafc;
}

.product-item.selected {
    background: #ede9fe;
    border-left: 4px solid #667eea;
}

.product-item:last-child {
    border-bottom: none;
}

.product-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.product-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.8rem;
    color: #6b7280;
    flex-wrap: wrap;
}

.brand {
    color: #4f46e5;
    font-weight: 500;
}

.product-details {
    text-align: right;
    flex-shrink: 0;
}

.product-price {
    font-weight: 600;
    color: #059669;
    margin-bottom: 0.25rem;
}

.stock-info {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
}

.stock-high {
    background: #d1fae5;
    color: #065f46;
}

.stock-medium {
    background: #fef3c7;
    color: #92400e;
}

.stock-low {
    background: #fee2e2;
    color: #991b1b;
}

.no-results {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
}

.no-results i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Quantity Control */
.quantity-control {
    display: flex;
    align-items: center;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.3s ease;
}

.quantity-control:focus-within {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.qty-btn {
    background: #f9fafb;
    border: none;
    padding: 0.75rem;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
}

.qty-btn:hover:not(:disabled) {
    background: #667eea;
    color: white;
}

.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-input {
    flex: 1;
    border: none;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 1rem;
}

.qty-input:focus {
    outline: none;
}

/* Price Input */
.price-input {
    font-family: 'Courier New', monospace;
    text-align: right;
    font-weight: 600;
}

/* Payment Methods */
.payment-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.payment-option {
    position: relative;
}

.payment-option input {
    position: absolute;
    opacity: 0;
}

.payment-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.payment-label:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-2px);
}

.payment-option input:checked + .payment-label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.payment-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.payment-icon.cash {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.payment-icon.mobile {
    background: linear-gradient(135deg, #10b981, #059669);
}

.payment-icon.bank {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.payment-label span {
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
}

/* Total Display */
.total-display {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    transition: transform 0.2s ease;
}

.total-amount {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.total-currency {
    font-size: 1rem;
    opacity: 0.9;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.btn-large {
    padding: 1rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex: 1;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: #6b7280;
    color: white;
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(75, 85, 99, 0.4);
}

/* Form Controls */
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Alert Messages */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-danger {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .sales-container {
        padding: 1.5rem;
    }
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
        gap: 0.75rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .sales-container {
        padding: 1rem;
    }
    
    .form-grid {
        gap: 1.5rem;
    }
    
    .payment-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .payment-label {
        flex-direction: row;
        justify-content: flex-start;
        padding: 0.75rem;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .total-amount {
        font-size: 2rem;
    }
    
    .dropdown-content {
        max-height: 250px;
    }
    
    .product-item {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .product-details {
        text-align: left;
    }
    
    .product-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
}

@media (max-width: 480px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 1rem;
    }
    
    .stat-icon {
        margin-right: 0;
    }
    
    .sales-container {
        padding: 0.75rem;
    }
    
    .form-group {
        gap: 0.5rem;
    }
    
    .payment-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .total-amount {
        font-size: 1.8rem;
    }
    
    .btn-large {
        padding: 0.875rem 1rem;
        font-size: 0.95rem;
    }
}

/* Custom Scrollbar for Dropdown */
.dropdown-content::-webkit-scrollbar {
    width: 6px;
}

.dropdown-content::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.dropdown-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dropdown-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
class SimplifiedSalesForm {
    constructor() {
        this.form = document.getElementById('sales-form');
        this.searchInput = document.getElementById('product-search');
        this.dropdown = document.getElementById('product-dropdown');
        this.productList = document.getElementById('product-list');
        this.toggleBtn = document.getElementById('toggle-dropdown');
        this.productSelect = document.getElementById('product_id');
        this.quantityInput = document.getElementById('quantity');
        this.priceInput = document.getElementById('unit_price');
        this.totalDisplay = document.getElementById('total-amount');
        this.submitBtn = document.getElementById('submit-btn');
        
        this.selectedProduct = null;
        this.allProducts = [];
        this.focusedIndex = -1;
        
        this.init();
    }
    
    init() {
        this.collectProducts();
        this.bindEvents();
    }
    
    collectProducts() {
        const items = this.productList.querySelectorAll('.product-item');
        this.allProducts = Array.from(items).map(item => ({
            element: item,
            id: item.dataset.id,
            name: item.dataset.name,
            price: parseFloat(item.dataset.price),
            stock: parseInt(item.dataset.stock),
            searchText: item.dataset.search
        }));
    }
    
    bindEvents() {
        // Search functionality
        this.searchInput.addEventListener('click', () => this.showDropdown());
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        this.searchInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        this.toggleBtn.addEventListener('click', () => this.toggleDropdown());
        
        // Product selection
        this.productList.addEventListener('click', (e) => {
            const item = e.target.closest('.product-item');
            if (item) this.selectProduct(item);
        });
        
        // Quantity controls
        document.getElementById('qty-minus').addEventListener('click', () => this.adjustQuantity(-1));
        document.getElementById('qty-plus').addEventListener('click', () => this.adjustQuantity(1));
        this.quantityInput.addEventListener('input', () => this.updateTotal());
        
        // Price input
        this.priceInput.addEventListener('input', () => {
            this.formatPrice();
            this.updateTotal();
        });
        
        // Payment methods
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => this.validateForm());
        });
        
        // Form actions
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        document.getElementById('reset-btn').addEventListener('click', () => this.resetForm());
        
        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.product-search-container')) {
                this.hideDropdown();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideDropdown();
        });
    }
    
    showDropdown() {
        this.dropdown.classList.add('show');
        this.toggleBtn.classList.add('open');
    }
    
    hideDropdown() {
        this.dropdown.classList.remove('show');
        this.toggleBtn.classList.remove('open');
        this.focusedIndex = -1;
        this.updateFocus();
    }
    
    toggleDropdown() {
        this.dropdown.classList.contains('show') ? this.hideDropdown() : this.showDropdown();
    }
    
    handleSearch(query) {
        const term = query.toLowerCase().trim();
        let visibleCount = 0;
        
        this.allProducts.forEach(product => {
            const matches = !term || product.searchText.includes(term) || 
                           product.name.toLowerCase().includes(term);
            
            product.element.style.display = matches ? 'flex' : 'none';
            if (matches) visibleCount++;
        });
        
        // Show/hide no results message
        const noResults = document.getElementById('no-results');
        if (visibleCount === 0 && term) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
        
        this.focusedIndex = -1;
        this.updateFocus();
        
        if (!this.dropdown.classList.contains('show')) {
            this.showDropdown();
        }
    }
    
    handleKeyDown(e) {
        if (!this.dropdown.classList.contains('show')) return;
        
        const visibleProducts = this.allProducts.filter(p => p.element.style.display !== 'none');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.focusedIndex = Math.min(this.focusedIndex + 1, visibleProducts.length - 1);
                this.updateFocus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
                this.updateFocus();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.focusedIndex >= 0 && visibleProducts[this.focusedIndex]) {
                    this.selectProduct(visibleProducts[this.focusedIndex].element);
                }
                break;
        }
    }
    
    updateFocus() {
        // Remove focus from all items
        this.allProducts.forEach(product => {
            product.element.classList.remove('focused');
        });
        
        // Add focus to current item
        const visibleProducts = this.allProducts.filter(p => p.element.style.display !== 'none');
        if (this.focusedIndex >= 0 && visibleProducts[this.focusedIndex]) {
            visibleProducts[this.focusedIndex].element.classList.add('focused');
            visibleProducts[this.focusedIndex].element.scrollIntoView({ block: 'nearest' });
        }
    }
    
    selectProduct(element) {
        const product = this.allProducts.find(p => p.element === element);
        if (!product) return;
        
        this.selectedProduct = product;
        
        // Update UI
        this.searchInput.value = product.name;
        this.productSelect.value = product.id;
        
        // Set suggested price
        this.priceInput.value = this.formatNumber(product.price);
        
        // Hide dropdown
        this.hideDropdown();
        
        // Update quantity constraints
        this.quantityInput.setAttribute('max', product.stock);
        
        // Focus quantity input
        this.quantityInput.focus();
        
        // Update calculations
        this.updateTotal();
        this.validateForm();
        
        // Visual feedback
        element.classList.add('selected');
        setTimeout(() => element.classList.remove('selected'), 2000);
    }
    
    adjustQuantity(delta) {
        const current = parseInt(this.quantityInput.value) || 0;
        const max = this.selectedProduct ? this.selectedProduct.stock : 999;
        const newValue = Math.max(1, Math.min(max, current + delta));
        
        this.quantityInput.value = newValue;
        this.updateTotal();
        this.validateForm();
    }
    
    formatPrice() {
        const value = this.priceInput.value.replace(/[^\d.]/g, '');
        const number = parseFloat(value) || 0;
        this.priceInput.value = this.formatNumber(number);
    }
    
    formatNumber(number) {
        return new Intl.NumberFormat('en-US').format(Math.round(number));
    }
    
    parseNumber(str) {
        return parseFloat(str.replace(/,/g, '')) || 0;
    }
    
    updateTotal() {
        const quantity = parseInt(this.quantityInput.value) || 0;
        const unitPrice = this.parseNumber(this.priceInput.value);
        const total = quantity * unitPrice;
        
        this.totalDisplay.textContent = this.formatNumber(total);
        
        // Visual feedback for calculation
        if (total > 0) {
            this.totalDisplay.parentElement.style.transform = 'scale(1.05)';
            setTimeout(() => {
                this.totalDisplay.parentElement.style.transform = 'scale(1)';
            }, 200);
        }
        
        this.validateForm();
    }
    
    validateForm() {
        const hasProduct = this.productSelect.value !== '';
        const hasQuantity = this.quantityInput.value !== '' && parseInt(this.quantityInput.value) > 0;
        const hasPrice = this.priceInput.value !== '' && this.parseNumber(this.priceInput.value) > 0;
        const hasPayment = document.querySelector('input[name="payment_method"]:checked') !== null;
        
        // Check stock availability
        let hasValidStock = true;
        if (this.selectedProduct && hasQuantity) {
            const requestedQty = parseInt(this.quantityInput.value);
            hasValidStock = requestedQty <= this.selectedProduct.stock;
        }
        
        const isValid = hasProduct && hasQuantity && hasPrice && hasPayment && hasValidStock;
        this.submitBtn.disabled = !isValid;
        
        // Update button appearance
        if (isValid) {
            this.submitBtn.style.opacity = '1';
        } else {
            this.submitBtn.style.opacity = '0.6';
        }
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateSubmission()) {
            return;
        }
        
        if (!this.confirmSale()) {
            return;
        }
        
        // Show loading state
        this.submitBtn.innerHTML = '<span class="loading"></span> Processing...';
        this.submitBtn.disabled = true;
        
        // Clean price for submission
        const cleanPrice = this.parseNumber(this.priceInput.value);
        this.priceInput.value = cleanPrice.toString();
        
        // Submit the form
        this.form.submit();
    }
    
    validateSubmission() {
        if (!this.selectedProduct) {
            alert('Please select a product');
            this.searchInput.focus();
            return false;
        }
        
        const quantity = parseInt(this.quantityInput.value);
        if (!quantity || quantity <= 0) {
            alert('Please enter a valid quantity');
            this.quantityInput.focus();
            return false;
        }
        
        if (quantity > this.selectedProduct.stock) {
            alert(`Not enough stock. Available: ${this.selectedProduct.stock}`);
            this.quantityInput.focus();
            return false;
        }
        
        const price = this.parseNumber(this.priceInput.value);
        if (!price || price <= 0) {
            alert('Please enter a valid price');
            this.priceInput.focus();
            return false;
        }
        
        if (!document.querySelector('input[name="payment_method"]:checked')) {
            alert('Please select a payment method');
            return false;
        }
        
        return true;
    }
    
    confirmSale() {
        const quantity = parseInt(this.quantityInput.value);
        const unitPrice = this.parseNumber(this.priceInput.value);
        const total = quantity * unitPrice;
        const payment = document.querySelector('input[name="payment_method"]:checked').value;
        
        return confirm(`Confirm Sale:

Product: ${this.selectedProduct.name}
Quantity: ${quantity}
Unit Price: ${this.formatNumber(unitPrice)} TZS
Payment: ${payment}

Total: ${this.formatNumber(total)} TZS

Proceed with this sale?`);
    }
    
    resetForm() {
        // Reset all form fields
        this.form.reset();
        this.searchInput.value = '';
        this.selectedProduct = null;
        this.totalDisplay.textContent = '0';
        
        // Reset UI states
        this.hideDropdown();
        this.allProducts.forEach(product => {
            product.element.style.display = 'flex';
            product.element.classList.remove('selected', 'focused');
        });
        
        // Reset validation
        this.validateForm();
        
        // Focus search input
        this.searchInput.focus();
        
        // Show success message
        this.showNotification('Form reset successfully', 'success');
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize the form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new SimplifiedSalesForm();
    
    // Add slide animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100%); }
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ Simplified Sales Form initialized successfully!');
});
</script>

<?php include '../includes/footer.php'; ?>