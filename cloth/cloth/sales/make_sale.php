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
                             ", Unit Price: " . number_format($unit_price) . " TZS" .
                             ", Total: " . number_format($total_price) . " TZS" .
                             ", Payment: " . htmlspecialchars($payment_method);
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$products = $conn->query("
    SELECT p.product_id, p.name, p.price, p.stock_quantity, c.name as category_name, p.size, p.color, p.brand
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock_quantity > 0 
    ORDER BY c.name, p.name
");
?>

<?php include '../includes/header.php'; ?>

<div class="compact-container">
    <div class="compact-header">
        <h2><i class="fas fa-shopping-cart"></i> Make a Sale</h2>
    </div>
    
    <div class="compact-card">
        <?php if (!empty($errors)): ?>
            <div class="compact-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error!</strong>
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="compact-alert success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong>
                    <div><?php echo $success_message; ?></div>
                </div>
            </div>
        <?php endif; ?>
        
        <form id="sales-form" method="POST" action="">
            <div class="form-grid">
                <!-- Product Selection -->
                <div class="form-section">
                    <div class="section-header">
                        <h3><i class="fas fa-tshirt"></i> Product</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Product *</label>
                        <select id="product_id" name="product_id" class="compact-select" required>
                            <option value="">Choose Product</option>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['stock_quantity']; ?> in stock)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Sale Details -->
                <div class="form-section">
                    <div class="section-header">
                        <h3><i class="fas fa-calculator"></i> Details</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity *</label>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn minus" id="quantityMinus">-</button>
                            <input type="number" id="quantity" name="quantity" min="1" required placeholder="0">
                            <button type="button" class="quantity-btn plus" id="quantityPlus">+</button>
                        </div>
                        <div class="stock-info">Available: <span id="availableStock">0</span></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit Price (TZS) *</label>
                        <input type="text" id="unit_price" name="unit_price" required placeholder="0">
                        <div id="suggested-price" class="suggested-price">
                            Suggested: <span id="suggested-price-value"></span>
                            <button type="button" id="useSuggestedPrice">Use</button>
                        </div>
                    </div>
                </div>
                
                <!-- Payment -->
                <div class="form-section">
                    <div class="section-header">
                        <h3><i class="fas fa-money-bill-wave"></i> Payment</h3>
                    </div>
                    
                    <div class="form-group">
                        <label>Method *</label>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="CASH" required>
                                <div class="payment-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Cash</span>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="LIPA NUMBER" required>
                                <div class="payment-content">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>M-Pesa</span>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="CRDB BANK" required>
                                <div class="payment-content">
                                    <i class="fas fa-university"></i>
                                    <span>Bank</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Total Amount</label>
                        <div id="total-display" class="total-display">
                            <span id="total-amount">0</span> TZS
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submit-btn">
                    <i class="fas fa-shopping-cart"></i> Complete Sale
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Compact Layout Styles */
.compact-container {
    max-width: 100%;
    margin: 0 auto;
    padding: 15px;
    box-sizing: border-box;
    height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.compact-header {
    padding: 10px 0;
    margin-bottom: 10px;
}

.compact-header h2 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.compact-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 15px;
    flex: 1;
    overflow: auto;
}

.compact-alert {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.compact-alert.error {
    background: #fee2e2;
    color: #b91c1c;
}

.compact-alert.success {
    background: #dcfce7;
    color: #166534;
}

.compact-alert i {
    font-size: 1.2rem;
    margin-top: 2px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.form-section {
    background: #f9fafb;
    border-radius: 8px;
    padding: 15px;
}

.section-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.section-header h3 {
    margin: 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    font-size: 0.9rem;
}

.compact-select, 
.form-group input[type="text"],
.form-group input[type="number"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}

.quantity-control {
    display: flex;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    overflow: hidden;
}

.quantity-btn {
    width: 40px;
    background: #f3f4f6;
    border: none;
    font-size: 1rem;
    cursor: pointer;
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quantity-control input {
    flex: 1;
    border: none;
    border-left: 1px solid #d1d5db;
    border-right: 1px solid #d1d5db;
    text-align: center;
    font-weight: 600;
}

.stock-info {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 5px;
}

.suggested-price {
    background: #fef3c7;
    color: #92400e;
    padding: 8px;
    border-radius: 6px;
    margin-top: 5px;
    font-size: 0.9rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggested-price button {
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 0.8rem;
    cursor: pointer;
}

.payment-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.payment-option input {
    position: absolute;
    opacity: 0;
}

.payment-content {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-option input:checked + .payment-content {
    border-color: #3b82f6;
    background: #eff6ff;
}

.payment-content i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 5px;
    color: #3b82f6;
}

.total-display {
    background: #10b981;
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.form-actions {
    margin-top: 15px;
    text-align: center;
}

.submit-btn {
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 12px 20px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}

.submit-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .compact-container {
        padding: 20px;
    }
    
    .compact-card {
        padding: 20px;
    }
}

@media (max-height: 800px) {
    .compact-container {
        height: auto;
        min-height: calc(100vh - 100px);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Product selection
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('unit_price');
    const availableStock = document.getElementById('availableStock');
    const suggestedPrice = document.getElementById('suggested-price');
    const suggestedPriceValue = document.getElementById('suggested-price-value');
    const useSuggestedBtn = document.getElementById('useSuggestedPrice');
    const totalAmount = document.getElementById('total-amount');
    const submitBtn = document.getElementById('submit-btn');
    
    // Quantity controls
    document.getElementById('quantityMinus').addEventListener('click', function() {
        let value = parseInt(quantityInput.value) || 0;
        quantityInput.value = Math.max(1, value - 1);
        updateTotal();
    });
    
    document.getElementById('quantityPlus').addEventListener('click', function() {
        let value = parseInt(quantityInput.value) || 0;
        let max = parseInt(quantityInput.getAttribute('max')) || 999;
        quantityInput.value = Math.min(max, value + 1);
        updateTotal();
    });
    
    // Product change handler
    productSelect.addEventListener('change', function() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = selectedOption.dataset.price;
        const stock = selectedOption.dataset.stock;
        
        if (price && stock) {
            suggestedPriceValue.textContent = formatPrice(price) + ' TZS';
            suggestedPrice.style.display = 'flex';
            
            quantityInput.setAttribute('max', stock);
            availableStock.textContent = stock;
            
            if (!priceInput.value) {
                priceInput.value = formatPrice(price);
            }
        } else {
            suggestedPrice.style.display = 'none';
        }
        
        validateForm();
    });
    
    // Use suggested price
    useSuggestedBtn.addEventListener('click', function() {
        priceInput.value = suggestedPriceValue.textContent.replace(' TZS', '');
        updateTotal();
    });
    
    // Input handlers
    quantityInput.addEventListener('input', updateTotal);
    priceInput.addEventListener('input', function() {
        // Format price as user types
        let value = this.value.replace(/\D/g, '');
        this.value = formatPrice(value);
        updateTotal();
    });
    
    // Payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', validateForm);
    });
    
    // Form validation
    function validateForm() {
        const isValid = productSelect.value && 
                       quantityInput.value && parseInt(quantityInput.value) > 0 &&
                       priceInput.value && parseFloat(priceInput.value.replace(/,/g, '')) > 0 &&
                       document.querySelector('input[name="payment_method"]:checked');
        
        submitBtn.disabled = !isValid;
    }
    
    // Update total price
    function updateTotal() {
        const quantity = parseInt(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value.replace(/,/g, '')) || 0;
        const total = quantity * price;
        
        totalAmount.textContent = formatPrice(total);
        validateForm();
    }
    
    // Format price with commas
    function formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Initialize
    validateForm();
});
</script>

<?php include '../includes/footer.php'; ?>