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
    
    // Validate payment method
    $allowed_payment_methods = ['LIPA NUMBER', 'CASH', 'CRDB BANK'];
    if (!in_array($payment_method, $allowed_payment_methods)) {
        $errors[] = "Invalid payment method selected";
    }
    
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Get product details with lock
            $stmt = $conn->prepare("SELECT name, price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            // Check stock
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("Not enough stock available. Current stock: " . number_format($product['stock_quantity']));
            }
            
            // Calculate total price
            $total_price = $unit_price * $quantity;
            
            // Record sale with payment method
            $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $product_id, $quantity, $total_price, $payment_method);
            $stmt->execute();
            $stmt->close();
            
            // Update stock
            $new_stock = $product['stock_quantity'] - $quantity;
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $success_message = "Sale completed successfully!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get products with stock for dropdown
$products = $conn->query("SELECT product_id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name");

// Get today's stats
$today_sales = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_row()[0];
$today_revenue = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_row()[0] ?? 0;
$today_items = $conn->query("SELECT SUM(quantity) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_row()[0] ?? 0;
?>

<?php include '../includes/header.php'; ?>

<style>
/* Reset and Base Styles */
* {
    box-sizing: border-box;
}

body {
    overflow: hidden;
}

.sales-container {
    height: 100vh;
    padding: 0.75rem;
    background: transparent;
    overflow: hidden;
}

/* Main Layout Grid */
.sales-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 1rem;
    height: calc(100vh - 1.5rem);
    max-width: 1400px;
    margin: 0 auto;
}

/* Left Panel - Sale Form */
.sale-form-panel {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Right Panel - Summary */
.sale-summary-panel {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    overflow: hidden;
}

.summary-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Header */
.panel-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f4f8;
}

.panel-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.panel-title i {
    color: #667eea;
    font-size: 1.3rem;
}

/* Summary Card Headers */
.summary-card h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-card h3 i {
    color: #667eea;
    font-size: 0.9rem;
}

/* Alerts */
.alert {
    padding: 0.75rem;
    border-radius: 10px;
    margin-bottom: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideIn 0.3s ease;
    font-size: 0.9rem;
}

.alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Form Layout */
.form-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    overflow: visible;
    min-height: 0;
}

/* Form Groups */
.form-group {
    position: relative;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
}

.form-control {
    width: 100%;
    padding: 0.7rem 0.9rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.8);
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
    background: white;
}

/* Product Search */
.product-search-container {
    position: relative;
}

.product-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 150px;
    overflow-y: auto;
    z-index: 100;
    display: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-dropdown.show {
    display: block;
    animation: dropdownSlide 0.2s ease;
}

.product-option {
    padding: 0.7rem 0.9rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-option:hover,
.product-option.focused {
    background: rgba(102, 126, 234, 0.08);
}

.product-option.selected {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
    font-weight: 600;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.2rem;
    font-size: 0.9rem;
}

.product-price {
    font-size: 0.8rem;
    color: #10b981;
    font-weight: 500;
}

.stock-badge {
    padding: 0.2rem 0.4rem;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
}

.stock-badge.high { background: rgba(16, 185, 129, 0.15); color: #059669; }
.stock-badge.low { background: rgba(245, 158, 11, 0.15); color: #d97706; }
.stock-badge.out { background: rgba(239, 68, 68, 0.15); color: #dc2626; }

/* Quantity and Price Row */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

/* Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 0.4rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
    text-align: center;
    min-height: 65px;
    justify-content: center;
}

.payment-label:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-1px);
}

.payment-option input[type="radio"]:checked + .payment-label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.payment-option.payment-selected .payment-label {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.15);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.payment-option.payment-selected .payment-icon {
    color: #10b981;
    transform: scale(1.1);
}

.payment-option.payment-selected .payment-name {
    color: #059669;
    font-weight: 700;
}

.payment-icon {
    font-size: 1.2rem;
    margin-bottom: 0.4rem;
    color: #667eea;
}

.payment-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
}

/* Action Buttons */
.form-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 0.75rem;
    border-top: 2px solid #f0f4f8;
    margin-top: auto;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    flex: 1;
    min-height: 50px;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    font-weight: 800;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    background: linear-gradient(135deg, #059669, #047857);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    background: #94a3b8;
}

.btn-secondary {
    background: rgba(107, 114, 128, 0.1);
    color: #6b7280;
    border: 2px solid #e5e7eb;
    min-width: 100px;
    min-height: 50px;
}

.btn-secondary:hover {
    background: rgba(107, 114, 128, 0.15);
    border-color: #d1d5db;
}

/* Total Display */
.total-display {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    border: 2px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;
}

.total-label {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 0.4rem;
    font-weight: 500;
}

.total-amount {
    font-size: 1.8rem;
    font-weight: 800;
    color: #059669;
    margin: 0;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem;
    background: rgba(102, 126, 234, 0.04);
    border-radius: 8px;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2d3748;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-actions .btn {
    padding: 0.6rem;
    font-size: 0.8rem;
    min-height: auto;
}

/* Input Helpers */
.input-helper {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.2rem;
    padding: 0.3rem 0.6rem;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 5px;
    font-size: 0.75rem;
    color: #667eea;
    font-weight: 500;
    display: none;
}

.input-helper.show {
    display: block;
    animation: fadeIn 0.2s ease;
}

.form-group:focus-within .input-helper:not(#stockHelper) {
    display: block;
    animation: fadeIn 0.2s ease;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .sales-layout {
        grid-template-columns: 1fr 280px;
    }
}

@media (max-width: 968px) {
    .sales-layout {
        grid-template-columns: 1fr;
        grid-template-rows: 1fr auto;
        gap: 0.75rem;
    }
    
    .sale-summary-panel {
        flex-direction: row;
        overflow-x: auto;
        overflow-y: hidden;
    }
    
    .summary-card {
        min-width: 200px;
        flex-shrink: 0;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .payment-methods {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        flex-direction: row;
    }
}

@media (max-width: 640px) {
    .sales-container {
        padding: 0.5rem;
    }
    
    .sales-layout {
        height: calc(100vh - 1rem);
    }
    
    .sale-form-panel,
    .summary-card {
        padding: 0.75rem;
    }
    
    .panel-title {
        font-size: 1.3rem;
    }
}

/* Animations */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes dropdownSlide {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideOut {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(100%); }
}

/* Scrollbar Styling */
.product-dropdown::-webkit-scrollbar {
    width: 4px;
}

.product-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.product-dropdown::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.product-dropdown::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Focus Management */
.form-control:focus,
.payment-label:focus-within {
    z-index: 1;
}
</style>

<div class="sales-container">
    <div class="sales-layout">
        <!-- Left Panel - Sale Form -->
        <div class="sale-form-panel">
            <div class="panel-header">
                <h1 class="panel-title">
                    <i class="fas fa-shopping-cart"></i>
                    Make Sale
                </h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo implode(', ', $errors); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form id="salesForm" method="POST" action="">
                <div class="form-content">
                    <!-- Product Selection -->
                    <div class="form-group">
                        <label for="product-search">
                            <i class="fas fa-tshirt"></i> Select Product
                        </label>
                        <div class="product-search-container">
                            <input type="text" 
                                   id="product-search" 
                                   class="form-control" 
                                   placeholder="Click to search products..."
                                   readonly>
                            
                            <div class="product-dropdown" id="productDropdown">
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <div class="product-option" 
                                         data-value="<?php echo $product['product_id']; ?>"
                                         data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                         data-price="<?php echo $product['price']; ?>"
                                         data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="product-price">Suggested: <?php echo number_format($product['price']); ?> TZS</div>
                                        </div>
                                        <div class="stock-badge <?php 
                                            echo $product['stock_quantity'] > 20 ? 'high' : 
                                                ($product['stock_quantity'] > 0 ? 'low' : 'out'); 
                                        ?>">
                                            <?php echo number_format($product['stock_quantity']); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <select id="product_id" name="product_id" style="display: none;" required>
                                <option value="">Choose Product</option>
                                <?php 
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>" 
                                            data-price="<?php echo $product['price']; ?>"
                                            data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-helper">Start typing to search products</div>
                    </div>

                    <!-- Quantity and Price Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-sort-numeric-up"></i> Quantity
                            </label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-control" 
                                   min="1" 
                                   required 
                                   placeholder="Enter quantity">
                            <div class="input-helper" id="stockHelper" style="display: none;">Available: 0 units</div>
                        </div>

                        <div class="form-group">
                            <label for="unit_price">
                                <i class="fas fa-money-bill-wave"></i> Unit Price (TZS)
                            </label>
                            <input type="text" 
                                   id="unit_price" 
                                   name="unit_price" 
                                   class="form-control" 
                                   required 
                                   placeholder="Enter price">
                            <div class="input-helper">Use suggested or custom</div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-credit-card"></i> Payment Method
                        </label>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="lipa" name="payment_method" value="LIPA NUMBER" required>
                                <label for="lipa" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="payment-name">LIPA</div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="cash" name="payment_method" value="CASH" required>
                                <label for="cash" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-name">CASH</div>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="crdb" name="payment_method" value="CRDB BANK" required>
                                <label for="crdb" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-name">CRDB</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="fas fa-shopping-cart"></i>
                        Complete Sale
                    </button>
                    <button type="button" class="btn btn-secondary" id="resetBtn">
                        <i class="fas fa-redo"></i>
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Panel - Summary & Stats -->
        <div class="sale-summary-panel">
            <!-- Total Display -->
            <div class="summary-card">
                <div class="total-display">
                    <div class="total-label">Total Amount</div>
                    <div class="total-amount" id="totalAmount">0 TZS</div>
                </div>
            </div>

            <!-- Today's Stats -->
            <div class="summary-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    Today's Stats
                </h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Sales</span>
                        <span class="stat-value"><?php echo number_format($today_sales); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Revenue</span>
                        <span class="stat-value"><?php echo number_format($today_revenue); ?> TZS</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Items</span>
                        <span class="stat-value"><?php echo number_format($today_items); ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="summary-card">
                <h3>
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
                <div class="quick-actions">
                    <a href="sales_history.php" class="btn btn-secondary">
                        <i class="fas fa-history"></i>
                        History
                    </a>
                    <a href="../reports/reports.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const productSearch = document.getElementById('product-search');
    const productDropdown = document.getElementById('productDropdown');
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalAmount = document.getElementById('totalAmount');
    const submitBtn = document.getElementById('submitBtn');
    const resetBtn = document.getElementById('resetBtn');
    const form = document.getElementById('salesForm');
    const stockHelper = document.getElementById('stockHelper');
    
    let selectedProduct = null;

    // Utility Functions
    function formatTZS(amount) {
        return new Intl.NumberFormat('en-US').format(Math.round(amount)) + ' TZS';
    }

    function formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function parsePrice(priceString) {
        return parseFloat(priceString.replace(/,/g, '')) || 0;
    }

    function calculateTotal() {
        const unitPrice = parsePrice(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value) || 0;
        const total = unitPrice * quantity;
        totalAmount.textContent = formatTZS(total);
        updateSubmitButton();
    }

    function updateSubmitButton() {
        const hasProduct = productSelect.value !== '';
        const hasQuantity = quantityInput.value !== '' && parseInt(quantityInput.value) > 0;
        const hasUnitPrice = unitPriceInput.value !== '' && parsePrice(unitPriceInput.value) > 0;
        const hasPaymentMethod = document.querySelector('input[name="payment_method"]:checked') !== null;
        
        const isValid = hasProduct && hasQuantity && hasUnitPrice && hasPaymentMethod;
        
        submitBtn.disabled = !isValid;
        
        if (isValid) {
            submitBtn.style.opacity = '1';
            submitBtn.style.transform = 'scale(1)';
            submitBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            submitBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Complete Sale';
        } else {
            submitBtn.style.opacity = '0.6';
            submitBtn.style.transform = 'scale(0.98)';
            submitBtn.style.background = '#94a3b8';
            
            // Show what's missing
            if (!hasProduct) {
                submitBtn.innerHTML = '<i class="fas fa-tshirt"></i> Select Product';
            } else if (!hasQuantity) {
                submitBtn.innerHTML = '<i class="fas fa-sort-numeric-up"></i> Enter Quantity';
            } else if (!hasUnitPrice) {
                submitBtn.innerHTML = '<i class="fas fa-money-bill-wave"></i> Enter Price';
            } else if (!hasPaymentMethod) {
                submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Select Payment';
            }
        }
    }

    function resetForm() {
        productSearch.value = '';
        productSelect.value = '';
        quantityInput.value = '';
        unitPriceInput.value = '';
        totalAmount.textContent = '0 TZS';
        stockHelper.style.display = 'none';
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.checked = false;
        });
        document.querySelectorAll('.payment-option').forEach(option => {
            option.classList.remove('payment-selected');
        });
        selectedProduct = null;
        updateSubmitButton();
        productSearch.focus();
    }

    // Product Search and Selection
    productSearch.addEventListener('click', function() {
        productDropdown.classList.add('show');
    });

    productDropdown.addEventListener('click', function(e) {
        const option = e.target.closest('.product-option');
        if (option) {
            const value = option.getAttribute('data-value');
            const name = option.getAttribute('data-name');
            const price = option.getAttribute('data-price');
            const stock = option.getAttribute('data-stock');
            
            productSearch.value = name;
            productSelect.value = value;
            selectedProduct = { value, name, price, stock };
            
            // Update UI
            productDropdown.classList.remove('show');
            quantityInput.setAttribute('max', stock);
            unitPriceInput.value = formatPrice(price);
            
            // Show stock helper
            stockHelper.textContent = `Available: ${parseInt(stock)} units`;
            stockHelper.style.display = 'block';
            
            calculateTotal();
            quantityInput.focus();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.product-search-container')) {
            productDropdown.classList.remove('show');
        }
    });

    // Quantity Input
    quantityInput.addEventListener('input', function() {
        if (selectedProduct) {
            const stock = parseInt(selectedProduct.stock);
            const quantity = parseInt(this.value);
            
            if (quantity > stock) {
                this.value = stock;
                showNotification(`Maximum available: ${stock}`, 'warning');
            }
        }
        calculateTotal();
    });

    // Unit Price Input with formatting
    unitPriceInput.addEventListener('input', function() {
        let value = this.value.replace(/[^\d.,]/g, '');
        let parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Add commas
        const cursorPos = this.selectionStart;
        const valueBeforeCursor = this.value.substring(0, cursorPos).replace(/,/g, '');
        const formattedValue = formatPrice(value);
        const commasBeforeCursor = (valueBeforeCursor.match(/,/g) || []).length;
        const newCommasBeforeCursor = (formattedValue.substring(0, valueBeforeCursor.length + commasBeforeCursor).match(/,/g) || []).length;
        const newCursorPos = cursorPos + (newCommasBeforeCursor - commasBeforeCursor);
        
        this.value = formattedValue;
        this.setSelectionRange(newCursorPos, newCursorPos);
        
        calculateTotal();
    });

    // Payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateSubmitButton();
            
            // Visual feedback for payment method selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('payment-selected');
            });
            this.closest('.payment-option').classList.add('payment-selected');
            
            // Show confirmation of payment method selection
            const paymentMethod = this.value;
            showNotification(`Payment: ${paymentMethod}`, 'success');
        });
    });

    // Reset button
    resetBtn.addEventListener('click', resetForm);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedProduct) {
            showNotification('Please select a product', 'error');
            return;
        }
        
        const quantity = parseInt(quantityInput.value);
        const unitPrice = parsePrice(unitPriceInput.value);
        const total = unitPrice * quantity;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (quantity > parseInt(selectedProduct.stock)) {
            showNotification(`Not enough stock. Max: ${selectedProduct.stock}`, 'error');
            return;
        }
        
        const confirmed = confirm(`Confirm Sale:

Product: ${selectedProduct.name}
Quantity: ${quantity}
Unit Price: ${formatTZS(unitPrice)}
Total: ${formatTZS(total)}
Payment: ${paymentMethod}

Proceed?`);
        
        if (confirmed) {
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Clean unit price for submission
            const cleanPrice = parsePrice(unitPriceInput.value);
            const formData = new FormData(this);
            formData.set('unit_price', cleanPrice.toString());
            
            // Submit form
            fetch(this.action || window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                document.documentElement.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Complete Sale';
                submitBtn.disabled = false;
                showNotification('Error processing sale. Please try again.', 'error');
            });
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            productSearch.focus();
        }
        if (e.key === 'F3') {
            e.preventDefault();
            quantityInput.focus();
        }
        if (e.key === 'F4') {
            e.preventDefault();
            unitPriceInput.focus();
        }
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            resetForm();
        }
    });

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `<i class="fas fa-${getIconForType(type)}"></i> ${message}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: slideIn 0.3s ease;
            cursor: pointer;
            font-size: 0.85rem;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        });
    }

    function getIconForType(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Initialize
    updateSubmitButton();
    productSearch.focus();
});
</script>

<?php include '../includes/footer.php'; ?>