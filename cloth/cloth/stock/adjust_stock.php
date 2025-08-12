<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $adjustment_type = $_POST['adjustment_type'];
    $quantity = intval($_POST['quantity']);
    $reason = trim($_POST['reason']);
    
    if ($product_id <= 0) {
        $error = "Invalid product ID";
    } elseif ($quantity <= 0) {
        $error = "Quantity must be greater than 0";
    } elseif (empty($reason)) {
        $error = "Please provide a reason for the adjustment";
    } else {
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
            
            // Calculate new stock
            $current_stock = $product['stock_quantity'];
            if ($adjustment_type === 'add') {
                $new_stock = $current_stock + $quantity;
            } else {
                $new_stock = max(0, $current_stock - $quantity);
                if ($current_stock < $quantity) {
                    $quantity = $current_stock; // Adjust to not go below 0
                }
            }
            
            // Update stock
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            // Record movement
            $movement_type = ($adjustment_type === 'add') ? 'in' : 'out';
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $product_id, $movement_type, $quantity, $reason);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $success = "Stock adjusted successfully! " . htmlspecialchars($product['name']) . " stock changed from {$current_stock} to {$new_stock}";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get product details if ID provided
$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get all products for dropdown
$products = $conn->query("SELECT product_id, name, stock_quantity FROM products ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-exchange-alt"></i> Stock Adjustment</h2>

<div class="card">
    <div class="card-header">
        <h3>Adjust Product Stock</h3>
        <div>
            <a href="manage_stock.php" class="btn btn-secondary">
                <i class="fas fa-cogs"></i> Manage All Stock
            </a>
            <a href="view_stock.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Stock View
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="adjustmentForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="product_id"><i class="fas fa-tshirt"></i> Select Product *</label>
                        <select id="product_id" name="product_id" class="form-control" required>
                            <option value="">Choose Product</option>
                            <?php while ($prod = $products->fetch_assoc()): ?>
                                <option value="<?php echo $prod['product_id']; ?>" 
                                        data-stock="<?php echo $prod['stock_quantity']; ?>"
                                        <?php echo ($product && $product['product_id'] == $prod['product_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['name']); ?> 
                                    (Current: <?php echo $prod['stock_quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="adjustment_type"><i class="fas fa-arrows-alt-v"></i> Adjustment Type *</label>
                        <select id="adjustment_type" name="adjustment_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="add">Add Stock (+)</option>
                            <option value="remove">Remove Stock (-)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" 
                               min="1" required placeholder="Enter quantity">
                        <small id="stockWarning" class="text-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> This will exceed current stock
                        </small>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Current Stock</label>
                        <div id="currentStock" style="padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px; font-size: 1.2rem; font-weight: bold;">
                            Select a product to see current stock
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> New Stock Level</label>
                        <div id="newStock" style="padding: 0.75rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px; font-size: 1.2rem; font-weight: bold; color: #27ae60;">
                            -
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason"><i class="fas fa-comment"></i> Reason for Adjustment *</label>
                        <select id="reason" name="reason" class="form-control" required>
                            <option value="">Select Reason</option>
                            <option value="Inventory Count">Inventory Count</option>
                            <option value="Damaged Goods">Damaged Goods</option>
                            <option value="Theft/Loss">Theft/Loss</option>
                            <option value="Return to Supplier">Return to Supplier</option>
                            <option value="Customer Return">Customer Return</option>
                            <option value="System Correction">System Correction</option>
                            <option value="Quality Control">Quality Control</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customReasonDiv" style="display: none;">
                        <label for="customReason"><i class="fas fa-edit"></i> Custom Reason</label>
                        <input type="text" id="customReason" class="form-control" 
                               placeholder="Enter custom reason">
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Apply Adjustment
                </button>
                <button type="reset" class="btn btn-secondary" id="resetBtn">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
                <a href="manage_stock.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Recent Adjustments -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Stock Adjustments</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_movements = $conn->query("
                        SELECT sm.*, p.name as product_name 
                        FROM stock_movements sm 
                        JOIN products p ON sm.product_id = p.product_id 
                        ORDER BY sm.created_at DESC 
                        LIMIT 15
                    ");
                    
                    if ($recent_movements->num_rows > 0):
                        while ($movement = $recent_movements->fetch_assoc()):
                            $typeClass = $movement['movement_type'] == 'in' ? 'text-success' : 'text-danger';
                            $typeIcon = $movement['movement_type'] == 'in' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                            $typeText = $movement['movement_type'] == 'in' ? 'Added' : 'Removed';
                    ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                        <td class="<?php echo $typeClass; ?>">
                            <i class="<?php echo $typeIcon; ?>"></i> <?php echo $typeText; ?>
                        </td>
                        <td><strong><?php echo $movement['quantity']; ?></strong></td>
                        <td><?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">
                            <i class="fas fa-info-circle"></i> No stock movements recorded yet
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
    const adjustmentType = document.getElementById('adjustment_type');
    const quantityInput = document.getElementById('quantity');
    const reasonSelect = document.getElementById('reason');
    const customReasonDiv = document.getElementById('customReasonDiv');
    const customReasonInput = document.getElementById('customReason');
    const currentStockDiv = document.getElementById('currentStock');
    const newStockDiv = document.getElementById('newStock');
    const stockWarning = document.getElementById('stockWarning');
    const form = document.getElementById('adjustmentForm');
    
    // Update current stock display when product changes
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        
        if (this.value) {
            currentStockDiv.textContent = currentStock + ' units';
        } else {
            currentStockDiv.textContent = 'Select a product to see current stock';
        }
        
        updateNewStock();
    });
    
    // Update calculations when adjustment type or quantity changes
    adjustmentType.addEventListener('change', updateNewStock);
    quantityInput.addEventListener('input', updateNewStock);
    
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
        const currentStock = parseInt(productSelect.options[productSelect.selectedIndex].getAttribute('data-stock')) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const type = adjustmentType.value;
        const reason = reasonSelect.value === 'Other' ? customReasonInput.value : reasonSelect.value;
        
        if (!productSelect.value) {
            e.preventDefault();
            showNotification('Please select a product', 'danger');
            return;
        }
        
        if (!type) {
            e.preventDefault();
            showNotification('Please select adjustment type', 'danger');
            return;
        }
        
        if (quantity <= 0) {
            e.preventDefault();
            showNotification('Please enter a valid quantity', 'danger');
            return;
        }
        
        if (!reason) {
            e.preventDefault();
            showNotification('Please provide a reason for the adjustment', 'danger');
            return;
        }
        
        // Confirm adjustment
        const action = type === 'add' ? 'add' : 'remove';
        const newStock = type === 'add' ? currentStock + quantity : Math.max(0, currentStock - quantity);
        
        if (!confirm(`Confirm stock adjustment:\nProduct: ${productName}\nAction: ${action.toUpperCase()} ${quantity} units\nCurrent Stock: ${currentStock}\nNew Stock: ${newStock}\nReason: ${reason}`)) {
            e.preventDefault();
            return;
        }
        
        // Update reason field if custom
        if (reasonSelect.value === 'Other') {
            reasonSelect.innerHTML += `<option value="${customReasonInput.value}" selected>${customReasonInput.value}</option>`;
        }
    });
    
    function updateNewStock() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const type = adjustmentType.value;
        
        if (!productSelect.value || !type || !quantity) {
            newStockDiv.textContent = '-';
            stockWarning.style.display = 'none';
            return;
        }
        
        let newStock;
        if (type === 'add') {
            newStock = currentStock + quantity;
            stockWarning.style.display = 'none';
        } else {
            newStock = Math.max(0, currentStock - quantity);
            if (quantity > currentStock) {
                stockWarning.style.display = 'block';
                stockWarning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Only ${currentStock} units available. Will remove all remaining stock.`;
            } else {
                stockWarning.style.display = 'none';
            }
        }
        
        newStockDiv.textContent = newStock + ' units';
        
        // Update color based on stock level
        if (newStock === 0) {
            newStockDiv.style.background = 'rgba(231, 76, 60, 0.1)';
            newStockDiv.style.color = '#e74c3c';
        } else if (newStock <= 10) {
            newStockDiv.style.background = 'rgba(243, 156, 18, 0.1)';
            newStockDiv.style.color = '#f39c12';
        } else {
            newStockDiv.style.background = 'rgba(46, 204, 113, 0.1)';
            newStockDiv.style.color = '#27ae60';
        }
    }
    
    // Initialize if product is pre-selected
    if (productSelect.value) {
        productSelect.dispatchEvent(new Event('change'));
    }
    
    // Quick adjustment buttons for common actions
    const quickActions = document.createElement('div');
    quickActions.innerHTML = `
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
            <h5><i class="fas fa-zap"></i> Quick Actions</h5>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-success quick-btn" data-type="add" data-qty="10">+10</button>
                <button type="button" class="btn btn-sm btn-success quick-btn" data-type="add" data-qty="25">+25</button>
                <button type="button" class="btn btn-sm btn-success quick-btn" data-type="add" data-qty="50">+50</button>
                <button type="button" class="btn btn-sm btn-warning quick-btn" data-type="remove" data-qty="1">-1</button>
                <button type="button" class="btn btn-sm btn-warning quick-btn" data-type="remove" data-qty="5">-5</button>
                <button type="button" class="btn btn-sm btn-warning quick-btn" data-type="remove" data-qty="10">-10</button>
                <button type="button" class="btn btn-sm btn-danger quick-btn" data-type="remove" data-qty="all">Clear All</button>
            </div>
        </div>
    `;
    quantityInput.closest('.form-group').appendChild(quickActions);
    
    // Quick action button handlers
    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const qty = this.getAttribute('data-qty');
            
            adjustmentType.value = type;
            
            if (qty === 'all') {
                const currentStock = parseInt(productSelect.options[productSelect.selectedIndex].getAttribute('data-stock')) || 0;
                quantityInput.value = currentStock;
            } else {
                quantityInput.value = qty;
            }
            
            adjustmentType.dispatchEvent(new Event('change'));
            quantityInput.dispatchEvent(new Event('input'));
        });
    });
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

#currentStock, #newStock {
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

.quick-btn {
    min-width: 50px;
}

.quick-btn:hover {
    transform: scale(1.05);
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}
</style>

<?php include '../includes/footer.php'; ?>