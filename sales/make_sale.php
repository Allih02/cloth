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

// Get products with stock for dropdown - include categories for better organization
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
                    <!-- Enhanced Product Selection -->
                    <div class="form-group">
                        <label for="product-search">
                            <i class="fas fa-tshirt"></i> Select Jersey, Cap or Shorts *
                            <span class="help-text">Click to browse or start typing to search</span>
                        </label>
                        
                        <div class="enhanced-product-selector" id="productSelector">
                            <!-- Main search input -->
                            <div class="search-input-container">
                                <input type="text" 
                                       id="product-search" 
                                       class="search-input" 
                                       placeholder="🔍 Click to browse products or type to search (jerseys, caps, shorts)..." 
                                       autocomplete="off"
                                       spellcheck="false">
                                <div class="search-actions">
                                    <button type="button" class="clear-btn" id="clearSearch" title="Clear search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button type="button" class="dropdown-toggle" id="dropdownToggle" title="Browse all products">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Dropdown menu -->
                            <div class="dropdown-menu" id="dropdownMenu">
                                <!-- Search tips -->
                                <div class="search-tips" id="searchTips">
                                    <div class="tip-item">
                                        <i class="fas fa-keyboard"></i>
                                        <span>Type product name, brand, or category</span>
                                    </div>
                                    <div class="tip-item">
                                        <i class="fas fa-arrow-up-down"></i>
                                        <span>Use ↑↓ arrow keys to navigate</span>
                                    </div>
                                    <div class="tip-item">
                                        <i class="fas fa-level-down-alt"></i>
                                        <span>Press Enter to select</span>
                                    </div>
                                </div>
                                
                                <!-- Quick filters -->
                                <div class="quick-filters" id="quickFilters">
                                    <div class="filter-label">Quick Filter:</div>
                                    <button type="button" class="filter-btn" data-category="Men's Clothing">
                                        <i class="fas fa-male"></i> Men's
                                    </button>
                                    <button type="button" class="filter-btn" data-category="Women's Clothing">
                                        <i class="fas fa-female"></i> Women's
                                    </button>
                                    <button type="button" class="filter-btn" data-category="Children's Clothing">
                                        <i class="fas fa-child"></i> Kids
                                    </button>
                                    <button type="button" class="filter-btn" data-category="">
                                        <i class="fas fa-list"></i> All
                                    </button>
                                </div>
                                
                                <!-- Products list -->
                                <div class="products-container" id="productsContainer">
                                    <div class="products-header">
                                        <div class="results-count" id="resultsCount">
                                            <?php echo $products->num_rows; ?> products available
                                        </div>
                                    </div>
                                    
                                    <div class="products-list" id="productsList">
                                        <?php 
                                        $current_category = '';
                                        while ($product = $products->fetch_assoc()): 
                                            // Group by category
                                            if ($current_category !== $product['category_name']):
                                                if ($current_category !== ''):
                                                    echo '</div>'; // Close previous category
                                                endif;
                                                $current_category = $product['category_name'];
                                        ?>
                                            <div class="category-group" data-category="<?php echo htmlspecialchars($current_category); ?>">
                                                <div class="category-header">
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo htmlspecialchars($current_category ?: 'Other'); ?>
                                                </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-item" 
                                             data-product-id="<?php echo $product['product_id']; ?>"
                                             data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                             data-price="<?php echo $product['price']; ?>"
                                             data-stock="<?php echo $product['stock_quantity']; ?>"
                                             data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
                                             data-size="<?php echo htmlspecialchars($product['size']); ?>"
                                             data-color="<?php echo htmlspecialchars($product['color']); ?>"
                                             data-brand="<?php echo htmlspecialchars($product['brand']); ?>"
                                             data-search="<?php echo strtolower(htmlspecialchars($product['name'] . ' ' . $product['brand'] . ' ' . $product['color'] . ' ' . $product['size'] . ' ' . $product['category_name'])); ?>">
                                            
                                            <div class="product-image">
                                                <div class="image-placeholder">
                                                    <i class="fas fa-tshirt"></i>
                                                </div>
                                                <div class="stock-indicator <?php 
                                                    echo $product['stock_quantity'] > 20 ? 'high' : 
                                                        ($product['stock_quantity'] > 5 ? 'medium' : 'low'); 
                                                ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="product-details">
                                                <div class="product-name">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </div>
                                                <div class="product-meta">
                                                    <span class="brand"><?php echo htmlspecialchars($product['brand']); ?></span>
                                                    <span class="separator">•</span>
                                                    <span class="size">Size: <?php echo htmlspecialchars($product['size']); ?></span>
                                                    <span class="separator">•</span>
                                                    <span class="color">
                                                        <span class="color-dot" style="background-color: <?php echo strtolower($product['color']); ?>"></span>
                                                        <?php echo htmlspecialchars($product['color']); ?>
                                                    </span>
                                                </div>
                                                <div class="product-price">
                                                    <?php echo number_format($product['price']); ?> TZS
                                                </div>
                                            </div>
                                            
                                            <div class="product-actions">
                                                <div class="stock-status <?php 
                                                    echo $product['stock_quantity'] > 20 ? 'high' : 
                                                        ($product['stock_quantity'] > 5 ? 'medium' : 'low'); 
                                                ?>">
                                                    <span class="stock-text">
                                                        <?php echo $product['stock_quantity']; ?> in stock
                                                    </span>
                                                </div>
                                                <button type="button" class="select-btn">
                                                    <i class="fas fa-check"></i> Select
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <?php endwhile; ?>
                                        </div> <!-- Close last category -->
                                    </div>
                                    
                                    <!-- No results message -->
                                    <div class="no-results" id="noResults" style="display: none;">
                                        <div class="no-results-icon">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <div class="no-results-text">
                                            <h4>No products found</h4>
                                            <p>Try adjusting your search terms or browse all products</p>
                                        </div>
                                        <button type="button" class="btn-clear-search" onclick="clearSearch()">
                                            <i class="fas fa-times"></i> Clear Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selected product display -->
                            <div class="selected-product" id="selectedProduct" style="display: none;">
                                <div class="selected-info">
                                    <div class="selected-image">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                    <div class="selected-details">
                                        <div class="selected-name" id="selectedName"></div>
                                        <div class="selected-meta" id="selectedMeta"></div>
                                    </div>
                                </div>
                                <button type="button" class="change-btn" id="changeProduct">
                                    <i class="fas fa-edit"></i> Change
                                </button>
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
                                            data-stock="<?php echo $product['stock_quantity']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                        <div class="quantity-input-container">
                            <button type="button" class="quantity-btn minus" id="quantityMinus">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-control quantity-input" 
                                   min="1" 
                                   required 
                                   placeholder="0">
                            <button type="button" class="quantity-btn plus" id="quantityPlus">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
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
                        <div class="price-input-container">
                            <input type="text" 
                                   id="unit_price" 
                                   name="unit_price" 
                                   class="form-control price-input" 
                                   required 
                                   placeholder="0">
                            <span class="currency-symbol"></span>
                        </div>
                        <div id="suggested-price" class="suggested-price" style="display: none;">
                            <i class="fas fa-lightbulb"></i>
                            <span>Suggested: <span id="suggested-price-value"></span></span>
                            <button type="button" class="use-suggested-btn" id="useSuggestedPrice">
                                Use This Price
                            </button>
                        </div>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="form-group">
                        <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method *</label>
                        <div class="payment-methods" id="paymentMethods">
                            <div class="payment-option" data-method="CASH">
                                <input type="radio" id="cash" name="payment_method" value="CASH" required>
                                <label for="cash" class="payment-label">
                                    <div class="payment-icon cash">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">CASH</div>
                                        <div class="payment-desc">Cash Payment</div>
                                    </div>
                                    <div class="payment-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </label>
                            </div> 


                            <div class="payment-option" data-method="LIPA NUMBER">
                                <input type="radio" id="lipa" name="payment_method" value="LIPA NUMBER" required>
                                <label for="lipa" class="payment-label">
                                    <div class="payment-icon lipa">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">LIPA NUMBER</div>
                                        <div class="payment-desc">Mobile Money Payment</div>
                                    </div>
                                    <div class="payment-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </label>
                            </div>
                                                      
                            <div class="payment-option" data-method="CRDB BANK">
                                <input type="radio" id="crdb" name="payment_method" value="CRDB BANK" required>
                                <label for="crdb" class="payment-label">
                                    <div class="payment-icon bank">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">CRDB BANK</div>
                                        <div class="payment-desc">Bank Transfer</div>
                                    </div>
                                    <div class="payment-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount Display -->
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> Total Amount</label>
                        <div id="total-display" class="total-display">
                            <div class="total-amount">0</div>
                            <div class="total-currency">TZS</div>
                        </div>
                        <div class="total-breakdown" id="totalBreakdown" style="display: none;">
                            <div class="breakdown-item">
                                <span>Unit Price:</span>
                                <span id="breakdownUnitPrice">0 TZS</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Quantity:</span>
                                <span id="breakdownQuantity">0</span>
                            </div>
                            <div class="breakdown-separator"></div>
                            <div class="breakdown-item total">
                                <span>Total:</span>
                                <span id="breakdownTotal">0 TZS</span>
                            </div>
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
                <div class="keyboard-shortcuts">
                    <small>
                        <i class="fas fa-keyboard"></i> 
                        Press F2 to search products • F3 for quantity • F4 for price
                    </small>
                </div>
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

<style>
/* Enhanced Product Selector Styles */
.enhanced-product-selector {
    position: relative;
    width: 100%;
}

.help-text {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: normal;
    margin-left: 0.5rem;
}

.search-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 1rem 4rem 1rem 1rem;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    font-size: 1rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
    transition: all 0.3s ease;
    cursor: text;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.search-input::placeholder {
    color: #9ca3af;
    font-style: italic;
}

.search-actions {
    position: absolute;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
}

.clear-btn, .dropdown-toggle {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #6c757d;
}

.clear-btn:hover, .dropdown-toggle:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    transform: scale(1.1);
}

.dropdown-toggle.open {
    transform: rotate(180deg);
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e1e8ed;
    border-top: none;
    border-radius: 0 0 12px 12px;
    max-height: 600px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.dropdown-menu.show {
    display: block;
    animation: dropdownFadeIn 0.3s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-tips {
    padding: 1rem;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 1px solid #e2e8f0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.tip-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #64748b;
}

.tip-item i {
    color: #667eea;
    width: 16px;
}

.quick-filters {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
    margin-right: 0.5rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-1px);
}

.filter-btn.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.products-container {
    max-height: 400px;
    overflow-y: auto;
}

.products-header {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 500;
}

.category-group {
    border-bottom: 1px solid #f1f5f9;
}

.category-header {
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-header i {
    color: #667eea;
}

.product-item.selected {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(102, 126, 234, 0.1));
    border-left: 4px solid #667eea;
}

.product-item.focused {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    border-left: 4px solid #a5b4fc;
}

.product-image {
    position: relative;
    flex-shrink: 0;
}

.image-placeholder {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stock-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    padding: 0 4px;
}

.stock-indicator.high {
    background: #10b981;
}

.stock-indicator.medium {
    background: #f59e0b;
}

.stock-indicator.low {
    background: #ef4444;
}

.product-details {
    flex: 1;
    min-width: 0;
}

.product-name {
    font-weight: 600;
    font-size: 1rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.product-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
    flex-wrap: wrap;
}

.brand {
    font-weight: 500;
    color: #4f46e5;
}

.separator {
    color: #d1d5db;
}

.color {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid #d1d5db;
}

.product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #059669;
}

.product-actions {
    display: flex;
    flex-direction: column;
    align-items: end;
    gap: 0.5rem;
}

.stock-status {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.stock-status.high {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.stock-status.medium {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.stock-status.low {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.select-btn {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.select-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.no-results {
    padding: 3rem 2rem;
    text-align: center;
    color: #6b7280;
}

.no-results-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.no-results-text h4 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.no-results-text p {
    margin-bottom: 1.5rem;
}

.btn-clear-search {
    padding: 0.75rem 1.5rem;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-clear-search:hover {
    background: #e5e7eb;
}

.selected-product {
    margin-top: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    border: 2px solid #10b981;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    animation: selectedFadeIn 0.3s ease;
}

@keyframes selectedFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.selected-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.selected-image {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.selected-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.selected-meta {
    font-size: 0.85rem;
    color: #6b7280;
}

.change-btn {
    padding: 0.5rem 1rem;
    background: white;
    border: 2px solid #10b981;
    color: #059669;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.change-btn:hover {
    background: #10b981;
    color: white;
}

/* Quantity Input Enhancement */
.quantity-input-container {
    display: flex;
    align-items: center;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
}

.quantity-input-container:focus-within {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.quantity-btn {
    width: 45px;
    height: 45px;
    border: none;
    background: #f8fafc;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.quantity-btn:hover {
    background: #667eea;
    color: white;
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quantity-input {
    flex: 1;
    border: none;
    padding: 0.75rem;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    background: transparent;
}

.quantity-input:focus {
    outline: none;
}

/* Price Input Enhancement */
.price-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.price-input {
    padding-right: 4rem;
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    font-weight: 600;
    text-align: right;
}

.currency-symbol {
    position: absolute;
    right: 1rem;
    color: #6b7280;
    font-weight: 600;
    pointer-events: none;
}

.suggested-price {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    animation: suggestedFadeIn 0.3s ease;
}

@keyframes suggestedFadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.suggested-price i {
    color: #f59e0b;
}

.use-suggested-btn {
    padding: 0.5rem 1rem;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.use-suggested-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
}

/* Enhanced Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
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
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
    gap: 1rem;
    position: relative;
}

.payment-label:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(102, 126, 234, 0.02));
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.payment-option input[type="radio"]:checked + .payment-label {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.payment-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.payment-icon.lipa {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.payment-icon.cash {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.payment-icon.bank {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.payment-info {
    flex: 1;
}

.payment-name {
    font-weight: 700;
    font-size: 1rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.payment-desc {
    font-size: 0.85rem;
    color: #6b7280;
}

.payment-check {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    transition: all 0.3s ease;
}

.payment-option input[type="radio"]:checked + .payment-label .payment-check {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.payment-option input[type="radio"]:checked + .payment-label .payment-icon {
    transform: scale(1.1);
}

/* Enhanced Total Display */
.total-display {
    padding: 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

.total-display::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.total-amount {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 0.25rem;
    position: relative;
    z-index: 1;
}

.total-currency {
    font-size: 1rem;
    font-weight: 600;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.total-breakdown {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.breakdown-item.total {
    font-weight: 700;
    font-size: 1rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.breakdown-separator {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0.5rem 0;
}

/* Stock Warning */
.stock-warning {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
    border: 1px solid #fecaca;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: warningPulse 2s infinite;
}

@keyframes warningPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.stock-warning i {
    color: #ef4444;
}

/* Form Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 2rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #1f2937;
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

/* Form Actions */
.form-actions {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 2px solid #f1f5f9;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.btn-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
}

.keyboard-shortcuts {
    margin-left: auto;
    color: #6b7280;
}

/* Stats Container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-box {
    display: flex;
    align-items: center;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    border-radius: 16px;
    border-left: 4px solid #667eea;
    transition: transform 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-right: 1.5rem;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 900;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

@media (max-width: 768px) {
    .search-tips {
        grid-template-columns: 1fr;
    }
    
    .quick-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-label {
        margin-bottom: 0.5rem;
    }
    
    .product-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .product-details {
        text-align: center;
    }
    
    .product-actions {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    .payment-methods {
        gap: 0.75rem;
    }
    
    .payment-label {
        padding: 0.75rem;
    }
    
    .total-amount {
        font-size: 2rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .keyboard-shortcuts {
        margin-left: 0;
        text-align: center;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
}

/* Custom Scrollbar */
.products-container::-webkit-scrollbar {
    width: 6px;
}

.products-container::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.products-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.products-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Enhanced Product Selector JavaScript
class EnhancedProductSelector {
    constructor() {
        this.container = document.getElementById('productSelector');
        this.searchInput = document.getElementById('product-search');
        this.dropdownToggle = document.getElementById('dropdownToggle');
        this.clearBtn = document.getElementById('clearSearch');
        this.dropdownMenu = document.getElementById('dropdownMenu');
        this.productsList = document.getElementById('productsList');
        this.selectedProduct = document.getElementById('selectedProduct');
        this.changeBtn = document.getElementById('changeProduct');
        this.hiddenSelect = document.getElementById('product_id');
        this.resultsCount = document.getElementById('resultsCount');
        this.noResults = document.getElementById('noResults');
        
        this.isOpen = false;
        this.focusedIndex = -1;
        this.selectedProductData = null;
        this.allProducts = [];
        this.filteredProducts = [];
        this.activeFilter = '';
        
        this.init();
    }
    
    init() {
        this.collectProducts();
        this.bindEvents();
        this.setupKeyboardShortcuts();
    }
    
    collectProducts() {
        const productItems = this.productsList.querySelectorAll('.product-item');
        this.allProducts = Array.from(productItems).map(item => ({
            element: item,
            id: item.dataset.productId,
            name: item.dataset.name,
            price: parseFloat(item.dataset.price),
            stock: parseInt(item.dataset.stock),
            category: item.dataset.category,
            size: item.dataset.size,
            color: item.dataset.color,
            brand: item.dataset.brand,
            searchText: item.dataset.search
        }));
        this.filteredProducts = [...this.allProducts];
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('click', () => this.open());
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        this.searchInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        // Toggle and clear buttons
        this.dropdownToggle.addEventListener('click', () => this.toggle());
        this.clearBtn.addEventListener('click', () => this.clearSearch());
        
        // Change product button
        this.changeBtn.addEventListener('click', () => this.changeProduct());
        
        // Product selection
        this.productsList.addEventListener('click', (e) => {
            const productItem = e.target.closest('.product-item');
            if (productItem) {
                this.selectProduct(productItem);
            }
        });
        
        // Quick filters
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => this.applyFilter(btn.dataset.category));
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
        
        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
                this.searchInput.focus();
            }
        });
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                this.searchInput.focus();
                if (!this.isOpen) this.open();
            }
        });
    }
    
    open() {
        this.isOpen = true;
        this.dropdownMenu.classList.add('show');
        this.dropdownToggle.classList.add('open');
        this.searchInput.style.borderRadius = '12px 12px 0 0';
        this.focusedIndex = -1;
        this.updateFocusedProduct();
        
        // Show search tips if no search term
        if (!this.searchInput.value.trim()) {
            document.getElementById('searchTips').style.display = 'block';
        }
    }
    
    close() {
        this.isOpen = false;
        this.dropdownMenu.classList.remove('show');
        this.dropdownToggle.classList.remove('open');
        this.searchInput.style.borderRadius = '12px';
        this.focusedIndex = -1;
        this.updateFocusedProduct();
    }
    
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
    
    handleSearch(searchTerm) {
        document.getElementById('searchTips').style.display = searchTerm.trim() ? 'none' : 'block';
        
        if (!searchTerm.trim()) {
            this.showAllProducts();
            return;
        }
        
        this.filterProducts(searchTerm);
        
        // Show clear button when searching
        this.clearBtn.style.display = searchTerm ? 'flex' : 'none';
        
        if (!this.isOpen) this.open();
    }
    
    filterProducts(searchTerm) {
        const term = searchTerm.toLowerCase();
        
        this.filteredProducts = this.allProducts.filter(product => 
            product.searchText.includes(term) ||
            product.name.toLowerCase().includes(term) ||
            product.brand.toLowerCase().includes(term) ||
            product.category.toLowerCase().includes(term)
        );
        
        this.displayFilteredProducts();
        this.focusedIndex = -1;
        this.updateFocusedProduct();
    }
    
    applyFilter(category) {
        // Update filter button states
        document.querySelectorAll('.filter-btn').forEach(btn => {
            if (btn.dataset.category === category) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        this.activeFilter = category;
        
        if (!category) {
            this.showAllProducts();
        } else {
            this.filteredProducts = this.allProducts.filter(product => 
                product.category === category
            );
            this.displayFilteredProducts();
        }
        
        if (!this.isOpen) this.open();
    }
    
    showAllProducts() {
        this.filteredProducts = [...this.allProducts];
        this.displayFilteredProducts();
    }
    
    displayFilteredProducts() {
        // Hide all products and categories first
        this.allProducts.forEach(product => {
            product.element.style.display = 'none';
        });
        
        // Hide all category groups
        const categoryGroups = this.productsList.querySelectorAll('.category-group');
        categoryGroups.forEach(group => {
            group.style.display = 'none';
        });
        
        // Show filtered products and their categories
        const visibleCategories = new Set();
        this.filteredProducts.forEach(product => {
            product.element.style.display = 'flex';
            visibleCategories.add(product.category);
        });
        
        // Show relevant category groups
        categoryGroups.forEach(group => {
            if (visibleCategories.has(group.dataset.category)) {
                group.style.display = 'block';
            }
        });
        
        // Update results count
        this.resultsCount.textContent = `${this.filteredProducts.length} products found`;
        
        // Show/hide no results
        this.noResults.style.display = this.filteredProducts.length === 0 ? 'block' : 'none';
    }
    
    handleKeyDown(e) {
        if (!this.isOpen) return;
        
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
                if (this.focusedIndex >= 0 && this.filteredProducts[this.focusedIndex]) {
                    this.selectProduct(this.filteredProducts[this.focusedIndex].element);
                }
                break;
            case 'Tab':
                this.close();
                break;
        }
    }
    
    focusNext() {
        if (this.filteredProducts.length === 0) return;
        this.focusedIndex = (this.focusedIndex + 1) % this.filteredProducts.length;
        this.updateFocusedProduct();
    }
    
    focusPrevious() {
        if (this.filteredProducts.length === 0) return;
        this.focusedIndex = this.focusedIndex <= 0 ? 
            this.filteredProducts.length - 1 : this.focusedIndex - 1;
        this.updateFocusedProduct();
    }
    
    updateFocusedProduct() {
        // Remove focus from all products
        this.allProducts.forEach(product => {
            product.element.classList.remove('focused');
        });
        
        // Add focus to current product
        if (this.focusedIndex >= 0 && this.filteredProducts[this.focusedIndex]) {
            const focusedProduct = this.filteredProducts[this.focusedIndex];
            focusedProduct.element.classList.add('focused');
            focusedProduct.element.scrollIntoView({ block: 'nearest' });
        }
    }
    
    selectProduct(productElement) {
        const productData = this.allProducts.find(p => p.element === productElement);
        if (!productData) return;
        
        // Update selected product data
        this.selectedProductData = productData;
        
        // Update hidden select
        this.hiddenSelect.value = productData.id;
        
        // Update UI
        this.updateSelectedProductDisplay();
        
        // Hide dropdown and show selected product
        this.close();
        this.searchInput.style.display = 'none';
        this.selectedProduct.style.display = 'flex';
        
        // Clear search
        this.searchInput.value = '';
        this.clearBtn.style.display = 'none';
        
        // Trigger change event
        this.hiddenSelect.dispatchEvent(new Event('change'));
        
        // Focus next input
        document.getElementById('quantity').focus();
        
        // Show success animation
        this.showSelectionSuccess();
    }
    
    updateSelectedProductDisplay() {
        if (!this.selectedProductData) return;
        
        const selectedName = document.getElementById('selectedName');
        const selectedMeta = document.getElementById('selectedMeta');
        
        selectedName.textContent = this.selectedProductData.name;
        selectedMeta.innerHTML = `
            <span class="brand">${this.selectedProductData.brand}</span>
            <span class="separator">•</span>
            <span>Size: ${this.selectedProductData.size}</span>
            <span class="separator">•</span>
            <span class="color">
                <span class="color-dot" style="background-color: ${this.selectedProductData.color.toLowerCase()}"></span>
                ${this.selectedProductData.color}
            </span>
            <span class="separator">•</span>
            <span class="price">${this.formatPrice(this.selectedProductData.price)} TZS</span>
        `;
    }
    
    changeProduct() {
        this.selectedProduct.style.display = 'none';
        this.searchInput.style.display = 'block';
        this.searchInput.focus();
        this.selectedProductData = null;
        this.hiddenSelect.value = '';
        this.hiddenSelect.dispatchEvent(new Event('change'));
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.clearBtn.style.display = 'none';
        this.showAllProducts();
        this.searchInput.focus();
        
        // Reset active filter
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector('.filter-btn[data-category=""]').classList.add('active');
        this.activeFilter = '';
    }
    
    showSelectionSuccess() {
        // Create success indicator
        const successIndicator = document.createElement('div');
        successIndicator.innerHTML = '<i class="fas fa-check"></i> Product Selected';
        successIndicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        `;
        
        document.body.appendChild(successIndicator);
        
        // Remove after 3 seconds
        setTimeout(() => {
            successIndicator.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => successIndicator.remove(), 300);
        }, 3000);
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('en-US').format(price);
    }
    
    reset() {
        this.changeProduct();
        this.clearSearch();
        this.close();
    }
    
    getValue() {
        return this.hiddenSelect.value;
    }
    
    getSelectedData() {
        return this.selectedProductData;
    }
}

// Enhanced Form Controller
class SalesFormController {
    constructor() {
        this.form = document.getElementById('sales-form');
        this.productSelector = new EnhancedProductSelector();
        this.quantityInput = document.getElementById('quantity');
        this.quantityMinus = document.getElementById('quantityMinus');
        this.quantityPlus = document.getElementById('quantityPlus');
        this.unitPriceInput = document.getElementById('unit_price');
        this.useSuggestedBtn = document.getElementById('useSuggestedPrice');
        this.totalDisplay = document.getElementById('total-display');
        this.totalAmount = this.totalDisplay.querySelector('.total-amount');
        this.totalBreakdown = document.getElementById('totalBreakdown');
        this.stockWarning = document.getElementById('stock-warning');
        this.suggestedPrice = document.getElementById('suggested-price');
        this.suggestedPriceValue = document.getElementById('suggested-price-value');
        this.submitBtn = document.getElementById('submit-btn');
        this.resetBtn = document.getElementById('reset-btn');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupKeyboardShortcuts();
        this.updateSubmitButton();
    }
    
    bindEvents() {
        // Product selection change
        document.getElementById('product_id').addEventListener('change', () => {
            this.updateProductInfo();
        });
        
        // Quantity controls
        this.quantityMinus.addEventListener('click', () => this.adjustQuantity(-1));
        this.quantityPlus.addEventListener('click', () => this.adjustQuantity(1));
        this.quantityInput.addEventListener('input', () => this.handleQuantityChange());
        this.quantityInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.unitPriceInput.focus();
            }
        });
        
        // Price input
        this.unitPriceInput.addEventListener('input', () => this.handlePriceChange());
        this.unitPriceInput.addEventListener('keydown', (e) => {
            this.handlePriceKeyDown(e);
        });
        this.useSuggestedBtn.addEventListener('click', () => this.useSuggestedPrice());
        
        // Payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => this.updateSubmitButton());
        });
        
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Reset button
        this.resetBtn.addEventListener('click', () => this.resetForm());
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F3') {
                e.preventDefault();
                this.quantityInput.focus();
            }
            if (e.key === 'F4') {
                e.preventDefault();
                this.unitPriceInput.focus();
            }
            if (e.key === 'F5') {
                e.preventDefault();
                document.getElementById('lipa').focus();
            }
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.resetForm();
            }
        });
    }
    
    updateProductInfo() {
        const selectedData = this.productSelector.getSelectedData();
        
        if (selectedData) {
            // Show suggested price
            this.suggestedPriceValue.textContent = this.formatPrice(selectedData.price) + ' TZS';
            this.suggestedPrice.style.display = 'block';
            
            // Auto-fill price if empty
            if (!this.unitPriceInput.value.trim()) {
                this.unitPriceInput.value = this.formatPrice(selectedData.price);
            }
            
            // Update quantity constraints
            this.quantityInput.setAttribute('max', selectedData.stock);
            this.quantityPlus.disabled = false;
            
            // Validate current quantity
            const currentQuantity = parseInt(this.quantityInput.value) || 0;
            if (currentQuantity > selectedData.stock) {
                this.quantityInput.value = selectedData.stock;
                this.showNotification(`Quantity adjusted to maximum available: ${selectedData.stock}`, 'warning');
            }
            
            this.updateStockWarning();
        } else {
            this.suggestedPrice.style.display = 'none';
            this.stockWarning.style.display = 'none';
            this.quantityInput.removeAttribute('max');
            this.quantityPlus.disabled = true;
        }
        
        this.calculateTotal();
        this.updateSubmitButton();
    }
    
    adjustQuantity(delta) {
        const selectedData = this.productSelector.getSelectedData();
        if (!selectedData) return;
        
        const currentQuantity = parseInt(this.quantityInput.value) || 0;
        const newQuantity = Math.max(1, Math.min(selectedData.stock, currentQuantity + delta));
        
        this.quantityInput.value = newQuantity;
        this.handleQuantityChange();
    }
    
    handleQuantityChange() {
        const selectedData = this.productSelector.getSelectedData();
        const quantity = parseInt(this.quantityInput.value) || 0;
        
        // Update quantity buttons
        this.quantityMinus.disabled = quantity <= 1;
        
        if (selectedData) {
            this.quantityPlus.disabled = quantity >= selectedData.stock;
            
            // Validate against stock
            if (quantity > selectedData.stock) {
                this.quantityInput.value = selectedData.stock;
                this.showNotification(`Maximum available: ${selectedData.stock}`, 'warning');
            }
            
            this.updateStockWarning();
        }
        
        this.calculateTotal();
        this.updateSubmitButton();
    }
    
    handlePriceChange() {
        // Format price with commas
        this.formatPriceInput();
        this.calculateTotal();
        this.updateSubmitButton();
    }
    
    handlePriceKeyDown(e) {
        // Allow navigation keys
        const allowedKeys = [
            'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
            'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
        ];
        
        if (allowedKeys.includes(e.key)) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('lipa').focus();
            }
            return;
        }
        
        // Allow Ctrl combinations
        if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }
        
        // Allow numbers and decimal point
        if (!/[\d.]/.test(e.key)) {
            e.preventDefault();
        }
    }
    
    formatPriceInput() {
        const value = this.unitPriceInput.value.replace(/[^\d.]/g, '');
        const cursorPos = this.unitPriceInput.selectionStart;
        
        // Format with commas
        const formattedValue = this.formatPrice(parseFloat(value) || 0);
        
        this.unitPriceInput.value = formattedValue;
        
        // Restore cursor position approximately
        const newCursorPos = Math.min(cursorPos + (formattedValue.length - value.length), formattedValue.length);
        this.unitPriceInput.setSelectionRange(newCursorPos, newCursorPos);
    }
    
    useSuggestedPrice() {
        const selectedData = this.productSelector.getSelectedData();
        if (selectedData) {
            this.unitPriceInput.value = this.formatPrice(selectedData.price);
            this.calculateTotal();
            this.updateSubmitButton();
            
            // Visual feedback
            this.useSuggestedBtn.innerHTML = '<i class="fas fa-check"></i> Applied!';
            this.useSuggestedBtn.style.background = '#10b981';
            
            setTimeout(() => {
                this.useSuggestedBtn.innerHTML = 'Use This Price';
                this.useSuggestedBtn.style.background = '#f59e0b';
            }, 1500);
        }
    }
    
    updateStockWarning() {
        const selectedData = this.productSelector.getSelectedData();
        const quantity = parseInt(this.quantityInput.value) || 0;
        
        if (!selectedData) {
            this.stockWarning.style.display = 'none';
            return;
        }
        
        const stock = selectedData.stock;
        const warningText = this.stockWarning.querySelector('.warning-text');
        
        if (stock <= 0) {
            this.stockWarning.style.display = 'flex';
            warningText.textContent = 'Product is out of stock!';
        } else if (quantity > stock) {
            this.stockWarning.style.display = 'flex';
            warningText.textContent = `Only ${stock} units available`;
        } else if (stock <= 5) {
            this.stockWarning.style.display = 'flex';
            warningText.textContent = `Low stock: Only ${stock} units remaining`;
        } else {
            this.stockWarning.style.display = 'none';
        }
    }
    
    calculateTotal() {
        const unitPrice = this.parsePrice(this.unitPriceInput.value);
        const quantity = parseInt(this.quantityInput.value) || 0;
        const total = unitPrice * quantity;
        
        // Update main total display
        this.totalAmount.textContent = this.formatPrice(total);
        
        // Update breakdown
        if (unitPrice > 0 && quantity > 0) {
            document.getElementById('breakdownUnitPrice').textContent = this.formatPrice(unitPrice) + ' TZS';
            document.getElementById('breakdownQuantity').textContent = quantity;
            document.getElementById('breakdownTotal').textContent = this.formatPrice(total) + ' TZS';
            this.totalBreakdown.style.display = 'block';
        } else {
            this.totalBreakdown.style.display = 'none';
        }
        
        // Visual feedback
        if (total > 0) {
            this.totalDisplay.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.totalDisplay.style.transform = 'scale(1)';
            }, 200);
        }
    }
    
    updateSubmitButton() {
        const hasProduct = this.productSelector.getValue() !== '';
        const hasQuantity = this.quantityInput.value !== '' && parseInt(this.quantityInput.value) > 0;
        const hasUnitPrice = this.unitPriceInput.value !== '' && this.parsePrice(this.unitPriceInput.value) > 0;
        const hasPaymentMethod = document.querySelector('input[name="payment_method"]:checked') !== null;
        
        const isValid = hasProduct && hasQuantity && hasUnitPrice && hasPaymentMethod;
        
        this.submitBtn.disabled = !isValid;
        
        if (isValid) {
            this.submitBtn.classList.add('ready');
        } else {
            this.submitBtn.classList.remove('ready');
        }
    }
    
    validateForm() {
        const selectedData = this.productSelector.getSelectedData();
        const quantity = parseInt(this.quantityInput.value) || 0;
        const unitPrice = this.parsePrice(this.unitPriceInput.value);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedData) {
            this.showNotification('Please select a product', 'error');
            return false;
        }
        
        if (quantity <= 0) {
            this.showNotification('Please enter a valid quantity', 'error');
            this.quantityInput.focus();
            return false;
        }
        
        if (unitPrice <= 0) {
            this.showNotification('Please enter a valid unit price', 'error');
            this.unitPriceInput.focus();
            return false;
        }
        
        if (!paymentMethod) {
            this.showNotification('Please select a payment method', 'error');
            return false;
        }
        
        if (quantity > selectedData.stock) {
            this.showNotification(`Not enough stock. Available: ${selectedData.stock}`, 'error');
            this.quantityInput.focus();
            return false;
        }
        
        return true;
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        if (!this.confirmSale()) {
            return;
        }
        
        // Show loading state
        this.submitBtn.innerHTML = '<span class="loading"></span> Processing Sale...';
        this.submitBtn.disabled = true;
        
        // Clean price for submission
        const cleanPrice = this.parsePrice(this.unitPriceInput.value);
        this.unitPriceInput.value = cleanPrice.toString();
        
        // Submit form
        this.form.submit();
    }
    
    confirmSale() {
        const selectedData = this.productSelector.getSelectedData();
        const quantity = parseInt(this.quantityInput.value);
        const unitPrice = this.parsePrice(this.unitPriceInput.value);
        const total = unitPrice * quantity;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        return confirm(`🛒 Confirm Sale Details:

📦 Product: ${selectedData.name}
🏷️ Brand: ${selectedData.brand}
📏 Size: ${selectedData.size}
🎨 Color: ${selectedData.color}

📊 Quantity: ${quantity}
💰 Unit Price: ${this.formatPrice(unitPrice)} TZS
💳 Payment: ${paymentMethod}

🧾 Total Amount: ${this.formatPrice(total)} TZS

✅ Proceed with this sale?`);
    }
    
    resetForm() {
        this.productSelector.reset();
        this.quantityInput.value = '';
        this.unitPriceInput.value = '';
        this.totalAmount.textContent = '0';
        this.totalBreakdown.style.display = 'none';
        this.stockWarning.style.display = 'none';
        this.suggestedPrice.style.display = 'none';
        
        // Reset quantity buttons
        this.quantityMinus.disabled = true;
        this.quantityPlus.disabled = true;
        
        // Reset payment methods
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.checked = false;
        });
        
        this.updateSubmitButton();
        
        // Focus product search
        setTimeout(() => {
            document.getElementById('product-search').focus();
        }, 300);
        
        this.showNotification('Form reset successfully', 'success');
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('en-US').format(Math.round(price || 0));
    }
    
    parsePrice(priceString) {
        return parseFloat(priceString?.replace(/,/g, '') || '0') || 0;
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${this.getIconForType(type)}"></i>
            <span>${message}</span>
        `;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.getColorForType(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 400px;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
        
        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    getIconForType(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    getColorForType(type) {
        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
        };
        return colors[type] || colors.info;
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Create form controller
    const salesForm = new SalesFormController();
    
    // Add custom animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        .btn.ready {
            animation: readyPulse 2s infinite;
        }
        
        @keyframes readyPulse {
            0%, 100% { 
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            }
            50% { 
                box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
                transform: translateY(-2px);
            }
        }
    `;
    document.head.appendChild(style);
    
    console.log('🚀 Enhanced Make Sale system initialized successfully!');
});

// Global function for clearing search (called from no results button)
function clearSearch() {
    document.getElementById('product-search').value = '';
    document.getElementById('clearSearch').click();
}
</script>

<?php include '../includes/footer.php'; ?>
         {
    display: flex;
    align-items: center;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
    gap: 1rem;
}

.product-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(102, 126, 234, 0.02));
    transform: translateX(4px);
}

.product-item