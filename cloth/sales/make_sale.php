<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$errors = [];
$success_message = '';

// Process sale form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $unit_price = floatval(str_replace(',', '', $_POST['unit_price']));
    $payment_method = trim($_POST['payment_method']);
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if ($product_id <= 0) $errors[] = "Please select a product.";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0.";
    if ($unit_price <= 0) $errors[] = "Unit price must be greater than 0.";
    if (empty($payment_method)) $errors[] = "Please select a payment method.";
    
    // Check product availability
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT name, price, stock_quantity FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            $errors[] = "Selected product not found.";
        } elseif ($product['stock_quantity'] < $quantity) {
            $errors[] = "Insufficient stock. Available: " . $product['stock_quantity'];
        }
    }
    
    // Process sale if no errors
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            $total_price = $quantity * $unit_price;
            $new_stock = $product['stock_quantity'] - $quantity;
            
            // Insert sale record
            $stmt = $conn->prepare("INSERT INTO sales (product_id, user_id, quantity, unit_price, total_price, payment_method, sale_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiidds", $product_id, $user_id, $quantity, $unit_price, $total_price, $payment_method);
            $stmt->execute();
            $stmt->close();
            
            // Update product stock
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            $success_message = "Sale completed! " . htmlspecialchars($product['name']) . 
                             " (Qty: " . number_format($quantity) . 
                             ", Total: " . number_format($total_price) . " TZS)";
            
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

<div class="sale-container">
    <div class="sale-header">
        <h2><i class="fas fa-shopping-cart"></i> Make Sale</h2>
        <a href="sales_history.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-history"></i> History
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo implode(', ', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div class="sale-content">
        <form method="POST" class="sale-form">
            <!-- Compact Search Product Section -->
            <div class="product-search-section">
                <label class="form-label">
                    <i class="fas fa-search"></i> Select Product
                </label>
                
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" 
                               id="productSearch" 
                               class="search-input" 
                               placeholder="Search products by name, brand, category..."
                               autocomplete="off">
                        <div class="search-actions">
                            <button type="button" id="clearSearch" class="search-btn">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" id="toggleDropdown" class="search-btn">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>

                    <div class="search-dropdown" id="searchDropdown">
                        <div class="search-filters">
                            <button type="button" class="filter-btn active" data-filter="">All</button>
                            <button type="button" class="filter-btn" data-filter="Kids Jerseys">Kids</button>
                            <button type="button" class="filter-btn" data-filter="Vintage Jerseys">Vintage</button>
                            <button type="button" class="filter-btn" data-filter="Fan Jerseys">Fan Jersey</button>
                            <button type="button" class="filter-btn" data-filter="Player Jerseys">Player Jersey</button>
                        </div>

                        <div class="products-list" id="productsList">
                            <?php 
                            $currentCategory = '';
                            while ($product = $products->fetch_assoc()): 
                                if ($currentCategory !== $product['category_name']):
                                    if ($currentCategory !== '') echo '</div>';
                                    $currentCategory = $product['category_name'];
                                    echo '<div class="category-section">';
                                    echo '<div class="category-title">' . htmlspecialchars($currentCategory ?: 'Other') . '</div>';
                                endif;
                                
                                $stockLevel = $product['stock_quantity'] > 20 ? 'high' : 
                                            ($product['stock_quantity'] > 5 ? 'medium' : 'low');
                            ?>
                                <div class="product-item" 
                                     data-id="<?php echo $product['product_id']; ?>"
                                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                     data-price="<?php echo $product['price']; ?>"
                                     data-stock="<?php echo $product['stock_quantity']; ?>"
                                     data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
                                     data-search="<?php echo strtolower(htmlspecialchars($product['name'] . ' ' . $product['brand'] . ' ' . $product['color'] . ' ' . $product['size'] . ' ' . $product['category_name'])); ?>">
                                    
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="product-details">
                                            <?php if ($product['brand']): ?>
                                                <span class="detail-item"><?php echo htmlspecialchars($product['brand']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($product['size']): ?>
                                                <span class="detail-item"><?php echo htmlspecialchars($product['size']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($product['color']): ?>
                                                <span class="detail-item"><?php echo htmlspecialchars($product['color']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="product-meta">
                                        <div class="product-price"><?php echo number_format($product['price']); ?> TZS</div>
                                        <div class="stock-indicator stock-<?php echo $stockLevel; ?>">
                                            <span class="stock-dot"></span>
                                            <?php echo $product['stock_quantity']; ?> in stock
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="no-results" id="noResults" style="display: none;">
                            <i class="fas fa-search"></i>
                            <div>No products found</div>
                            <button type="button" class="btn-link" onclick="clearSearch()">Clear search</button>
                        </div>
                    </div>
                </div>

                <!-- Selected Product Display -->
                <div class="selected-product" id="selectedProduct" style="display: none;">
                    <div class="selected-info">
                        <div class="selected-name" id="selectedName"></div>
                        <div class="selected-details" id="selectedDetails"></div>
                    </div>
                    <button type="button" class="change-btn" onclick="changeProduct()">Change</button>
                </div>

                <input type="hidden" name="product_id" id="productId" required>
            </div>

            <!-- Quantity and Price Section -->
            <div class="transaction-section">
                <div class="input-row">
                    <div class="input-group">
                        <label class="form-label">Quantity</label>
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn" onclick="adjustQuantity(-1)">-</button>
                            <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                            <button type="button" class="qty-btn" onclick="adjustQuantity(1)">+</button>
                        </div>
                        <div class="input-note">Max: <span id="maxStock">0</span></div>
                    </div>

                    <div class="input-group">
                        <label class="form-label">Unit Price (TZS)</label>
                        <div class="price-controls">
                            <input type="text" name="unit_price" id="unitPrice" required>
                            <button type="button" class="suggest-btn" id="suggestPrice" onclick="useSuggestedPrice()">
                                Use <span id="suggestedAmount">0</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="payment-section">
                    <label class="form-label">Payment Method</label>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash" required>
                            <span class="payment-label"><i class="fas fa-money-bill"></i> Cash</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="mobile_money" required>
                            <span class="payment-label"><i class="fas fa-mobile-alt"></i> Mobile Money</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card" required>
                            <span class="payment-label"><i class="fas fa-credit-card"></i> Card</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Total and Submit -->
            <div class="total-section">
                <div class="total-display">
                    <span class="total-label">Total Amount:</span>
                    <span class="total-amount" id="totalAmount">0 TZS</span>
                </div>
                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <i class="fas fa-shopping-cart"></i> Complete Sale
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Compact Sale Page Styles */
.sale-container {
    max-width: 100%;
    height: calc(100vh - 80px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.75rem;
    box-sizing: border-box;
}

.sale-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.75rem;
    flex-shrink: 0;
}

.sale-header h2 {
    margin: 0;
    font-size: 1.4rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sale-content {
    flex: 1;
    overflow: auto;
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sale-form {
    display: flex;
    flex-direction: column;
    height: 100%;
    gap: 1rem;
}

/* Compact Alert Styles */
.alert {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.alert-danger {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

/* Product Search Section */
.product-search-section {
    flex-shrink: 0;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
    font-size: 0.9rem;
}

.search-container {
    position: relative;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 0.75rem 4rem 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-actions {
    position: absolute;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
}

.search-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: #f3f4f6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
}

.search-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

.search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 250px;
    overflow: hidden;
    z-index: 1000;
    display: none;
}

.search-dropdown.show {
    display: block;
}

.search-filters {
    display: flex;
    padding: 0.5rem;
    border-bottom: 1px solid #f3f4f6;
    gap: 0.5rem;
}

.filter-btn {
    padding: 0.25rem 0.75rem;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn.active,
.filter-btn:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.products-list {
    max-height: 180px;
    overflow-y: auto;
}

.category-section {
    border-bottom: 1px solid #f3f4f6;
}

.category-title {
    padding: 0.5rem;
    background: #f9fafb;
    font-weight: 600;
    font-size: 0.8rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid #f9fafb;
}

.product-item:hover {
    background: #f3f4f6;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.product-details {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.detail-item {
    font-size: 0.75rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
}

.product-meta {
    text-align: right;
}

.product-price {
    font-weight: 600;
    color: #059669;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.stock-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.stock-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.stock-high .stock-dot { background: #10b981; }
.stock-medium .stock-dot { background: #f59e0b; }
.stock-low .stock-dot { background: #ef4444; }

.no-results {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
}

.selected-product {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f0f9ff;
    border: 2px solid #0ea5e9;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.selected-name {
    font-weight: 600;
    color: #0c4a6e;
}

.selected-details {
    font-size: 0.8rem;
    color: #075985;
}

.change-btn {
    background: #0ea5e9;
    color: white;
    border: none;
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
}

/* Transaction Section */
.transaction-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.input-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.input-group {
    display: flex;
    flex-direction: column;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}

.qty-btn {
    width: 32px;
    height: 40px;
    border: none;
    background: #f3f4f6;
    cursor: pointer;
    font-weight: 600;
    color: #374151;
}

.qty-btn:hover {
    background: #e5e7eb;
}

#quantity {
    flex: 1;
    border: none;
    text-align: center;
    padding: 0.5rem;
    font-weight: 600;
}

.price-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

#unitPrice {
    flex: 1;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
}

.suggest-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    white-space: nowrap;
}

.input-note {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* Payment Section */
.payment-section {
    margin-top: 0.5rem;
}

.payment-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.payment-option {
    display: flex;
    cursor: pointer;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-label {
    flex: 1;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    text-align: center;
    transition: all 0.2s;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.payment-option input[type="radio"]:checked + .payment-label {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Total Section */
.total-section {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-top: 2px solid #f3f4f6;
    margin-top: auto;
}

.total-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.total-label {
    font-size: 0.9rem;
    color: #6b7280;
}

.total-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #059669;
}

.submit-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.submit-btn:enabled:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.submit-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sale-container {
        padding: 0.5rem;
        height: calc(100vh - 60px);
    }
    
    .input-row {
        grid-template-columns: 1fr;
    }
    
    .payment-options {
        grid-template-columns: 1fr;
    }
    
    .total-section {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .submit-btn {
        width: 100%;
        justify-content: center;
    }
    
    .search-dropdown {
        max-height: 200px;
    }
    
    .products-list {
        max-height: 140px;
    }
}

@media (max-width: 480px) {
    .sale-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .product-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .product-meta {
        text-align: left;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}

/* Scrollbar Styling */
.products-list::-webkit-scrollbar {
    width: 4px;
}

.products-list::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.products-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}
</style>

<script>
// Compact Sale Form JavaScript
class CompactSaleForm {
    constructor() {
        this.searchInput = document.getElementById('productSearch');
        this.dropdown = document.getElementById('searchDropdown');
        this.productsList = document.getElementById('productsList');
        this.selectedProduct = document.getElementById('selectedProduct');
        this.productIdInput = document.getElementById('productId');
        this.quantityInput = document.getElementById('quantity');
        this.unitPriceInput = document.getElementById('unitPrice');
        this.totalAmount = document.getElementById('totalAmount');
        this.submitBtn = document.getElementById('submitBtn');
        this.maxStock = document.getElementById('maxStock');
        this.suggestPrice = document.getElementById('suggestPrice');
        this.suggestedAmount = document.getElementById('suggestedAmount');
        
        this.selectedProductData = null;
        this.isDropdownOpen = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.collectProducts();
    }
    
    bindEvents() {
        // Search functionality
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        this.searchInput.addEventListener('focus', () => this.openDropdown());
        
        // Dropdown toggle
        document.getElementById('toggleDropdown').addEventListener('click', () => this.toggleDropdown());
        document.getElementById('clearSearch').addEventListener('click', () => this.clearSearch());
        
        // Product selection
        this.productsList.addEventListener('click', (e) => {
            const productItem = e.target.closest('.product-item');
            if (productItem) this.selectProduct(productItem);
        });
        
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => this.applyFilter(btn.dataset.filter, btn));
        });
        
        // Form inputs
        this.quantityInput.addEventListener('input', () => this.updateTotal());
        this.unitPriceInput.addEventListener('input', () => this.updateTotal());
        
        // Payment methods
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => this.validateForm());
        });
        
        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.closeDropdown();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeDropdown();
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                this.searchInput.focus();
                this.openDropdown();
            }
        });
    }
    
    collectProducts() {
        this.allProducts = Array.from(document.querySelectorAll('.product-item')).map(item => ({
            element: item,
            id: item.dataset.id,
            name: item.dataset.name,
            price: parseFloat(item.dataset.price),
            stock: parseInt(item.dataset.stock),
            category: item.dataset.category,
            searchText: item.dataset.search
        }));
    }
    
    handleSearch(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const products = document.querySelectorAll('.product-item');
        const categories = document.querySelectorAll('.category-section');
        let visibleCount = 0;
        
        // Filter products
        products.forEach(product => {
            const searchText = product.dataset.search;
            const isVisible = !term || searchText.includes(term);
            product.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Show/hide categories based on visible products
        categories.forEach(category => {
            const visibleProducts = category.querySelectorAll('.product-item[style*="flex"]');
            category.style.display = visibleProducts.length > 0 ? 'block' : 'none';
        });
        
        // Show/hide no results
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        
        if (!this.isDropdownOpen) this.openDropdown();
    }
    
    applyFilter(filter, button) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        const products = document.querySelectorAll('.product-item');
        let visibleCount = 0;
        
        products.forEach(product => {
            let isVisible = true;
            
            // Apply category filter
            if (filter && product.dataset.category !== filter) {
                isVisible = false;
            }
            
            // Apply search filter if exists
            const searchTerm = this.searchInput.value.toLowerCase().trim();
            if (searchTerm && !product.dataset.search.includes(searchTerm)) {
                isVisible = false;
            }
            
            product.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Update categories visibility
        document.querySelectorAll('.category-section').forEach(category => {
            const visibleProducts = category.querySelectorAll('.product-item[style*="flex"]');
            category.style.display = visibleProducts.length > 0 ? 'block' : 'none';
        });
        
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    }
    
    selectProduct(productItem) {
        this.selectedProductData = {
            id: productItem.dataset.id,
            name: productItem.dataset.name,
            price: parseFloat(productItem.dataset.price),
            stock: parseInt(productItem.dataset.stock)
        };
        
        // Update UI
        document.getElementById('selectedName').textContent = this.selectedProductData.name;
        document.getElementById('selectedDetails').textContent = `Stock: ${this.selectedProductData.stock} | Price: ${this.formatPrice(this.selectedProductData.price)} TZS`;
        
        this.productIdInput.value = this.selectedProductData.id;
        this.maxStock.textContent = this.selectedProductData.stock;
        this.suggestedAmount.textContent = this.formatPrice(this.selectedProductData.price);
        
        // Set suggested price
        this.unitPriceInput.value = this.formatPrice(this.selectedProductData.price);
        
        // Update quantity max
        this.quantityInput.max = this.selectedProductData.stock;
        
        // Show selected product, hide search
        this.selectedProduct.style.display = 'flex';
        this.searchInput.parentElement.style.display = 'none';
        
        this.closeDropdown();
        this.updateTotal();
        this.validateForm();
    }
    
    changeProduct() {
        this.selectedProduct.style.display = 'none';
        this.searchInput.parentElement.style.display = 'block';
        this.searchInput.focus();
        this.clearSearch();
        this.selectedProductData = null;
        this.productIdInput.value = '';
        this.validateForm();
    }
    
    clearSearch() {
        this.searchInput.value = '';
        document.querySelectorAll('.product-item').forEach(item => item.style.display = 'flex');
        document.querySelectorAll('.category-section').forEach(cat => cat.style.display = 'block');
        document.getElementById('noResults').style.display = 'none';
        
        // Reset filter
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.filter-btn[data-filter=""]').classList.add('active');
    }
    
    openDropdown() {
        this.isDropdownOpen = true;
        this.dropdown.classList.add('show');
        this.searchInput.style.borderRadius = '8px 8px 0 0';
    }
    
    closeDropdown() {
        this.isDropdownOpen = false;
        this.dropdown.classList.remove('show');
        this.searchInput.style.borderRadius = '8px';
    }
    
    toggleDropdown() {
        this.isDropdownOpen ? this.closeDropdown() : this.openDropdown();
    }
    
    updateTotal() {
        const quantity = parseInt(this.quantityInput.value) || 0;
        const unitPrice = this.parsePrice(this.unitPriceInput.value) || 0;
        const total = quantity * unitPrice;
        
        this.totalAmount.textContent = this.formatPrice(total) + ' TZS';
        this.validateForm();
    }
    
    validateForm() {
        const isValid = this.selectedProductData &&
                       parseInt(this.quantityInput.value) > 0 &&
                       this.parsePrice(this.unitPriceInput.value) > 0 &&
                       document.querySelector('input[name="payment_method"]:checked');
        
        this.submitBtn.disabled = !isValid;
    }
    
    formatPrice(price) {
        return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    parsePrice(priceStr) {
        return parseFloat(priceStr.replace(/,/g, '')) || 0;
    }
}

// Global functions for onclick handlers
function adjustQuantity(delta) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value) || 1;
    const maxValue = parseInt(input.max) || 999;
    const newValue = Math.max(1, Math.min(maxValue, currentValue + delta));
    input.value = newValue;
    window.saleForm.updateTotal();
}

function useSuggestedPrice() {
    const unitPriceInput = document.getElementById('unitPrice');
    const suggestedAmount = document.getElementById('suggestedAmount').textContent;
    unitPriceInput.value = suggestedAmount;
    window.saleForm.updateTotal();
}

function changeProduct() {
    window.saleForm.changeProduct();
}

function clearSearch() {
    window.saleForm.clearSearch();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.saleForm = new CompactSaleForm();
    
    // Format price input as user types
    const unitPriceInput = document.getElementById('unitPrice');
    unitPriceInput.addEventListener('input', function() {
        const cursorPosition = this.selectionStart;
        const oldLength = this.value.length;
        
        // Remove non-digits and format
        let value = this.value.replace(/\D/g, '');
        let formatted = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        this.value = formatted;
        
        // Restore cursor position
        const newLength = formatted.length;
        const newPosition = cursorPosition + (newLength - oldLength);
        this.setSelectionRange(newPosition, newPosition);
    });
    
    // Auto-focus search on page load
    setTimeout(() => {
        document.getElementById('productSearch').focus();
    }, 100);
});
</script>

<?php include '../includes/footer.php'; ?>