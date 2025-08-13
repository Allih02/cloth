<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-boxes"></i> Stock Management</h2>

<div class="card">
    <div class="card-header">
        <h3>Current Stock Levels</h3>
        <?php if (isAdmin()): ?>
            <a href="restock.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Restock Products
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Stock <?php echo htmlspecialchars($_GET['success']); ?> successfully!
            </div>
        <?php endif; ?>
        
        <!-- Stock Filter -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <select id="statusFilter" class="form-control" style="max-width: 200px;">
                <option value="">All Stock Levels</option>
                <option value="in-stock">In Stock (>20)</option>
                <option value="low-stock">Low Stock (1-20)</option>
                <option value="out-of-stock">Out of Stock (0)</option>
            </select>
            
            <select id="categoryFilter" class="form-control" style="max-width: 200px;">
                <option value="">All Categories</option>
                <?php
                $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                while ($category = $categories->fetch_assoc()):
                ?>
                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
            
            <input type="text" id="searchInput" placeholder="Search products..." class="form-control" style="max-width: 300px;">
        </div>
        
        <!-- Stock Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <?php
            $stockStats = $conn->query("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN stock_quantity > 20 THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN stock_quantity BETWEEN 1 AND 20 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(stock_quantity) as total_units
                FROM products
            ")->fetch_assoc();
            ?>
            
            <div style="text-align: center; padding: 1rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #3498db;">Total Products</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $stockStats['total_products']; ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #2ecc71;">In Stock</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $stockStats['in_stock']; ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(243, 156, 18, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #f39c12;">Low Stock</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $stockStats['low_stock']; ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(231, 76, 60, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #e74c3c;">Out of Stock</h4>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $stockStats['out_of_stock']; ?>
                </p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="stockTable">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT p.product_id, p.name, p.size, p.color, p.stock_quantity, c.name AS category_name 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.stock_quantity ASC, p.name ASC
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                        $status = '';
                        $class = '';
                        $icon = '';
                        
                        if ($row['stock_quantity'] > 20) {
                            $status = 'In Stock';
                            $class = 'text-success';
                            $icon = 'fas fa-check-circle';
                        } elseif ($row['stock_quantity'] > 0) {
                            $status = 'Low Stock';
                            $class = 'text-warning';
                            $icon = 'fas fa-exclamation-triangle';
                        } else {
                            $status = 'Out of Stock';
                            $class = 'text-danger';
                            $icon = 'fas fa-times-circle';
                        }
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($row['category_name']); ?>" 
                        data-stock="<?php echo $row['stock_quantity']; ?>"
                        data-status="<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
                        <td><?php echo $row['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td>
                            <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo strtolower($row['color']); ?>; border: 1px solid #ddd; border-radius: 3px; margin-right: 5px; vertical-align: middle;"></span>
                            <?php echo htmlspecialchars($row['color']); ?>
                        </td>
                        <td>
                            <span style="font-size: 1.2rem; font-weight: bold;" class="<?php echo $class; ?>">
                                <?php echo $row['stock_quantity']; ?>
                            </span>
                        </td>
                        <td class="<?php echo $class; ?>">
                            <i class="<?php echo $icon; ?>"></i> <?php echo $status; ?>
                        </td>
                        <td>
                            <?php if (isAdmin()): ?>
                                <a href="restock.php?product_id=<?php echo $row['product_id']; ?>" 
                                   class="btn btn-primary btn-sm" title="Restock Product">
                                    <i class="fas fa-plus"></i> Restock
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($row['stock_quantity'] == 0): ?>
                                <span class="btn btn-danger btn-sm" style="cursor: not-allowed;" title="Out of stock">
                                    <i class="fas fa-ban"></i> Empty
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Movement History -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exchange-alt"></i> Recent Stock Changes</h3>
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
                    $movements = $conn->query("
                        SELECT sm.*, p.name as product_name 
                        FROM stock_movements sm 
                        JOIN products p ON sm.product_id = p.product_id 
                        ORDER BY sm.created_at DESC 
                        LIMIT 10
                    ");
                    
                    if ($movements->num_rows > 0):
                        while ($movement = $movements->fetch_assoc()):
                            $typeClass = $movement['movement_type'] == 'in' ? 'text-success' : 'text-danger';
                            $typeIcon = $movement['movement_type'] == 'in' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                    ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                        <td class="<?php echo $typeClass; ?>">
                            <i class="<?php echo $typeIcon; ?>"></i> 
                            <?php echo ucfirst($movement['movement_type']); ?>
                        </td>
                        <td><?php echo $movement['quantity']; ?></td>
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
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#stockTable tbody tr');

    function filterTable() {
        const statusValue = statusFilter.value;
        const categoryValue = categoryFilter.value;
        const searchTerm = searchInput.value.toLowerCase();

        let visibleCount = 0;

        tableRows.forEach(row => {
            const status = row.getAttribute('data-status');
            const category = row.getAttribute('data-category');
            const text = row.textContent.toLowerCase();
            
            let showRow = true;

            // Status filter
            if (statusValue && status !== statusValue) {
                showRow = false;
            }

            // Category filter
            if (categoryValue && category !== categoryValue) {
                showRow = false;
            }

            // Search filter
            if (searchTerm && !text.includes(searchTerm)) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        updateTableInfo(visibleCount, tableRows.length);
    }

    function updateTableInfo(visible, total) {
        let infoElement = document.getElementById('tableInfo');
        if (!infoElement) {
            infoElement = document.createElement('div');
            infoElement.id = 'tableInfo';
            infoElement.style.marginTop = '1rem';
            infoElement.style.fontStyle = 'italic';
            infoElement.style.color = '#666';
            document.querySelector('#stockTable').parentNode.appendChild(infoElement);
        }
        infoElement.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visible} of ${total} products`;
    }

    // Event listeners
    statusFilter.addEventListener('change', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);

    // Initialize table info
    updateTableInfo(tableRows.length, tableRows.length);

    // Auto-refresh stock alerts
    function checkStockAlerts() {
        const lowStockRows = Array.from(tableRows).filter(row => {
            const stock = parseInt(row.getAttribute('data-stock'));
            return stock > 0 && stock <= 10;
        });

        const outOfStockRows = Array.from(tableRows).filter(row => {
            const stock = parseInt(row.getAttribute('data-stock'));
            return stock === 0;
        });

        if (outOfStockRows.length > 0) {
            showNotification(`${outOfStockRows.length} products are out of stock!`, 'danger');
        } else if (lowStockRows.length > 0) {
            showNotification(`${lowStockRows.length} products have low stock`, 'warning');
        }
    }

    // Check alerts on page load
    setTimeout(checkStockAlerts, 2000);

    // Highlight critical stock levels
    tableRows.forEach(row => {
        const stock = parseInt(row.getAttribute('data-stock'));
        if (stock === 0) {
            row.style.backgroundColor = 'rgba(231, 76, 60, 0.05)';
        } else if (stock <= 5) {
            row.style.backgroundColor = 'rgba(243, 156, 18, 0.05)';
        }
    });

    // Sort functionality
    const headers = document.querySelectorAll('#stockTable th');
    headers.forEach((header, index) => {
        if (index < 6) { // Only make sortable headers clickable
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(index));
        }
    });

    function sortTable(columnIndex) {
        const table = document.getElementById('stockTable');
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.rows);
        
        // Toggle sort direction
        const isAscending = !table.dataset.sortAsc || table.dataset.sortAsc === 'false';
        table.dataset.sortAsc = isAscending;
        
        rows.sort((a, b) => {
            let aValue = a.cells[columnIndex].textContent.trim();
            let bValue = b.cells[columnIndex].textContent.trim();
            
            // Handle numeric columns
            if (columnIndex === 0 || columnIndex === 5) { // ID and Stock columns
                aValue = parseInt(aValue) || 0;
                bValue = parseInt(bValue) || 0;
            }
            
            if (aValue < bValue) return isAscending ? -1 : 1;
            if (aValue > bValue) return isAscending ? 1 : -1;
            return 0;
        });
        
        // Re-append rows in sorted order
        rows.forEach(row => tbody.appendChild(row));
        
        // Update header indicators
        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
        headers[columnIndex].classList.add(isAscending ? 'sorted-asc' : 'sorted-desc');
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="display: flex"] {
        flex-direction: column !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

.table tbody tr:hover {
    transform: scale(1.01);
    transition: all 0.2s ease;
}

#stockTable th[style*="cursor: pointer"]:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6b5b95 100%);
    color: white;
}

.sorted-asc::after {
    content: ' ↑';
    color: #fff;
}

.sorted-desc::after {
    content: ' ↓';
    color: #fff;
}

/* Stock level indicators */
tr[data-stock="0"] {
    animation: pulse-red 2s infinite;
}

tr[data-stock="1"], tr[data-stock="2"], tr[data-stock="3"], tr[data-stock="4"], tr[data-stock="5"] {
    animation: pulse-orange 3s infinite;
}

@keyframes pulse-red {
    0%, 100% { background-color: rgba(231, 76, 60, 0.05); }
    50% { background-color: rgba(231, 76, 60, 0.15); }
}

@keyframes pulse-orange {
    0%, 100% { background-color: rgba(243, 156, 18, 0.05); }
    50% { background-color: rgba(243, 156, 18, 0.1); }
}

/* Filter controls styling */
#statusFilter, #categoryFilter, #searchInput {
    transition: all 0.3s ease;
}

#statusFilter:focus, #categoryFilter:focus, #searchInput:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}
</style>

<?php include '../includes/footer.php'; ?>