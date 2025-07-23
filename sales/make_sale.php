<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$success_message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval(str_replace(',', '', $_POST['unit_price'])); // Remove commas before processing
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
            
            // Calculate total price using the manually entered unit price
            $total_price = $unit_price * $quantity;
            
            // Record sale with the payment method
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
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Sale completed successfully! Product: " . htmlspecialchars($product['name']) . 
                             ", Quantity: " . number_format($quantity) . 
                             ", Unit Price: " . number_format($unit_price) . " TZS" .
                             ", Total: " . number_format($total_price) . " TZS" .
                             ", Payment: " . htmlspecialchars($payment_method);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get products with stock for dropdown
$products = $conn->query("SELECT product_id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-shopping-cart"></i> Make a Sale</h2>

<div class="card">
    <div class="card-header">
        <h3>Process Sale Transaction</h3>
        <a href="sales_history.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> Sales History
        </a>
    </div>
    <div class="card-body">
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
                <div class="form-left">
                    <!-- Product Selection -->
                    <div class="form-group">
                        <label for="product-search"><i class="fas fa-tshirt"></i> Select Jersey, Cap or Shorts *</label>
                        <div class="searchable-dropdown-container">
                            <div class="searchable-dropdown" id="productDropdown">
                                <input type="text" 
                                       id="product-search" 
                                       class="dropdown-input" 
                                       placeholder="Type to search or click to browse products..." 
                                       autocomplete="off"
                                       readonly>
                                <div class="dropdown-arrow" id="dropdownArrow">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                
                                <div class="dropdown-menu" id="dropdownMenu">
                                    <div class="search-header">
                                        <input type="text" 
                                               id="search-filter" 
                                               class="search-input" 
                                               placeholder="Search products..."
                                               autocomplete="off">
                                    </div>
                                    
                                    <div class="options-list" id="optionsList">
                                        <div class="option-item placeholder" data-value="" data-name="">
                                            <div class="option-content">
                                                <div class="product-name">Choose a product...</div>
                                            </div>
                                        </div>
                                        
                                        <?php while ($product = $products->fetch_assoc()): ?>
                                        <div class="option-item" 
                                             data-value="<?php echo $product['product_id']; ?>"
                                             data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                             data-price="<?php echo $product['price']; ?>"
                                             data-stock="<?php echo $product['stock_quantity']; ?>"
                                             data-search="<?php echo strtolower(htmlspecialchars($product['name'])); ?>">
                                            <div class="option-content">
                                                <div class="product-info">
                                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="product-price">Suggested: <?php echo number_format($product['price']); ?> TZS</div>
                                                </div>
                                                <div class="stock-info">
                                                    <span class="stock-badge <?php 
                                                        echo $product['stock_quantity'] > 20 ? 'high' : 
                                                            ($product['stock_quantity'] > 0 ? 'low' : 'out'); 
                                                    ?>">
                                                        <?php echo number_format($product['stock_quantity']); ?> in stock
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    
                                    <div class="no-results" id="noResults" style="display: none;">
                                        <i class="fas fa-search"></i>
                                        <span>No products found</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden select for form submission -->
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
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               class="form-control" 
                               min="1" 
                               required 
                               placeholder="Enter quantity">
                        <div id="stock-warning" class="stock-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="warning-text"></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-right">
                    <!-- Unit Price Input -->
                    <div class="form-group">
                        <label for="unit_price"><i class="fas fa-money-bill-wave"></i> Unit Price (TZS) *</label>
                        <input type="text" 
                               id="unit_price" 
                               name="unit_price" 
                               class="form-control price-input" 
                               required 
                               placeholder="Enter unit price">
                        <div id="suggested-price" class="suggested-price" style="display: none;">
                            <i class="fas fa-lightbulb"></i>
                            <span>Suggested: <span id="suggested-price-value"></span></span>
                        </div>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="form-group">
                        <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method *</label>
                        <div class="payment-methods" id="paymentMethods">
                            <div class="payment-option" data-method="LIPA NUMBER">
                                <input type="radio" id="lipa" name="payment_method" value="LIPA NUMBER" required>
                                <label for="lipa" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">LIPA NUMBER</div>
                                        <div class="payment-desc">Mobile Money</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option" data-method="CASH">
                                <input type="radio" id="cash" name="payment_method" value="CASH" required>
                                <label for="cash" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">CASH</div>
                                        <div class="payment-desc">Cash Payment</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-option" data-method="CRDB BANK">
                                <input type="radio" id="crdb" name="payment_method" value="CRDB BANK" required>
                                <label for="crdb" class="payment-label">
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">CRDB BANK</div>
                                        <div class="payment-desc">Bank Transfer</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount Display -->
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> Total Amount</label>
                        <div id="total-display" class="total-display">
                            0 TZS
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                    <i class="fas fa-shopping-cart"></i> Complete Sale
                </button>
                <button type="button" class="btn btn-secondary" id="reset-btn">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Today's Sales Statistics -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Today's Sales Statistics</h3>
    </div>
    <div class="card-body">
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()");
                        echo number_format($stmt->fetch_row()[0]);
                        ?>
                    </div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE()");
                        echo number_format($stmt->fetch_row()[0] ?? 0);
                        ?> TZS
                    </div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
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
    </div>
</div>

<!-- Popular Items Today -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-fire"></i> Popular Items Today</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT p.name, SUM(s.quantity) as units_sold, SUM(s.total_price) as revenue
                        FROM sales s 
                        JOIN products p ON s.product_id = p.product_id 
                        WHERE DATE(s.sale_date) = CURDATE()
                        GROUP BY p.product_id, p.name
                        ORDER BY units_sold DESC
                        LIMIT 5
                    ");
                    
                    if ($stmt->num_rows > 0):
                        while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo number_format($row['units_sold']); ?></td>
                        <td class="text-success"><?php echo number_format($row['revenue']); ?> TZS</td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">No sales recorded today</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Form Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

/* Searchable Dropdown */
.searchable-dropdown-container {
    position: relative;
}

.searchable-dropdown {
    position: relative;
    cursor: pointer;
}

.dropdown-input {
    cursor: pointer;
    padding-right: 3rem;
}

.dropdown-input:focus {
    cursor: text;
}

.dropdown-arrow {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    transition: transform 0.3s ease;
    pointer-events: none;
}

.dropdown-arrow.open {
    transform: translateY(-50%) rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e1e8ed;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 400px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.dropdown-menu.show {
    display: block;
    animation: dropdownSlide 0.2s ease;
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

.search-header {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    background: #f8f9fa;
}

.search-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e1e8ed;
    border-radius: 6px;
    font-size: 0.9rem;
    outline: none;
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.options-list {
    max-height: 300px;
    overflow-y: auto;
}

.option-item {
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.option-item:last-child {
    border-bottom: none;
}

.option-item:hover,
.option-item.focused {
    background: rgba(102, 126, 234, 0.08);
}

.option-item.selected {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
}

.option-item.placeholder {
    color: #6c757d;
    font-style: italic;
}

.option-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.product-price {
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 500;
}

.stock-info {
    flex-shrink: 0;
}

.stock-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.stock-badge.high {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
}

.stock-badge.low {
    background: rgba(255, 193, 7, 0.15);
    color: #e67e22;
}

.stock-badge.out {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

.no-results {
    padding: 3rem 2rem;
    text-align: center;
    color: #6c757d;
}

.no-results i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
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
    align-items: center;
    padding: 1rem;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
    gap: 1rem;
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

.payment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.payment-option[data-method="LIPA NUMBER"] .payment-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.payment-option[data-method="CASH"] .payment-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.payment-option[data-method="CRDB BANK"] .payment-icon {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.payment-info {
    flex: 1;
}

.payment-name {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.payment-desc {
    font-size: 0.85rem;
    color: #6c757d;
}

.payment-option input[type="radio"]:checked + .payment-label .payment-name {
    color: #667eea;
}

.payment-option input[type="radio"]:checked + .payment-label .payment-icon {
    transform: scale(1.1);
}

/* Price Input */
.price-input {
    font-family: 'Courier New', monospace;
    text-align: right;
    font-size: 1.1rem;
}

/* Suggested Price */
.suggested-price {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 6px;
    font-size: 0.85rem;
    color: #856404;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: fadeIn 0.3s ease;
}

.suggested-price i {
    color: #ffc107;
}

.suggested-price span span {
    font-weight: 600;
    color: #28a745;
}

/* Stock Warning */
.stock-warning {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(220, 53, 69, 0.1);
    border-radius: 6px;
    font-size: 0.85rem;
    color: #721c24;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: pulse 2s infinite;
}

.stock-warning i {
    color: #dc3545;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Total Display */
.total-display {
    padding: 1rem;
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
    border: 2px solid #2ecc71;
    border-radius: 12px;
    font-size: 1.8rem;
    font-weight: bold;
    color: #2ecc71;
    text-align: center;
    transition: all 0.3s ease;
}

.total-display.updating {
    transform: scale(1.05);
}

/* Form Actions */
.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e1e8ed;
    display: flex;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
    color: white;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(149, 165, 166, 0.4);
}

/* Stats Container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-box {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    border-radius: 12px;
    border-left: 4px solid #667eea;
    transition: transform 0.2s ease;
}

.stat-box:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 1rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .option-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .stock-info {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .total-display {
        font-size: 1.5rem;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Scrollbar */
.options-list::-webkit-scrollbar {
    width: 6px;
}

.options-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.options-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.options-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// Utility Functions
function formatTZS(amount) {
    return new Intl.NumberFormat('en-US').format(Math.round(amount)) + ' TZS';
}

function formatPrice(price) {
    // Format price with commas, preserving cursor position
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function parsePrice(priceString) {
    // Remove commas and convert to number
    return parseFloat(priceString.replace(/,/g, '')) || 0;
}

function addCommasToInput(input) {
    let value = input.value.replace(/,/g, ''); // Remove existing commas
    let cursorPos = input.selectionStart;
    let valueBeforeCursor = input.value.substring(0, cursorPos).replace(/,/g, '');
    
    // Format with commas
    let formattedValue = formatPrice(value);
    
    // Calculate new cursor position
    let commasBeforeCursor = (valueBeforeCursor.match(/,/g) || []).length;
    let newCommasBeforeCursor = (formattedValue.substring(0, valueBeforeCursor.length + commasBeforeCursor).match(/,/g) || []).length;
    let newCursorPos = cursorPos + (newCommasBeforeCursor - commasBeforeCursor);
    
    // Update input
    input.value = formattedValue;
    input.setSelectionRange(newCursorPos, newCursorPos);
}

// Searchable Dropdown Class
class SearchableDropdown {
    constructor(containerId, hiddenSelectId) {
        this.container = document.getElementById(containerId);
        this.hiddenSelect = document.getElementById(hiddenSelectId);
        this.input = document.getElementById('product-search');
        this.arrow = document.getElementById('dropdownArrow');
        this.menu = document.getElementById('dropdownMenu');
        this.searchFilter = document.getElementById('search-filter');
        this.optionsList = document.getElementById('optionsList');
        this.noResults = document.getElementById('noResults');
        
        this.isOpen = false;
        this.focusedIndex = -1;
        this.selectedOption = null;
        this.options = Array.from(this.optionsList.querySelectorAll('.option-item'));
        this.filteredOptions = [];
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateFilteredOptions();
    }
    
    bindEvents() {
        // Input click to open dropdown
        this.input.addEventListener('click', () => this.open());
        
        // Arrow click to toggle
        this.arrow.addEventListener('click', () => this.toggle());
        
        // Search filter
        this.searchFilter.addEventListener('input', (e) => this.filterOptions(e.target.value));
        
        // Option selection
        this.optionsList.addEventListener('click', (e) => {
            const option = e.target.closest('.option-item');
            if (option) this.selectOption(option);
        });
        
        // Keyboard navigation
        this.searchFilter.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.open();
            }
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) this.close();
        });
        
        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
                this.input.focus();
            }
        });
    }
    
    open() {
        this.isOpen = true;
        this.menu.classList.add('show');
        this.arrow.classList.add('open');
        this.searchFilter.value = '';
        this.filterOptions('');
        setTimeout(() => this.searchFilter.focus(), 100);
    }
    
    close() {
        this.isOpen = false;
        this.menu.classList.remove('show');
        this.arrow.classList.remove('open');
        this.focusedIndex = -1;
        this.updateFocusedOption();
    }
    
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
    
    filterOptions(searchTerm) {
        this.filteredOptions = [];
        let hasResults = false;
        
        this.options.forEach(option => {
            const searchData = option.getAttribute('data-search') || '';
            const name = option.getAttribute('data-name') || '';
            const isPlaceholder = option.classList.contains('placeholder');
            
            if (isPlaceholder || 
                searchData.includes(searchTerm.toLowerCase()) || 
                name.toLowerCase().includes(searchTerm.toLowerCase())) {
                option.style.display = 'block';
                if (!isPlaceholder) {
                    this.filteredOptions.push(option);
                    hasResults = true;
                }
            } else {
                option.style.display = 'none';
            }
        });
        
        this.noResults.style.display = (searchTerm && !hasResults) ? 'block' : 'none';
        this.focusedIndex = -1;
        this.updateFocusedOption();
    }
    
    updateFilteredOptions() {
        this.filteredOptions = this.options.filter(option => 
            !option.classList.contains('placeholder') && 
            option.style.display !== 'none'
        );
    }
    
    handleKeyDown(e) {
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.focusNext();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.focusPrevious();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.focusedIndex >= 0) {
                    this.selectOption(this.filteredOptions[this.focusedIndex]);
                }
                break;
            case 'Tab':
                this.close();
                break;
        }
    }
    
    focusNext() {
        if (this.filteredOptions.length === 0) return;
        this.focusedIndex = (this.focusedIndex + 1) % this.filteredOptions.length;
        this.updateFocusedOption();
    }
    
    focusPrevious() {
        if (this.filteredOptions.length === 0) return;
        this.focusedIndex = this.focusedIndex <= 0 ? 
            this.filteredOptions.length - 1 : this.focusedIndex - 1;
        this.updateFocusedOption();
    }
    
    updateFocusedOption() {
        this.options.forEach(option => option.classList.remove('focused'));
        
        if (this.focusedIndex >= 0 && this.filteredOptions[this.focusedIndex]) {
            const focused = this.filteredOptions[this.focusedIndex];
            focused.classList.add('focused');
            focused.scrollIntoView({ block: 'nearest' });
        }
    }
    
    selectOption(option) {
        const value = option.getAttribute('data-value');
        const name = option.getAttribute('data-name');
        
        // Clear previous selection
        this.options.forEach(opt => opt.classList.remove('selected'));
        
        if (value) {
            option.classList.add('selected');
            this.input.value = name;
            this.selectedOption = option;
        } else {
            this.input.value = '';
            this.selectedOption = null;
        }
        
        // Update hidden select
        this.hiddenSelect.value = value || '';
        
        // Trigger change event
        this.hiddenSelect.dispatchEvent(new Event('change'));
        
        this.close();
        
        // Focus quantity input
        if (value) {
            document.getElementById('quantity').focus();
        }
    }
    
    reset() {
        const placeholder = this.options.find(opt => opt.classList.contains('placeholder'));
        if (placeholder) this.selectOption(placeholder);
    }
    
    getValue() {
        return this.hiddenSelect.value;
    }
    
    getSelectedData() {
        if (!this.selectedOption) return null;
        
        return {
            value: this.selectedOption.getAttribute('data-value'),
            name: this.selectedOption.getAttribute('data-name'),
            price: this.selectedOption.getAttribute('data-price'),
            stock: this.selectedOption.getAttribute('data-stock')
        };
    }
}

// Main Application
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const salesForm = document.getElementById('sales-form');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalDisplay = document.getElementById('total-display');
    const stockWarning = document.getElementById('stock-warning');
    const suggestedPrice = document.getElementById('suggested-price');
    const suggestedPriceValue = document.getElementById('suggested-price-value');
    const submitBtn = document.getElementById('submit-btn');
    const resetBtn = document.getElementById('reset-btn');
    
    // Initialize dropdown
    const productDropdown = new SearchableDropdown('productDropdown', 'product_id');
    
    // Functions
    function calculateTotal() {
        const unitPrice = parsePrice(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value) || 0;
        const total = unitPrice * quantity;
        
        totalDisplay.textContent = formatTZS(total);
        
        // Visual feedback
        if (total > 0) {
            totalDisplay.classList.add('updating');
            setTimeout(() => totalDisplay.classList.remove('updating'), 200);
        }
    }
    
    function updateProductInfo() {
        const selectedData = productDropdown.getSelectedData();
        
        if (selectedData && selectedData.value) {
            const productPrice = parseFloat(selectedData.price);
            const stock = parseInt(selectedData.stock);
            
            // Show suggested price
            suggestedPriceValue.textContent = formatTZS(productPrice);
            suggestedPrice.style.display = 'block';
            
            // Auto-fill unit price if empty
            if (!unitPriceInput.value.trim()) {
                unitPriceInput.value = formatPrice(productPrice);
            }
            
            // Update quantity constraints
            quantityInput.setAttribute('max', stock);
            if (parseInt(quantityInput.value) > stock) {
                quantityInput.value = stock;
                showNotification(`Quantity adjusted to maximum available: ${stock}`, 'warning');
            }
            
            updateStockWarning(stock);
        } else {
            suggestedPrice.style.display = 'none';
            unitPriceInput.value = '';
            stockWarning.style.display = 'none';
            quantityInput.removeAttribute('max');
        }
        
        calculateTotal();
        updateSubmitButton();
    }
    
    function updateStockWarning(stock) {
        const warningText = stockWarning.querySelector('.warning-text');
        
        if (stock <= 0) {
            stockWarning.style.display = 'block';
            warningText.textContent = 'Product is out of stock!';
        } else if (stock <= 10) {
            stockWarning.style.display = 'block';
            warningText.textContent = `Only ${stock} units remaining`;
        } else {
            stockWarning.style.display = 'none';
        }
    }
    
    function updateSubmitButton() {
        const hasProduct = productDropdown.getValue() !== '';
        const hasQuantity = quantityInput.value !== '' && parseInt(quantityInput.value) > 0;
        const hasUnitPrice = unitPriceInput.value !== '' && parsePrice(unitPriceInput.value) > 0;
        const hasPaymentMethod = document.querySelector('input[name="payment_method"]:checked') !== null;
        
        submitBtn.disabled = !(hasProduct && hasQuantity && hasUnitPrice && hasPaymentMethod);
    }
    
    function validateForm() {
        const selectedData = productDropdown.getSelectedData();
        const quantity = parseInt(quantityInput.value) || 0;
        const unitPrice = parsePrice(unitPriceInput.value);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedData || !selectedData.value) {
            showNotification('Please select a product', 'error');
            return false;
        }
        
        if (quantity <= 0) {
            showNotification('Please enter a valid quantity', 'error');
            quantityInput.focus();
            return false;
        }
        
        if (unitPrice <= 0) {
            showNotification('Please enter a valid unit price', 'error');
            unitPriceInput.focus();
            return false;
        }
        
        if (!paymentMethod) {
            showNotification('Please select a payment method', 'error');
            document.getElementById('paymentMethods').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        
        const stock = parseInt(selectedData.stock);
        if (quantity > stock) {
            showNotification(`Not enough stock available. Maximum: ${stock}`, 'error');
            quantityInput.focus();
            return false;
        }
        
        return true;
    }
    
    function confirmSale() {
        const selectedData = productDropdown.getSelectedData();
        const quantity = parseInt(quantityInput.value);
        const unitPrice = parsePrice(unitPriceInput.value);
        const total = unitPrice * quantity;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        return confirm(`Confirm sale:

Product: ${selectedData.name}
Quantity: ${quantity}
Unit Price: ${formatTZS(unitPrice)}
Total Amount: ${formatTZS(total)}
Payment Method: ${paymentMethod}

Proceed with this sale?`);
    }
    
    function resetForm() {
        productDropdown.reset();
        quantityInput.value = '';
        unitPriceInput.value = '';
        totalDisplay.textContent = '0 TZS';
        stockWarning.style.display = 'none';
        suggestedPrice.style.display = 'none';
        
        // Reset payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.checked = false;
        });
        
        updateSubmitButton();
        
        // Focus product search
        document.getElementById('product-search').focus();
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `<i class="fas fa-${getIconForType(type)}"></i> ${message}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
            cursor: pointer;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
        
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
    
    // Event Listeners
    
    // Product selection change
    document.getElementById('product_id').addEventListener('change', updateProductInfo);
    
    // Quantity input
    quantityInput.addEventListener('input', function() {
        const selectedData = productDropdown.getSelectedData();
        if (selectedData) {
            const stock = parseInt(selectedData.stock);
            const quantity = parseInt(this.value);
            
            if (quantity > stock) {
                this.value = stock;
                showNotification(`Maximum available: ${stock}`, 'warning');
            }
            
            updateStockWarning(stock);
        }
        
        calculateTotal();
        updateSubmitButton();
    });
    
    quantityInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            unitPriceInput.focus();
        }
    });
    
    // Unit price input with real-time comma formatting
    unitPriceInput.addEventListener('input', function(e) {
        // Only allow numbers, commas, and decimal points
        let value = this.value.replace(/[^\d.,]/g, '');
        
        // Handle multiple decimal points
        let parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Add commas as user types
        addCommasToInput(this);
        
        calculateTotal();
        updateSubmitButton();
    });
    
    unitPriceInput.addEventListener('keydown', function(e) {
        // Allow navigation and editing keys
        const allowedKeys = [
            'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
            'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
        ];
        
        if (allowedKeys.includes(e.key)) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Focus first payment method radio button
                document.getElementById('lipa').focus();
            }
            return;
        }
        
        // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }
        
        // Allow numbers and decimal point
        if (!/[\d.]/.test(e.key)) {
            e.preventDefault();
        }
    });
    
    // Payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateSubmitButton();
            
            // Visual feedback for selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            this.closest('.payment-option').classList.add('selected');
        });
        
        radio.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !submitBtn.disabled) {
                e.preventDefault();
                salesForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    });
    
    unitPriceInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            addCommasToInput(this);
            calculateTotal();
            updateSubmitButton();
        }, 10);
    });
    
    // Reset button
    resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        resetForm();
        showNotification('Form reset successfully', 'info');
    });
    
    // Form submission
    salesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        if (!confirmSale()) {
            return;
        }
        
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Clean unit price value for submission
        const cleanPrice = parsePrice(unitPriceInput.value);
        
        // Create form data
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
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showNotification('Error processing sale. Please try again.', 'error');
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            resetForm();
            showNotification('Form reset with Ctrl+R', 'info');
        }
        
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('product-search').focus();
        }
        
        if (e.key === 'F3') {
            e.preventDefault();
            quantityInput.focus();
        }
        
        if (e.key === 'F4') {
            e.preventDefault();
            unitPriceInput.focus();
        }
        
        if (e.key === 'F5') {
            e.preventDefault();
            document.getElementById('lipa').focus();
        }
    });
    
    // Initialize
    updateSubmitButton();
    
    // Auto-focus on page load
    setTimeout(() => {
        document.getElementById('product-search').focus();
    }, 500);
    
    // Add notification styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border-color: #2ecc71;
            color: #27ae60;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            border-color: #e74c3c;
            color: #c0392b;
        }
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            border-color: #f39c12;
            color: #e67e22;
        }
        
        .alert-info {
            background: rgba(52, 152, 219, 0.1);
            border-color: #3498db;
            color: #2980b9;
        }
    `;
    document.head.appendChild(style);
    
    console.log('Enhanced Make Sale system initialized successfully');
});
</script>

<?php include '../includes/footer.php'; ?>