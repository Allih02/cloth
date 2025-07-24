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
                <p id="totalRevenue" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">0 TZS</p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(155, 89, 182, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #9b59b6;">Items Sold</h4>
                <p id="totalItems" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">0</p>
            </div>
            
            <div style="text-align: center; padding: 1rem; background: rgba(243, 156, 18, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #f39c12;">Average Sale</h4>
                <p id="avgSale" style="font-size: 1.5rem; font-weight: bold; margin: 0.5rem 0 0 0;">0 TZS</p>
            </div>
        </div>
        
        <div class="sales-container" id="salesContainer">
            <?php
            // Get sales data grouped by date with daily sale numbering
            $stmt = $conn->query("
                SELECT s.sale_id, s.sale_date, s.quantity, s.total_price, s.payment_method,
                       p.name, p.price,
                       DATE(s.sale_date) as sale_date_only,
                       ROW_NUMBER() OVER (PARTITION BY DATE(s.sale_date) ORDER BY s.sale_date ASC) as daily_sale_number
                FROM sales s 
                JOIN products p ON s.product_id = p.product_id 
                ORDER BY s.sale_date DESC
            ");
            
            $salesByDate = [];
            $totalSales = 0;
            $totalRevenue = 0;
            $totalItems = 0;
            
            // Group sales by date
            while ($row = $stmt->fetch_assoc()) {
                $date = $row['sale_date_only'];
                if (!isset($salesByDate[$date])) {
                    $salesByDate[$date] = [
                        'sales' => [],
                        'daily_total' => 0,
                        'daily_items' => 0,
                        'daily_count' => 0
                    ];
                }
                
                $salesByDate[$date]['sales'][] = $row;
                $salesByDate[$date]['daily_total'] += $row['total_price'];
                $salesByDate[$date]['daily_items'] += $row['quantity'];
                $salesByDate[$date]['daily_count']++;
                
                $totalSales++;
                $totalRevenue += $row['total_price'];
                $totalItems += $row['quantity'];
            }
            
            if (empty($salesByDate)): ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No sales recorded yet</h3>
                    <p>Start by making your first sale!</p>
                    <a href="make_sale.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Make Sale
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($salesByDate as $date => $dayData): ?>
                    <div class="day-group" data-date="<?php echo $date; ?>">
                        <!-- Day Header -->
                        <div class="day-header">
                            <div class="day-info">
                                <h3 class="day-title">
                                    <i class="fas fa-calendar-day"></i>
                                    <?php echo date('l, F j, Y', strtotime($date)); ?>
                                </h3>
                                <div class="day-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-shopping-cart"></i>
                                        <?php echo $dayData['daily_count']; ?> sales
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-box"></i>
                                        <?php echo number_format($dayData['daily_items']); ?> items
                                    </span>
                                </div>
                            </div>
                            <div class="day-total">
                                <span class="total-label">Daily Total:</span>
                                <span class="total-amount"><?php echo number_format($dayData['daily_total']); ?> TZS</span>
                            </div>
                        </div>
                        
                        <!-- Day Sales Table -->
                        <div class="day-sales">
                            <div class="table-responsive">
                                <table class="table sales-table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Daily Sale #</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total Price</th>
                                            <th>Payment Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dayData['sales'] as $sale): 
                                            $unit_price = $sale['total_price'] / $sale['quantity'];
                                        ?>
                                        <tr data-date="<?php echo $date; ?>" data-full-date="<?php echo date('Y-m-d', strtotime($sale['sale_date'])); ?>">
                                            <td class="time-cell">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('g:i A', strtotime($sale['sale_date'])); ?>
                                            </td>
                                            <td class="sale-id">
                                                <span class="daily-sale-badge">#<?php echo $sale['daily_sale_number']; ?></span>
                                            </td>
                                            <td class="product-name"><?php echo htmlspecialchars($sale['name']); ?></td>
                                            <td class="quantity">
                                                <span class="qty-badge"><?php echo number_format($sale['quantity']); ?></span>
                                            </td>
                                            <td class="unit-price"><?php echo number_format($unit_price); ?> TZS</td>
                                            <td class="total-price">
                                                <strong class="amount"><?php echo number_format($sale['total_price']); ?> TZS</strong>
                                            </td>
                                            <td class="payment-method">
                                                <?php 
                                                $payment_method = $sale['payment_method'] ?? 'CASH';
                                                $payment_class = strtolower(str_replace(' ', '-', $payment_method));
                                                $icons = [
                                                    'LIPA NUMBER' => 'fa-mobile-alt',
                                                    'CASH' => 'fa-money-bill-wave',
                                                    'CRDB BANK' => 'fa-university',
                                                    'CARD' => 'fa-credit-card'
                                                ];
                                                $icon = $icons[$payment_method] ?? 'fa-credit-card';
                                                ?>
                                                <span class="payment-badge payment-<?php echo $payment_class; ?>">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <span class="payment-text"><?php echo htmlspecialchars($payment_method); ?></span>
                                                </span>
                                            </td>
                                        </tr>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
    const dayGroups = document.querySelectorAll('.day-group');
    
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
        showAllGroups();
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
            showAllGroups();
            updateStatistics();
            return;
        }
        
        dayGroups.forEach(group => {
            const groupDate = group.getAttribute('data-date');
            let showGroup = true;
            
            if (fromDate && groupDate < fromDate) showGroup = false;
            if (toDate && groupDate > toDate) showGroup = false;
            
            group.style.display = showGroup ? 'block' : 'none';
        });
        
        updateStatistics();
    }
    
    function showAllGroups() {
        dayGroups.forEach(group => {
            group.style.display = 'block';
        });
    }
    
    function updateStatistics() {
        const visibleGroups = Array.from(dayGroups).filter(group => group.style.display !== 'none');
        
        let totalSales = 0;
        let totalRevenue = 0;
        let totalItems = 0;
        
        visibleGroups.forEach(group => {
            const rows = group.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const quantity = parseInt(row.cells[3].textContent);
                const totalPriceText = row.cells[5].textContent.replace('TZS', '').replace(/,/g, '').trim();
                const total = parseFloat(totalPriceText);
                
                totalSales++;
                totalItems += quantity;
                totalRevenue += total;
            });
        });
        
        const avgSale = totalSales > 0 ? totalRevenue / totalSales : 0;
        
        document.getElementById('totalSales').textContent = totalSales;
        document.getElementById('totalRevenue').textContent = `${totalRevenue.toLocaleString()} TZS`;
        document.getElementById('totalItems').textContent = totalItems.toLocaleString();
        document.getElementById('avgSale').textContent = `${Math.round(avgSale).toLocaleString()} TZS`;
    }
    
    function exportToCSV() {
        const visibleGroups = Array.from(dayGroups).filter(group => group.style.display !== 'none');
        
        if (visibleGroups.length === 0) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        let csv = 'Date,Time,Daily Sale #,Product,Quantity,Unit Price,Total Price,Payment Method\n';
        
        visibleGroups.forEach(group => {
            const date = group.getAttribute('data-date');
            const rows = group.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = Array.from(row.cells);
                const rowData = [
                    date,
                    cells[0].textContent.trim().replace('🕒', ''),
                    cells[1].textContent.trim(),
                    `"${cells[2].textContent.trim()}"`,
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim(),
                    cells[6].textContent.trim()
                ];
                csv += rowData.join(',') + '\n';
            });
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
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
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
            notification.remove();
        }, 3000);
        
        notification.addEventListener('click', () => notification.remove());
    }
});
</script>

<style>
/* Day Group Styles */
.day-group {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    background: white;
}

.day-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.day-info {
    flex: 1;
}

.day-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.day-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.day-total {
    text-align: right;
}

.total-label {
    display: block;
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.total-amount {
    font-size: 1.8rem;
    font-weight: bold;
    display: block;
}

/* Table Styles */
.day-sales {
    background: white;
}

.sales-table {
    margin: 0;
}

.sales-table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    padding: 1rem;
    border: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sales-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.sales-table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.005);
    transition: all 0.2s ease;
}

/* Cell-specific styles */
.time-cell {
    color: #6c757d;
    font-size: 0.9rem;
    white-space: nowrap;
}

.sale-id {
    text-align: center;
}

.daily-sale-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.product-name {
    font-weight: 500;
    color: #2c3e50;
}

.quantity {
    text-align: center;
}

.qty-badge {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.unit-price, .total-price {
    text-align: right;
    font-family: 'Segoe UI', monospace;
}

.amount {
    color: #27ae60;
    font-size: 1.05rem;
}

/* Payment Method Badges */
.payment-method {
    text-align: center;
}

.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.payment-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.payment-lipa-number {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.payment-cash {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.payment-crdb-bank {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.payment-card {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    border: 1px solid rgba(139, 92, 246, 0.3);
}

.payment-text {
    font-size: 0.7rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .day-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .day-stats {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .day-total {
        text-align: center;
    }
    
    .total-amount {
        font-size: 1.5rem;
    }
    
    .sales-table {
        font-size: 0.85rem;
    }
    
    .sales-table th,
    .sales-table td {
        padding: 0.5rem;
    }
    
    /* Hide some columns on mobile for better fit */
    .sales-table th:nth-child(5),
    .sales-table td:nth-child(5) {
        display: none;
    }
    
    .payment-badge {
        padding: 0.3rem 0.5rem;
        font-size: 0.7rem;
    }
    
    .payment-text {
        display: none;
    }
    
    .daily-sale-badge {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    div[style*="display: flex"] {
        flex-direction: column !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 480px) {
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    
    .day-title {
        font-size: 1.1rem;
    }
    
    .total-amount {
        font-size: 1.3rem;
    }
}

/* Animation for slide effects */
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

/* Alert styles */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid;
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    border-color: #2ecc71;
    color: #27ae60;
}

.alert-warning {
    background: rgba(243, 156, 18, 0.1);
    border-color: #f39c12;
    color: #e67e22;
}

/* Empty state improvements */
.sales-container:empty::after {
    content: '';
    display: block;
    width: 100%;
    height: 200px;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="50" font-size="50" text-anchor="middle" x="50">📊</text></svg>') center center no-repeat;
    background-size: 60px;
    opacity: 0.1;
}
</style>

<?php include '../includes/footer.php'; ?>