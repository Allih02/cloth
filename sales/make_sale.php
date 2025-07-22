<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validation
    $errors = [];
    if ($product_id <= 0) $errors[] = "Please select a product";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0";
    
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
            $total_price = $product['price'] * $quantity;
            
            // Record sale
            $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $product_id, $quantity, $total_price);
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
            
            $success_message = "Sale completed successfully! Product: " . $product['name'] . ", Quantity: " . number_format($quantity) . ", Total: " . number_format($total_price) . " TZS";
            
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
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form id="sales-form" method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="product"><i class="fas fa-tshirt"></i> Select Jersey, Cap or Shorts *</label>
                        <select id="product" name="product_id" class="form-control" required>
                            <option value="">Choose Product</option>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> - 
                                    <?php echo number_format($product['price']); ?> TZS 
                                    (Stock: <?php echo number_format($product['stock_quantity']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" 
                               min="1" required placeholder="Enter quantity">
                        <small id="stock-warning" style="color: #f39c12; display: none;">
                            <i class="fas fa-exclamation-triangle"></i> Limited stock available
                        </small>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Unit Price</label>
                        <div id="price-display" style="padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px; font-size: 1.2rem; font-weight: bold;">
                            0 TZS
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> Total Price</label>
                        <div id="total-display" style="padding: 0.75rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px; font-size: 1.5rem; font-weight: bold; color: #27ae60;">
                            0 TZS
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-success" id="submit-btn">
                    <i class="fas fa-shopping-cart"></i> Complete Sale
                </button>
                <button type="reset" class="btn btn-secondary" id="reset-btn">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Sale Statistics -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Today's Sales Statistics</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="text-align: center; padding: 1rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #3498db;">Total Sales</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()");
                    echo number_format($stmt->fetch_row()[0]);
                    ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #2ecc71;">Revenue</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php
                    $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE()");
                    echo number_format($stmt->fetch_row()[0] ?? 0);
                    ?> TZS
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(155, 89, 182, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #9b59b6;">Items Sold</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php
                    $stmt = $conn->query("SELECT SUM(quantity) FROM sales WHERE DATE(sale_date) = CURDATE()");
                    echo number_format($stmt->fetch_row()[0] ?? 0);
                    ?>
                </p>
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
                        <td><?php echo number_format($row['revenue']); ?> TZS</td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">No sales recorded today</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesForm = document.getElementById('sales-form');
    const productSelect = salesForm.querySelector('#product');
    const quantityInput = salesForm.querySelector('#quantity');
    const priceDisplay = salesForm.querySelector('#price-display');
    const totalDisplay = salesForm.querySelector('#total-display');
    const stockWarning = document.getElementById('stock-warning');
    const submitBtn = document.getElementById('submit-btn');
    const resetBtn = document.getElementById('reset-btn');
    
    // Function to format numbers with commas for TZS
    function formatTZS(amount) {
        return new Intl.NumberFormat('en-US').format(amount) + ' TZS';
    }
    
    // Update price when product changes
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const stock = selectedOption.getAttribute('data-stock') || 0;
        
        priceDisplay.textContent = formatTZS(parseFloat(price));
        
        if (quantityInput.value) {
            quantityInput.setAttribute('max', stock);
            if (parseInt(quantityInput.value) > parseInt(stock)) {
                quantityInput.value = stock;
            }
        }
        
        updateStockWarning(stock);
        calculateTotal();
        updateSubmitButton();
    });
    
    // Update total when quantity changes
    quantityInput.addEventListener('input', function() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0;
        
        if (parseInt(this.value) > stock) {
            this.value = stock;
            showNotification(`Maximum available quantity is ${new Intl.NumberFormat('en-US').format(stock)}`);
        }
        
        updateStockWarning(stock);
        calculateTotal();
        updateSubmitButton();
    });
    
    // Reset form
    resetBtn.addEventListener('click', function() {
        priceDisplay.textContent = '0 TZS';
        totalDisplay.textContent = '0 TZS';
        stockWarning.style.display = 'none';
        updateSubmitButton();
    });
    
    // Form submission
    salesForm.addEventListener('submit', function(e) {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0;
        const quantity = parseInt(quantityInput.value) || 0;
        
        if (!productSelect.value) {
            e.preventDefault();
            alert('Please select a product');
            return;
        }
        
        if (quantity <= 0) {
            e.preventDefault();
            alert('Please enter a valid quantity');
            return;
        }
        
        if (quantity > stock) {
            e.preventDefault();
            alert(`Not enough stock available. Maximum: ${new Intl.NumberFormat('en-US').format(stock)}`);
            return;
        }
        
        // Confirm sale
        const productName = selectedOption.textContent.split(' - ')[0];
        const price = parseFloat(selectedOption.getAttribute('data-price'));
        const total = price * quantity;
        
        if (!confirm(`Confirm sale:\nProduct: ${productName}\nQuantity: ${new Intl.NumberFormat('en-US').format(quantity)}\nTotal: ${formatTZS(total)}`)) {
            e.preventDefault();
            return;
        }
    });
    
    function calculateTotal() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) : 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = price * quantity;
        totalDisplay.textContent = formatTZS(total);
    }
    
    function updateStockWarning(stock) {
        if (stock > 0 && stock <= 10) {
            stockWarning.style.display = 'block';
            stockWarning.textContent = `⚠️ Only ${new Intl.NumberFormat('en-US').format(stock)} units remaining`;
        } else {
            stockWarning.style.display = 'none';
        }
    }
    
    function updateSubmitButton() {
        const hasProduct = productSelect.value !== '';
        const hasQuantity = quantityInput.value !== '' && parseInt(quantityInput.value) > 0;
        
        if (hasProduct && hasQuantity) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
        }
    }
    
    function showNotification(message) {
        alert(message);
    }
    
    // Initialize button state
    updateSubmitButton();
    
    // Auto-complete functionality
    productSelect.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            quantityInput.focus();
        }
    });
    
    quantityInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !submitBtn.disabled) {
            e.preventDefault();
            salesForm.submit();
        }
    });
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
}

#price-display, #total-display {
    transition: all 0.3s ease;
}

#total-display {
    border: 2px solid #2ecc71;
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

#submit-btn:disabled {
    cursor: not-allowed;
}

#stock-warning {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>

<?php include '../includes/footer.php'; ?>