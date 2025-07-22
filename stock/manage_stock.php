<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_stock':
            try {
                $product_id = intval($_POST['product_id']);
                $new_stock = intval($_POST['new_stock']);
                $reason = trim($_POST['reason']) ?: 'Manual stock update';
                
                if ($product_id <= 0) throw new Exception("Invalid product ID");
                if ($new_stock < 0) throw new Exception("Stock cannot be negative");
                
                // Begin transaction
                $conn->begin_transaction();
                
                // Get current stock
                $stmt = $conn->prepare("SELECT name, stock_quantity FROM products WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();
                
                if (!$product) throw new Exception("Product not found");
                
                $old_stock = $product['stock_quantity'];
                $difference = $new_stock - $old_stock;
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                $stmt->bind_param("ii", $new_stock, $product_id);
                $stmt->execute();
                $stmt->close();
                
                // Record stock movement
                if ($difference != 0) {
                    $movement_type = $difference > 0 ? 'in' : 'out';
                    $movement_quantity = abs($difference);
                    
                    $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isis", $product_id, $movement_type, $movement_quantity, $reason);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $conn->commit();
                $message = "Stock updated successfully for " . htmlspecialchars($product['name']) . ". Changed from {$old_stock} to {$new_stock} units.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
            break;
            
        case 'bulk_update':
            try {
                $updates = json_decode($_POST['bulk_data'], true);
                if (!$updates || !is_array($updates)) throw new Exception("Invalid bulk update data");
                
                $conn->begin_transaction();
                $updated_count = 0;
                
                foreach ($updates as $update) {
                    $product_id = intval($update['product_id']);
                    $new_stock = intval($update['new_stock']);
                    
                    if ($product_id <= 0 || $new_stock < 0) continue;
                    
                    // Get current stock
                    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $current = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!$current) continue;
                    
                    // Update stock
                    $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                    $stmt->bind_param("ii", $new_stock, $product_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Record movement
                    $difference = $new_stock - $current['stock_quantity'];
                    if ($difference != 0) {
                        $movement_type = $difference > 0 ? 'in' : 'out';
                        $movement_quantity = abs($difference);
                        
                        $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isis", $product_id, $movement_type, $movement_quantity, "Bulk update");
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    $updated_count++;
                }
                
                $conn->commit();
                $message = "Bulk update completed. {$updated_count} products updated.";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
            break;
    }
}

// Get all products for management
$products = $conn->query("
    SELECT p.product_id, p.name, p.stock_quantity, p.price, c.name as category_name, s.name as supplier_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    ORDER BY p.name
");

// Get low stock count
$low_stock_count = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 10")->fetch_row()[0];
$out_of_stock_count = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetch_row()[0];
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-cogs"></i> Stock Management</h2>

<!-- Quick Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card primary">
        <h4><i class="fas fa-boxes"></i> Total Products</h4>
        <p><?php echo $products->num_rows; ?></p>
    </div>
    <div class="stat-card warning">
        <h4><i class="fas fa-exclamation-triangle"></i> Low Stock</h4>
        <p><?php echo $low_stock_count; ?></p>
    </div>
    <div class="stat-card danger">
        <h4><i class="fas fa-times-circle"></i> Out of Stock</h4>
        <p><?php echo $out_of_stock_count; ?></p>
    </div>
    <div class="stat-card success">
        <h4><i class="fas fa-check-circle"></i> Well Stocked</h4>
        <p><?php echo $products->num_rows - $low_stock_count; ?></p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Stock Management Tools -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-tools"></i> Stock Management Tools</h3>
        <div>
            <button id="bulkUpdateBtn" class="btn btn-warning">
                <i class="fas fa-edit"></i> Bulk Update
            </button>
            <a href="restock.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Quick Restock
            </a>
            <a href="view_stock.php" class="btn btn-secondary">
                <i class="fas fa-eye"></i> View Only
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Search and Filter -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="text" id="searchInput" placeholder="Search products..." class="form-control" style="max-width: 300px;">
            <select id="stockFilter" class="form-control" style="max-width: 200px;">
                <option value="">All Stock Levels</option>
                <option value="out">Out of Stock (0)</option>
                <option value="low">Low Stock (1-10)</option>
                <option value="medium">Medium Stock (11-20)</option>
                <option value="high">High Stock (20+)</option>
            </select>
            <button id="exportBtn" class="btn btn-info">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="stockTable">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" title="Select All">
                        </th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>New Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $products->data_seek(0); // Reset pointer
                    while ($product = $products->fetch_assoc()): 
                        $stock_class = '';
                        $status = '';
                        if ($product['stock_quantity'] == 0) {
                            $stock_class = 'text-danger';
                            $status = 'Out of Stock';
                        } elseif ($product['stock_quantity'] <= 10) {
                            $stock_class = 'text-warning';
                            $status = 'Low Stock';
                        } elseif ($product['stock_quantity'] <= 20) {
                            $stock_class = 'text-info';
                            $status = 'Medium Stock';
                        } else {
                            $stock_class = 'text-success';
                            $status = 'Well Stocked';
                        }
                    ?>
                    <tr data-product-id="<?php echo $product['product_id']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                        <td>
                            <input type="checkbox" class="product-checkbox" value="<?php echo $product['product_id']; ?>">
                        </td>
                        <td><?php echo $product['product_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td class="<?php echo $stock_class; ?>">
                            <strong><?php echo $product['stock_quantity']; ?></strong>
                        </td>
                        <td>
                            <input type="number" class="form-control stock-input" 
                                   value="<?php echo $product['stock_quantity']; ?>" 
                                   min="0" style="width: 80px; display: inline-block;">
                        </td>
                        <td>
                            <span class="badge <?php echo str_replace('text-', 'badge-', $stock_class); ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm update-btn" 
                                    data-product-id="<?php echo $product['product_id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div id="updateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h4><i class="fas fa-edit"></i> Update Stock</h4>
        <form id="updateForm" method="POST">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="product_id" id="modalProductId">
            
            <div class="form-group">
                <label>Product:</label>
                <p id="modalProductName" style="font-weight: bold;"></p>
            </div>
            
            <div class="form-group">
                <label for="modalCurrentStock">Current Stock:</label>
                <input type="text" id="modalCurrentStock" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="new_stock">New Stock Quantity:</label>
                <input type="number" name="new_stock" id="modalNewStock" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason for Change:</label>
                <select name="reason" class="form-control">
                    <option value="Manual adjustment">Manual adjustment</option>
                    <option value="Inventory count">Inventory count</option>
                    <option value="Damaged goods">Damaged goods</option>
                    <option value="Return to supplier">Return to supplier</option>
                    <option value="System correction">System correction</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Stock
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Update Modal -->
<div id="bulkModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h4><i class="fas fa-edit"></i> Bulk Update Selected Items</h4>
        <div id="bulkUpdateList"></div>
        <div class="modal-actions">
            <button id="confirmBulkUpdate" class="btn btn-primary">
                <i class="fas fa-save"></i> Update All Selected
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const stockFilter = document.getElementById('stockFilter');
    const selectAll = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const updateButtons = document.querySelectorAll('.update-btn');
    const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
    const exportBtn = document.getElementById('exportBtn');

    // Search functionality
    searchInput.addEventListener('input', function() {
        filterTable();
    });

    stockFilter.addEventListener('change', function() {
        filterTable();
    });

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const stockLevel = stockFilter.value;
        const rows = document.querySelectorAll('#stockTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const stock = parseInt(row.getAttribute('data-stock'));
            let showRow = true;

            // Search filter
            if (searchTerm && !text.includes(searchTerm)) {
                showRow = false;
            }

            // Stock level filter
            if (stockLevel) {
                switch (stockLevel) {
                    case 'out':
                        if (stock !== 0) showRow = false;
                        break;
                    case 'low':
                        if (stock === 0 || stock > 10) showRow = false;
                        break;
                    case 'medium':
                        if (stock <= 10 || stock > 20) showRow = false;
                        break;
                    case 'high':
                        if (stock <= 20) showRow = false;
                        break;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    // Select all functionality
    selectAll.addEventListener('change', function() {
        const visibleCheckboxes = Array.from(productCheckboxes).filter(cb => 
            cb.closest('tr').style.display !== 'none'
        );
        
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Individual update buttons
    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const row = this.closest('tr');
            const currentStock = row.getAttribute('data-stock');
            const newStock = row.querySelector('.stock-input').value;

            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalCurrentStock').value = currentStock;
            document.getElementById('modalNewStock').value = newStock;

            document.getElementById('updateModal').style.display = 'block';
        });
    });

    // Bulk update
    bulkUpdateBtn.addEventListener('click', function() {
        const checkedBoxes = Array.from(productCheckboxes).filter(cb => cb.checked);
        
        if (checkedBoxes.length === 0) {
            showNotification('Please select at least one product', 'warning');
            return;
        }

        const bulkList = document.getElementById('bulkUpdateList');
        bulkList.innerHTML = '';

        checkedBoxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const productId = checkbox.value;
            const productName = row.cells[2].textContent;
            const currentStock = row.getAttribute('data-stock');
            const newStock = row.querySelector('.stock-input').value;

            const item = document.createElement('div');
            item.className = 'bulk-item';
            item.innerHTML = `
                <strong>${productName}</strong><br>
                Current: ${currentStock} → New: ${newStock}
                <input type="hidden" class="bulk-data" data-product-id="${productId}" data-new-stock="${newStock}">
            `;
            bulkList.appendChild(item);
        });

        document.getElementById('bulkModal').style.display = 'block';
    });

    // Confirm bulk update
    document.getElementById('confirmBulkUpdate').addEventListener('click', function() {
        const bulkData = Array.from(document.querySelectorAll('.bulk-data')).map(input => ({
            product_id: input.getAttribute('data-product-id'),
            new_stock: input.getAttribute('data-new-stock')
        }));

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_update">
            <input type="hidden" name="bulk_data" value='${JSON.stringify(bulkData)}'>
        `;
        document.body.appendChild(form);
        form.submit();
    });

    // Export functionality
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(document.querySelectorAll('#stockTable tbody tr')).filter(
            row => row.style.display !== 'none'
        );

        let csv = 'Product ID,Product Name,Category,Supplier,Price,Current Stock,Status\n';
        
        visibleRows.forEach(row => {
            const cells = row.cells;
            const data = [
                cells[1].textContent, // ID
                cells[2].textContent.replace(/,/g, ''), // Name
                cells[3].textContent, // Category
                cells[4].textContent, // Supplier
                cells[5].textContent, // Price
                cells[6].textContent, // Stock
                cells[8].textContent // Status
            ];
            csv += data.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `stock_report_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
});

function closeModal() {
    document.getElementById('updateModal').style.display = 'none';
}

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const updateModal = document.getElementById('updateModal');
    const bulkModal = document.getElementById('bulkModal');
    
    if (event.target === updateModal) {
        closeModal();
    }
    if (event.target === bulkModal) {
        closeBulkModal();
    }
});
</script>

<style>
.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid;
}

.stat-card.primary { border-left-color: #3498db; }
.stat-card.success { border-left-color: #2ecc71; }
.stat-card.warning { border-left-color: #f39c12; }
.stat-card.danger { border-left-color: #e74c3c; }

.stat-card h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    color: #666;
}

.stat-card p {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
}

.badge {
    padding: 0.25em 0.6em;
    font-size: 0.75em;
    font-weight: 700;
    border-radius: 0.375rem;
}

.badge-success { background-color: #28a745; color: white; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger { background-color: #dc3545; color: white; }
.badge-info { background-color: #17a2b8; color: white; }

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.bulk-item {
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 1rem;
    background: #f8f9fa;
}

.stock-input {
    max-width: 80px;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .stock-input {
        max-width: 60px;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>