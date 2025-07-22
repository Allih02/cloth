<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-history"></i> Sales History</h2>

<div class="card">
    <div class="card-header">
        <h3>All Sales Transactions</h3>
        <a href="make_sale.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Make New Sale
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Sale <?php echo htmlspecialchars($_GET['success']); ?>!
            </div>
        <?php endif; ?>
        
        <!-- Date Filter -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <label for="dateFrom" style="margin-right: 0.5rem;">From:</label>
                <input type="date" id="dateFrom" class="form-control" style="display: inline-block; width: auto;">
            </div>
            <div>
                <label for="dateTo" style="margin-right: 0.5rem;">To:</label>
                <input type="date" id="dateTo" class="form-control" style="display: inline-block; width: auto;">
            </div>
            <button id="filterBtn" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button id="clearBtn" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </button>
            <button id="exportBtn" class="btn btn-warning">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
        
        <!-- Sales Summary -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="text-align: center; padding: 1rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #3498db;">Total Sales</h4>
                <p id="totalSales" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">0</p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #2ecc71;">Total Revenue</h4>
                <p id="totalRevenue" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">$0.00</p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(155, 89, 182, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #9b59b6;">Items Sold</h4>
                <p id="totalItems" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">0</p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(243, 156, 18, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #f39c12;">Average Sale</h4>
                <p id="avgSale" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">$0.00</p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="salesTable">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT s.sale_id, s.sale_date, s.quantity, s.total_price, p.name, p.price 
                        FROM sales s 
                        JOIN products p ON s.product_id = p.product_id 
                        ORDER BY s.sale_date DESC
                    ");
                    
                    $totalSales = 0;
                    $totalRevenue = 0;
                    $totalItems = 0;
                    
                    while ($row = $stmt->fetch_assoc()):
                        $unit_price = $row['total_price'] / $row['quantity'];
                        $totalSales++;
                        $totalRevenue += $row['total_price'];
                        $totalItems += $row['quantity'];
                    ?>
                    <tr data-date="<?php echo date('Y-m-d', strtotime($row['sale_date'])); ?>">
                        <td><?php echo $row['sale_id']; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['sale_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>$<?php echo number_format($unit_price, 2); ?></td>
                        <td class="text-success"><strong>$<?php echo number_format($row['total_price'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($totalSales == 0): ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No sales recorded yet</h3>
                    <p>Start by making your first sale!</p>
                    <a href="make_sale.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Make Sale
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const filterBtn = document.getElementById('filterBtn');
    const clearBtn = document.getElementById('clearBtn');
    const exportBtn = document.getElementById('exportBtn');
    const tableRows = document.querySelectorAll('#salesTable tbody tr');
    
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    dateTo.value = today.toISOString().split('T')[0];
    dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
    
    // Initialize statistics
    updateStatistics();
    
    // Filter functionality
    filterBtn.addEventListener('click', function() {
        filterSales();
    });
    
    clearBtn.addEventListener('click', function() {
        dateFrom.value = '';
        dateTo.value = '';
        showAllRows();
        updateStatistics();
    });
    
    // Export functionality
    exportBtn.addEventListener('click', function() {
        exportToCSV();
    });
    
    function filterSales() {
        const fromDate = dateFrom.value;
        const toDate = dateTo.value;
        
        if (!fromDate && !toDate) {
            showAllRows();
            updateStatistics();
            return;
        }
        
        tableRows.forEach(row => {
            const rowDate = row.getAttribute('data-date');
            let showRow = true;
            
            if (fromDate && rowDate < fromDate) showRow = false;
            if (toDate && rowDate > toDate) showRow = false;
            
            row.style.display = showRow ? '' : 'none';
        });
        
        updateStatistics();
    }
    
    function showAllRows() {
        tableRows.forEach(row => {
            row.style.display = '';
        });
    }
    
    function updateStatistics() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        let totalSales = visibleRows.length;
        let totalRevenue = 0;
        let totalItems = 0;
        
        visibleRows.forEach(row => {
            const cells = row.cells;
            const quantity = parseInt(cells[3].textContent);
            const total = parseFloat(cells[5].textContent.replace('$', ''));
            
            totalItems += quantity;
            totalRevenue += total;
        });
        
        const avgSale = totalSales > 0 ? totalRevenue / totalSales : 0;
        
        document.getElementById('totalSales').textContent = totalSales;
        document.getElementById('totalRevenue').textContent = `$${totalRevenue.toFixed(2)}`;
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('avgSale').textContent = `$${avgSale.toFixed(2)}`;
    }
    
    function exportToCSV() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        let csv = 'Sale ID,Date & Time,Product,Quantity,Unit Price,Total Price\n';
        
        visibleRows.forEach(row => {
            const cells = Array.from(row.cells);
            const rowData = cells.map(cell => {
                let value = cell.textContent.trim();
                // Escape quotes and wrap in quotes if contains comma
                if (value.includes(',') || value.includes('"')) {
                    value = '"' + value.replace(/"/g, '""') + '"';
                }
                return value;
            });
            csv += rowData.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sales_history_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Sales data exported successfully!', 'success');
    }
    
    // Auto-filter when dates change
    dateFrom.addEventListener('change', filterSales);
    dateTo.addEventListener('change', filterSales);
    
    // Initialize with filter
    filterSales();
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
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

#salesTable tbody tr[style*="display: none"] {
    display: none !important;
}
</style>

<?php include '../includes/footer.php'; ?>