<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$selected_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $reason = trim($_POST['reason']);
    
    // Validation
    $errors = [];
    if ($product_id <= 0) $errors[] = "Please select a product";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0";
    if (empty($reason)) $errors[] = "Please provide a reason for restocking";
    
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Get current stock
            $stmt = $conn->prepare("SELECT name, stock_quantity FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            // Update stock
            $new_stock = $product['stock_quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            // Record stock movement
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason) VALUES (?, 'in', ?, ?)");
            $stmt->bind_param("iis", $product_id, $quantity, $reason);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Product restocked successfully! " . $product['name'] . " - Added: " . $quantity . " units. New stock: " . $new_stock;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get products for dropdown
$products = $conn->query("SELECT product_id, name, stock_quantity FROM products ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-plus"></i> Restock Products</h2>

<div class="card">
    <div class="card-header">
        <h3>Add Stock to Products</h3>
        <a href="view_stock.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Stock
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
        
        <form method="POST" action="" id="restockForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="product_id"><i class="fas fa-tshirt"></i> Product *</label>
                        <select id="product_id" name="product_id" class="form-control" required>
                            <option value="">Select Product</option>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        data-stock="<?php echo $product['stock_quantity']; ?>"
                                        <?php echo ($product['product_id'] == $selected_product_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Current: <?php echo $product['stock_quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity to Add *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" 
                               min="1" required placeholder="Enter quantity to add">
                    </div>
                    
                    <div class="form-group">
                        <label for="reason"><i class="fas fa-comment"></i> Reason *</label>
                        <select id="reason" name="reason" class="form-control" required>
                            <option value="">Select Reason</option>
                            <option value="New Purchase">New Purchase</option>
                            <option value="Supplier Delivery">Supplier Delivery</option>
                            <option value="Return from Customer">Return from Customer</option>
                            <option value="Inventory Adjustment">Inventory Adjustment</option>
                            <option value="Transfer from Store">Transfer from Store</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customReasonDiv" style="display: none;">
                        <label for="customReason"><i class="fas fa-edit"></i> Custom Reason</label>
                        <input type="text" id="customReason" class="form-control" 
                               placeholder="Enter custom reason">
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Current Stock</label>
                        <div id="current-stock" style="padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px; font-size: 1.2rem; font-weight: bold;">
                            Select a product to see current stock
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> New Stock Level</label>
                        <div id="new-stock" style="padding: 0.75rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px; font-size: 1.2rem; font-weight: bold; color: #27ae60;">
                            0 units
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Stock Status</label>
                        <div id="stock-status" style="padding: 0.75rem; background: rgba(149, 165, 166, 0.1); border-radius: 8px; font-size: 1.1rem; font-weight: bold;">
                            -
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-success" id="submit-btn">
                    <i class="fas fa-plus"></i> Add Stock
                </button>
                <button type="reset" class="btn btn-secondary" id="reset-btn">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
                <a href="view_stock.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Low Stock Products -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-triangle"></i> Products Needing Restock</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lowStockProducts = $conn->query("
                        SELECT product_id, name, stock_quantity 
                        FROM products 
                        WHERE stock_quantity <= 10 
                        ORDER BY stock_quantity ASC
                    ");
                    
                    if ($lowStockProducts->num_rows > 0):
                        while ($product = $lowStockProducts->fetch_assoc()):
                            $status = $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Low Stock';
                            $class = $product['stock_quantity'] == 0 ? 'text-danger' : 'text-warning';
                            $icon = $product['stock_quantity'] == 0 ? 'fas fa-times-circle' : 'fas fa-exclamation-triangle';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="<?php echo $class; ?>">
                            <strong><?php echo $product['stock_quantity']; ?></strong>
                        </td>
                        <td class="<?php echo $class; ?>">
                            <i class="<?php echo $icon; ?>"></i> <?php echo $status; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm quick-restock-btn" 
                                    data-product-id="<?php echo $product['product_id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                <i class="fas fa-plus"></i> Quick Restock
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #2ecc71;">
                            <i class="fas fa-check-circle"></i> All products have sufficient stock
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const reasonSelect = document.getElementById('reason');
    const customReasonDiv = document.getElementById('customReasonDiv');
    const customReasonInput = document.getElementById('customReason');
    const currentStockDiv = document.getElementById('current-stock');
    const newStockDiv = document.getElementById('new-stock');
    const stockStatusDiv = document.getElementById('stock-status');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('restockForm');
    
    // Update stock display when product changes
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        
        currentStockDiv.textContent = currentStock + ' units';
        updateNewStock();
    });
    
    // Update new stock when quantity changes
    quantityInput.addEventListener('input', function() {
        updateNewStock();
    });
    
    // Show/hide custom reason input
    reasonSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            customReasonDiv.style.display = 'block';
            customReasonInput.required = true;
        } else {
            customReasonDiv.style.display = 'none';
            customReasonInput.required = false;
            customReasonInput.value = '';
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        const productName = productSelect.options[productSelect.selectedIndex].textContent.split(' (')[0];
        const quantity = parseInt(quantityInput.value) || 0;
        const reason = reasonSelect.value === 'Other' ? customReasonInput.value : reasonSelect.value;
        
        if (!productSelect.value) {
            e.preventDefault();
            showNotification('Please select a product', 'danger');
            return;
        }
        
        if (quantity <= 0) {
            e.preventDefault();
            showNotification('Please enter a valid quantity', 'danger');
            return;
        }
        
        if (!reason) {
            e.preventDefault();
            showNotification('Please provide a reason for restocking', 'danger');
            return;
        }
        
        // Confirm restock
        if (!confirm(`Confirm restock:\nProduct: ${productName}\nQuantity: ${quantity}\nReason: ${reason}`)) {
            e.preventDefault();
            return;
        }
        
        // Update reason field if custom
        if (reasonSelect.value === 'Other') {
            reasonSelect.innerHTML += `<option value="${customReasonInput.value}" selected>${customReasonInput.value}</option>`;
        }
        
        // Show loading state
        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
        setLoadingState(submitBtn, true);
    });
    
    // Quick restock buttons
    document.querySelectorAll('.quick-restock-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            // Set product in form
            productSelect.value = productId;
            productSelect.dispatchEvent(new Event('change'));
            
            // Suggest quantity based on stock level
            const currentStock = parseInt(productSelect.options[productSelect.selectedIndex].getAttribute('data-stock')) || 0;
            let suggestedQuantity = 20; // Default
            
            if (currentStock === 0) {
                suggestedQuantity = 50; // More for out of stock
            } else if (currentStock <= 5) {
                suggestedQuantity = 30; // Medium for low stock
            }
            
            quantityInput.value = suggestedQuantity;
            reasonSelect.value = 'New Purchase';
            
            updateNewStock();
            
            // Scroll to form
            document.getElementById('restockForm').scrollIntoView({ behavior: 'smooth' });
            
            showNotification(`Pre-filled restock form for ${productName}`, 'success');
        });
    });
    
    function updateNewStock() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const addQuantity = parseInt(quantityInput.value) || 0;
        const newStock = currentStock + addQuantity;
        
        newStockDiv.textContent = newStock + ' units';
        
        // Update status
        let status = '';
        let statusClass = '';
        
        if (newStock > 20) {
            status = '✅ Well Stocked';
            statusClass = 'color: #2ecc71;';
        } else if (newStock > 10) {
            status = '⚠️ Adequate Stock';
            statusClass = 'color: #f39c12;';
        } else if (newStock > 0) {
            status = '🔶 Still Low Stock';
            statusClass = 'color: #e67e22;';
        } else {
            status = '❌ Out of Stock';
            statusClass = 'color: #e74c3c;';
        }
        
        stockStatusDiv.innerHTML = `<span style="${statusClass}">${status}</span>`;
    }
    
    // Initialize if product is pre-selected
    if (productSelect.value) {
        productSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.quick-restock-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

#current-stock, #new-stock, #stock-status {
    transition: all 0.3s ease;
}

#customReasonDiv {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php include '../includes/footer.php'; ?>