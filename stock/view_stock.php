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

<!-- Complete Stock Movement History -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exchange-alt"></i> Complete Stock Movement History</h3>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <button id="exportMovementsBtn" class="btn btn-info btn-sm">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button id="refreshMovementsBtn" class="btn btn-secondary btn-sm">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Movement Filters -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <label for="movementSearch" style="margin-right: 0.5rem; font-weight: 600;">Search:</label>
                <input type="text" id="movementSearch" placeholder="Search by product name..." 
                       class="form-control" style="display: inline-block; width: 200px;">
            </div>
            
            <div>
                <label for="movementType" style="margin-right: 0.5rem; font-weight: 600;">Type:</label>
                <select id="movementType" class="form-control" style="display: inline-block; width: 120px;">
                    <option value="">All Types</option>
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                </select>
            </div>
            
            <div>
                <label for="dateFilter" style="margin-right: 0.5rem; font-weight: 600;">Date:</label>
                <select id="dateFilter" class="form-control" style="display: inline-block; width: 150px;">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
            
            <div>
                <label for="movementReason" style="margin-right: 0.5rem; font-weight: 600;">Reason:</label>
                <select id="movementReason" class="form-control" style="display: inline-block; width: 180px;">
                    <option value="">All Reasons</option>
                    <option value="New Purchase">New Purchase</option>
                    <option value="Supplier Delivery">Supplier Delivery</option>
                    <option value="Sale">Sale</option>
                    <option value="Return from Customer">Return from Customer</option>
                    <option value="Inventory Adjustment">Inventory Adjustment</option>
                    <option value="Manual adjustment">Manual adjustment</option>
                    <option value="Damaged Goods">Damaged Goods</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <button id="clearFiltersBtn" class="btn btn-warning btn-sm">
                <i class="fas fa-times"></i> Clear Filters
            </button>
        </div>
        
        <!-- Movement Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <?php
            $movementStats = $conn->query("
                SELECT 
                    COUNT(*) as total_movements,
                    SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as total_out,
                    COUNT(DISTINCT product_id) as affected_products
                FROM stock_movements
            ")->fetch_assoc();
            ?>
            
            <div style="text-align: center; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                <h5 style="margin: 0; color: #667eea;">Total Movements</h5>
                <p style="font-size: 1.3rem; font-weight: bold; margin: 0.25rem 0 0 0;">
                    <?php echo number_format($movementStats['total_movements']); ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h5 style="margin: 0; color: #2ecc71;">Stock Added</h5>
                <p style="font-size: 1.3rem; font-weight: bold; margin: 0.25rem 0 0 0;">
                    <?php echo number_format($movementStats['total_in']); ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(231, 76, 60, 0.1); border-radius: 8px;">
                <h5 style="margin: 0; color: #e74c3c;">Stock Removed</h5>
                <p style="font-size: 1.3rem; font-weight: bold; margin: 0.25rem 0 0 0;">
                    <?php echo number_format($movementStats['total_out']); ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(155, 89, 182, 0.1); border-radius: 8px;">
                <h5 style="margin: 0; color: #9b59b6;">Affected Products</h5>
                <p style="font-size: 1.3rem; font-weight: bold; margin: 0.25rem 0 0 0;">
                    <?php echo number_format($movementStats['affected_products']); ?>
                </p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="movementsTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="movementsTableBody">
                    <?php
                    // Get all stock movements with pagination support
                    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $limit = 50; // Show 50 movements per load
                    $offset = ($page - 1) * $limit;
                    
                    $movements = $conn->query("
                        SELECT sm.*, p.name as product_name, p.product_id
                        FROM stock_movements sm 
                        JOIN products p ON sm.product_id = p.product_id 
                        ORDER BY sm.created_at DESC
                        LIMIT $limit OFFSET $offset
                    ");
                    
                    if ($movements->num_rows > 0):
                        while ($movement = $movements->fetch_assoc()):
                            $typeClass = $movement['movement_type'] == 'in' ? 'text-success' : 'text-danger';
                            $typeIcon = $movement['movement_type'] == 'in' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                            $typeText = $movement['movement_type'] == 'in' ? 'Stock In' : 'Stock Out';
                            $rowClass = $movement['movement_type'] == 'in' ? 'table-success-light' : 'table-danger-light';
                    ?>
                    <tr class="<?php echo $rowClass; ?>" 
                        data-date="<?php echo date('Y-m-d', strtotime($movement['created_at'])); ?>"
                        data-type="<?php echo $movement['movement_type']; ?>"
                        data-reason="<?php echo htmlspecialchars($movement['reason'] ?? ''); ?>"
                        data-product="<?php echo strtolower(htmlspecialchars($movement['product_name'])); ?>">
                        <td>
                            <div style="font-weight: 600; color: #333;">
                                <?php echo date('M j, Y', strtotime($movement['created_at'])); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #666;">
                                <?php echo date('g:i A', strtotime($movement['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                <?php echo htmlspecialchars($movement['product_name']); ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #666;">
                                ID: <?php echo $movement['product_id']; ?>
                            </div>
                        </td>
                        <td class="<?php echo $typeClass; ?>">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="<?php echo $typeIcon; ?>"></i> 
                                <span style="font-weight: 600;"><?php echo $typeText; ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 1.1rem; font-weight: bold; color: <?php echo $movement['movement_type'] == 'in' ? '#2ecc71' : '#e74c3c'; ?>">
                                <?php echo $movement['movement_type'] == 'in' ? '+' : '-'; ?><?php echo number_format($movement['quantity']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="padding: 0.25rem 0.5rem; background: rgba(102, 126, 234, 0.1); border-radius: 12px; font-size: 0.8rem; font-weight: 500;">
                                <?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm view-details-btn" 
                                    data-movement-id="<?php echo $movement['movement_id']; ?>"
                                    data-product="<?php echo htmlspecialchars($movement['product_name']); ?>"
                                    data-type="<?php echo $typeText; ?>"
                                    data-quantity="<?php echo $movement['quantity']; ?>"
                                    data-date="<?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?>"
                                    data-reason="<?php echo htmlspecialchars($movement['reason'] ?? 'N/A'); ?>">
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #666; padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3>No stock movements recorded yet</h3>
                            <p>Stock movements will appear here when products are restocked or sold.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Load More Button -->
        <?php 
        $totalMovements = $conn->query("SELECT COUNT(*) FROM stock_movements")->fetch_row()[0];
        $hasMore = ($page * $limit) < $totalMovements;
        ?>
        
        <?php if ($hasMore): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <button id="loadMoreBtn" class="btn btn-primary" data-page="<?php echo $page + 1; ?>">
                <i class="fas fa-plus"></i> Load More Movements 
                <span style="opacity: 0.8;">(Showing <?php echo min($page * $limit, $totalMovements); ?> of <?php echo number_format($totalMovements); ?>)</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Movement Info -->
        <div style="margin-top: 2rem; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 1rem; justify-content: space-between; flex-wrap: wrap;">
                <div>
                    <strong><i class="fas fa-info-circle"></i> Movement Information:</strong>
                    <span id="movementCount"><?php echo number_format($movements->num_rows); ?></span> movements shown
                </div>
                <div style="font-size: 0.9rem; color: #666;">
                    Last updated: <?php echo date('M j, Y g:i A'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Movement Details Modal -->
<div id="movementModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-info-circle"></i> Movement Details</h4>
            <button class="modal-close" onclick="closeMovementModal()">&times;</button>
        </div>
        <div class="modal-body" id="movementModalBody">
            <!-- Movement details will be populated here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeMovementModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#stockTable tbody tr');
    
    // Movement filters
    const movementSearch = document.getElementById('movementSearch');
    const movementType = document.getElementById('movementType');
    const dateFilter = document.getElementById('dateFilter');
    const movementReason = document.getElementById('movementReason');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const movementRows = document.querySelectorAll('#movementsTable tbody tr');
    const exportMovementsBtn = document.getElementById('exportMovementsBtn');
    const refreshMovementsBtn = document.getElementById('refreshMovementsBtn');
    const loadMoreBtn = document.getElementById('loadMoreBtn');

    // Stock table filtering
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

            if (statusValue && status !== statusValue) showRow = false;
            if (categoryValue && category !== categoryValue) showRow = false;
            if (searchTerm && !text.includes(searchTerm)) showRow = false;

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        updateTableInfo(visibleCount, tableRows.length);
    }

    // Movement table filtering
    function filterMovements() {
        const searchTerm = movementSearch.value.toLowerCase();
        const typeValue = movementType.value;
        const dateValue = dateFilter.value;
        const reasonValue = movementReason.value;

        let visibleCount = 0;
        const today = new Date();

        movementRows.forEach(row => {
            const productName = row.getAttribute('data-product') || '';
            const type = row.getAttribute('data-type') || '';
            const reason = row.getAttribute('data-reason') || '';
            const dateStr = row.getAttribute('data-date') || '';
            const rowDate = new Date(dateStr);
            
            let showRow = true;

            // Search filter
            if (searchTerm && !productName.includes(searchTerm)) showRow = false;
            
            // Type filter
            if (typeValue && type !== typeValue) showRow = false;
            
            // Reason filter
            if (reasonValue && reason !== reasonValue) showRow = false;
            
            // Date filter
            if (dateValue && showRow) {
                const daysDiff = (today - rowDate) / (1000 * 60 * 60 * 24);
                
                switch (dateValue) {
                    case 'today':
                        if (daysDiff > 1) showRow = false;
                        break;
                    case 'week':
                        if (daysDiff > 7) showRow = false;
                        break;
                    case 'month':
                        if (daysDiff > 30) showRow = false;
                        break;
                    case 'year':
                        if (daysDiff > 365) showRow = false;
                        break;
                }
            }

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        updateMovementInfo(visibleCount, movementRows.length);
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

    function updateMovementInfo(visible, total) {
        const countElement = document.getElementById('movementCount');
        if (countElement) {
            countElement.textContent = `${visible}`;
        }
    }

    // Clear all movement filters
    clearFiltersBtn.addEventListener('click', function() {
        movementSearch.value = '';
        movementType.value = '';
        dateFilter.value = '';
        movementReason.value = '';
        
        movementRows.forEach(row => {
            row.style.display = '';
        });
        
        updateMovementInfo(movementRows.length, movementRows.length);
        showNotification('All filters cleared', 'info');
    });

    // Export movements to CSV
    exportMovementsBtn.addEventListener('click', function() {
        const visibleRows = Array.from(movementRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            showNotification('No movements to export', 'warning');
            return;
        }
        
        let csv = 'Date,Time,Product,Type,Quantity,Reason\n';
        
        visibleRows.forEach(row => {
            const cells = row.cells;
            if (cells.length >= 5) {
                const dateTime = cells[0].textContent.trim().replace(/\s+/g, ' ');
                const dateParts = dateTime.split(' ');
                const date = dateParts.slice(0, 3).join(' ');
                const time = dateParts.slice(3).join(' ');
                
                const product = cells[1].textContent.trim().replace(/\s+/g, ' ');
                const type = cells[2].textContent.trim();
                const quantity = cells[3].textContent.trim();
                const reason = cells[4].textContent.trim();
                
                const rowData = [date, time, product, type, quantity, reason].map(val => 
                    '"' + val.replace(/"/g, '""') + '"'
                );
                csv += rowData.join(',') + '\n';
            }
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `stock_movements_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Stock movements exported successfully!', 'success');
    });

    // Refresh movements
    refreshMovementsBtn.addEventListener('click', function() {
        const originalContent = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        this.disabled = true;
        
        setTimeout(() => {
            location.reload();
        }, 500);
    });

    // Load more movements
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            const originalContent = this.innerHTML;
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.disabled = true;
            
            // Fetch more movements via AJAX
            fetch(`view_stock.php?page=${page}&ajax=1`)
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    const newRows = tempDiv.querySelectorAll('#movementsTableBody tr');
                    const tableBody = document.getElementById('movementsTableBody');
                    
                    newRows.forEach(row => {
                        tableBody.appendChild(row);
                    });
                    
                    // Update page number or hide button if no more
                    const nextPage = parseInt(page) + 1;
                    const hasMore = tempDiv.querySelector('#loadMoreBtn');
                    
                    if (hasMore) {
                        this.setAttribute('data-page', nextPage);
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    } else {
                        this.style.display = 'none';
                        showNotification('All movements loaded', 'info');
                    }
                    
                    // Re-attach event listeners to new detail buttons
                    attachDetailButtonListeners();
                })
                .catch(error => {
                    console.error('Error loading more movements:', error);
                    this.innerHTML = originalContent;
                    this.disabled = false;
                    showNotification('Error loading more movements', 'error');
                });
        });
    }

    // Detail buttons functionality
    function attachDetailButtonListeners() {
        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.removeEventListener('click', showMovementDetails); // Remove existing listeners
            btn.addEventListener('click', showMovementDetails);
        });
    }

    function showMovementDetails() {
        const movementId = this.getAttribute('data-movement-id');
        const product = this.getAttribute('data-product');
        const type = this.getAttribute('data-type');
        const quantity = this.getAttribute('data-quantity');
        const date = this.getAttribute('data-date');
        const reason = this.getAttribute('data-reason');
        
        const modalBody = document.getElementById('movementModalBody');
        modalBody.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <h5><i class="fas fa-tag"></i> Movement Information</h5>
                    <table style="width: 100%; font-size: 0.9rem;">
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Movement ID:</td>
                            <td style="padding: 0.5rem 0;">#${movementId}</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Type:</td>
                            <td style="padding: 0.5rem 0;">
                                <span style="color: ${type.includes('In') ? '#2ecc71' : '#e74c3c'};">
                                    <i class="fas fa-arrow-${type.includes('In') ? 'up' : 'down'}"></i> ${type}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Quantity:</td>
                            <td style="padding: 0.5rem 0; font-size: 1.1rem; font-weight: bold;">
                                ${type.includes('In') ? '+' : '-'}${quantity} units
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Date & Time:</td>
                            <td style="padding: 0.5rem 0;">${date}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    <h5><i class="fas fa-tshirt"></i> Product Information</h5>
                    <table style="width: 100%; font-size: 0.9rem;">
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Product:</td>
                            <td style="padding: 0.5rem 0;">${product}</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.5rem 0; font-weight: 600;">Reason:</td>
                            <td style="padding: 0.5rem 0;">
                                <span style="padding: 0.25rem 0.5rem; background: rgba(102, 126, 234, 0.1); border-radius: 12px; font-size: 0.8rem;">
                                    ${reason}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                <h6><i class="fas fa-info-circle"></i> Movement Impact</h6>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                    This ${type.toLowerCase()} movement ${type.includes('In') ? 'increased' : 'decreased'} 
                    the stock level of <strong>${product}</strong> by <strong>${quantity} units</strong>.
                </p>
            </div>
        `;
        
        document.getElementById('movementModal').style.display = 'block';
    }

    // Event listeners
    statusFilter.addEventListener('change', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);

    movementSearch.addEventListener('input', filterMovements);
    movementType.addEventListener('change', filterMovements);
    dateFilter.addEventListener('change', filterMovements);
    movementReason.addEventListener('change', filterMovements);

    // Initialize
    updateTableInfo(tableRows.length, tableRows.length);
    updateMovementInfo(movementRows.length, movementRows.length);
    
    // Attach detail button listeners initially
    attachDetailButtonListeners();

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

// Modal functions
function closeMovementModal() {
    document.getElementById('movementModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('movementModal');
    if (event.target === modal) {
        closeMovementModal();
    }
});

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getIconForType(type)}"></i> ${message}
    `;
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
        danger: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}
</script>

<style>
@media (max-width: 768px) {
    div[style*="display: flex"] {
        flex-direction: column !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr 1fr !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr))"] {
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
#statusFilter, #categoryFilter, #searchInput, 
#movementSearch, #movementType, #dateFilter, #movementReason {
    transition: all 0.3s ease;
}

#statusFilter:focus, #categoryFilter:focus, #searchInput:focus,
#movementSearch:focus, #movementType:focus, #dateFilter:focus, #movementReason:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

/* Movement table styling */
.table-success-light {
    background-color: rgba(46, 204, 113, 0.03) !important;
    border-left: 3px solid #2ecc71;
}

.table-danger-light {
    background-color: rgba(231, 76, 60, 0.03) !important;
    border-left: 3px solid #e74c3c;
}

#movementsTable tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.08) !important;
    transform: translateX(5px);
    transition: all 0.2s ease;
}

/* Modal styling */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 0;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    margin: 0;
    font-size: 1.2rem;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 2rem;
    overflow-y: auto;
    max-height: 60vh;
}

.modal-footer {
    padding: 1rem 2rem;
    border-top: 1px solid #e1e8ed;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Enhanced button effects */
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn:active {
    transform: translateY(0);
}

/* Loading animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.fa-spinner.fa-spin {
    animation: spin 1s linear infinite;
}

/* Notification animations */
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

/* Enhanced alert styling */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    border-color: #2ecc71;
    color: #27ae60;
}

.alert-danger {
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

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    border-color: #e74c3c;
    color: #c0392b;
}

/* Improved responsive design for movement filters */
@media (max-width: 1200px) {
    div[style*="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;"] {
        display: block !important;
    }
    
    div[style*="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;"] > div {
        display: block !important;
        margin-bottom: 1rem !important;
    }
    
    div[style*="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;"] label {
        display: block !important;
        margin-bottom: 0.25rem !important;
    }
    
    div[style*="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;"] .form-control {
        width: 100% !important;
        display: block !important;
    }
}
</style>

<?php 
// Handle AJAX requests for loading more movements
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    $movements = $conn->query("
        SELECT sm.*, p.name as product_name, p.product_id
        FROM stock_movements sm 
        JOIN products p ON sm.product_id = p.product_id 
        ORDER BY sm.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    
    if ($movements->num_rows > 0) {
        echo '<div id="movementsTableBody">';
        while ($movement = $movements->fetch_assoc()) {
            $typeClass = $movement['movement_type'] == 'in' ? 'text-success' : 'text-danger';
            $typeIcon = $movement['movement_type'] == 'in' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
            $typeText = $movement['movement_type'] == 'in' ? 'Stock In' : 'Stock Out';
            $rowClass = $movement['movement_type'] == 'in' ? 'table-success-light' : 'table-danger-light';
            
            echo '<tr class="' . $rowClass . '" 
                    data-date="' . date('Y-m-d', strtotime($movement['created_at'])) . '"
                    data-type="' . $movement['movement_type'] . '"
                    data-reason="' . htmlspecialchars($movement['reason'] ?? '') . '"
                    data-product="' . strtolower(htmlspecialchars($movement['product_name'])) . '">';
            
            echo '<td>';
            echo '<div style="font-weight: 600; color: #333;">' . date('M j, Y', strtotime($movement['created_at'])) . '</div>';
            echo '<div style="font-size: 0.85rem; color: #666;">' . date('g:i A', strtotime($movement['created_at'])) . '</div>';
            echo '</td>';
            
            echo '<td>';
            echo '<div style="font-weight: 600;">' . htmlspecialchars($movement['product_name']) . '</div>';
            echo '<div style="font-size: 0.8rem; color: #666;">ID: ' . $movement['product_id'] . '</div>';
            echo '</td>';
            
            echo '<td class="' . $typeClass . '">';
            echo '<div style="display: flex; align-items: center; gap: 0.5rem;">';
            echo '<i class="' . $typeIcon . '"></i> <span style="font-weight: 600;">' . $typeText . '</span>';
            echo '</div>';
            echo '</td>';
            
            echo '<td>';
            echo '<span style="font-size: 1.1rem; font-weight: bold; color: ' . ($movement['movement_type'] == 'in' ? '#2ecc71' : '#e74c3c') . ';">';
            echo ($movement['movement_type'] == 'in' ? '+' : '-') . number_format($movement['quantity']);
            echo '</span>';
            echo '</td>';
            
            echo '<td>';
            echo '<span style="padding: 0.25rem 0.5rem; background: rgba(102, 126, 234, 0.1); border-radius: 12px; font-size: 0.8rem; font-weight: 500;">';
            echo htmlspecialchars($movement['reason'] ?? 'N/A');
            echo '</span>';
            echo '</td>';
            
            echo '<td>';
            echo '<button class="btn btn-info btn-sm view-details-btn" ';
            echo 'data-movement-id="' . $movement['movement_id'] . '" ';
            echo 'data-product="' . htmlspecialchars($movement['product_name']) . '" ';
            echo 'data-type="' . $typeText . '" ';
            echo 'data-quantity="' . $movement['quantity'] . '" ';
            echo 'data-date="' . date('M j, Y g:i A', strtotime($movement['created_at'])) . '" ';
            echo 'data-reason="' . htmlspecialchars($movement['reason'] ?? 'N/A') . '">';
            echo '<i class="fas fa-eye"></i> Details';
            echo '</button>';
            echo '</td>';
            
            echo '</tr>';
        }
        echo '</div>';
        
        // Check if there are more movements
        $totalMovements = $conn->query("SELECT COUNT(*) FROM stock_movements")->fetch_row()[0];
        $hasMore = ($page * $limit) < $totalMovements;
        
        if ($hasMore) {
            echo '<div style="text-align: center; margin-top: 2rem;">';
            echo '<button id="loadMoreBtn" class="btn btn-primary" data-page="' . ($page + 1) . '">';
            echo '<i class="fas fa-plus"></i> Load More Movements ';
            echo '<span style="opacity: 0.8;">(Showing ' . min($page * $limit, $totalMovements) . ' of ' . number_format($totalMovements) . ')</span>';
            echo '</button>';
            echo '</div>';
        }
    }
    
    exit; // Stop here for AJAX requests
}
?>

<?php include '../includes/footer.php'; ?>