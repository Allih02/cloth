<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>

<!-- Summary Statistics -->
<div class="stats-container">
    <div class="stat-card primary">
        <h3><i class="fas fa-tshirt"></i> Total Products</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM products");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
    
    <div class="stat-card success">
        <h3><i class="fas fa-shopping-cart"></i> Total Sales</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM sales");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
    
    <div class="stat-card warning">
        <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
        <p>
            $<?php
            $stmt = $conn->query("SELECT SUM(total_price) FROM sales");
            echo number_format($stmt->fetch_row()[0] ?? 0, 2);
            ?>
        </p>
    </div>
    
    <div class="stat-card danger">
        <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar"></i> Report Filters</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <label for="dateFrom">From Date:</label>
                <input type="date" id="dateFrom" class="form-control" style="display: inline-block; width: auto;">
            </div>
            <div>
                <label for="dateTo">To Date:</label>
                <input type="date" id="dateTo" class="form-control" style="display: inline-block; width: auto;">
            </div>
            <button id="applyFilter" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
            <button id="exportReport" class="btn btn-success">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
    </div>
</div>

<!-- Sales Performance -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Stock Summary -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-boxes"></i> Stock Summary by Category</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Items</th>
                            <th>Total Stock</th>
                            <th>Low Stock Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT c.name, 
                                   COUNT(p.product_id) AS total_items,
                                   SUM(p.stock_quantity) AS total_stock,
                                   SUM(CASE WHEN p.stock_quantity < 10 THEN 1 ELSE 0 END) AS low_stock_items
                            FROM categories c
                            LEFT JOIN products p ON c.category_id = p.category_id
                            GROUP BY c.category_id, c.name
                            ORDER BY total_items DESC
                        ");
                        
                        while ($row = $stmt->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo $row['total_items']; ?></td>
                            <td><?php echo $row['total_stock'] ?? 0; ?></td>
                            <td>
                                <?php if ($row['low_stock_items'] > 0): ?>
                                    <span class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $row['low_stock_items']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fas fa-check"></i> 0
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

    <!-- Sales by Category -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Sales by Category</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Sales</th>
                            <th>Revenue</th>
                            <th>Avg Sale</th>
                        </tr>
                    </thead>
                    <tbody id="salesByCategoryTable">
                        <?php
                        $stmt = $conn->query("
                            SELECT c.name, 
                                   COUNT(s.sale_id) AS total_sales,
                                   SUM(s.total_price) AS total_revenue,
                                   AVG(s.total_price) AS avg_sale
                            FROM categories c
                            LEFT JOIN products p ON c.category_id = p.category_id
                            LEFT JOIN sales s ON p.product_id = s.product_id
                            GROUP BY c.category_id, c.name
                            ORDER BY total_revenue DESC
                        ");
                        
                        while ($row = $stmt->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo $row['total_sales'] ?? 0; ?></td>
                            <td class="text-success">
                                <strong>$<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></strong>
                            </td>
                            <td>$<?php echo number_format($row['avg_sale'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Performing Products -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-trophy"></i> Top Performing Products</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="topProductsTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT p.name, c.name as category_name, p.stock_quantity,
                               COUNT(s.sale_id) as sales_count,
                               SUM(s.quantity) as units_sold,
                               SUM(s.total_price) as revenue
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.category_id
                        LEFT JOIN sales s ON p.product_id = s.product_id
                        GROUP BY p.product_id
                        ORDER BY revenue DESC
                        LIMIT 10
                    ");
                    
                    $rank = 1;
                    while ($row = $stmt->fetch_assoc()):
                        $rankIcon = '';
                        if ($rank == 1) $rankIcon = '<i class="fas fa-crown" style="color: #f1c40f;"></i>';
                        elseif ($rank == 2) $rankIcon = '<i class="fas fa-medal" style="color: #95a5a6;"></i>';
                        elseif ($rank == 3) $rankIcon = '<i class="fas fa-medal" style="color: #cd7f32;"></i>';
                    ?>
                    <tr>
                        <td><?php echo $rankIcon; ?> <?php echo $rank; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['units_sold'] ?? 0; ?></td>
                        <td class="text-success">
                            <strong>$<?php echo number_format($row['revenue'] ?? 0, 2); ?></strong>
                        </td>
                        <td>
                            <?php 
                            $stock = $row['stock_quantity'];
                            $stockClass = $stock > 20 ? 'text-success' : ($stock > 0 ? 'text-warning' : 'text-danger');
                            ?>
                            <span class="<?php echo $stockClass; ?>">
                                <?php echo $stock; ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                        $rank++;
                        endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Recent Sales Activity</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT s.sale_date, s.total_price, p.name
                            FROM sales s
                            JOIN products p ON s.product_id = p.product_id
                            ORDER BY s.sale_date DESC
                            LIMIT 10
                        ");
                        
                        while ($row = $stmt->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('M j, g:i A', strtotime($row['sale_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="text-success">$<?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Stock Alerts</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT name, stock_quantity
                            FROM products
                            WHERE stock_quantity <= 10
                            ORDER BY stock_quantity ASC
                            LIMIT 10
                        ");
                        
                        if ($stmt->num_rows > 0):
                            while ($row = $stmt->fetch_assoc()):
                                $status = $row['stock_quantity'] == 0 ? 'Out of Stock' : 'Low Stock';
                                $class = $row['stock_quantity'] == 0 ? 'text-danger' : 'text-warning';
                                $icon = $row['stock_quantity'] == 0 ? 'fas fa-times-circle' : 'fas fa-exclamation-triangle';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="<?php echo $class; ?>">
                                <strong><?php echo $row['stock_quantity']; ?></strong>
                            </td>
                            <td class="<?php echo $class; ?>">
                                <i class="<?php echo $icon; ?>"></i> <?php echo $status; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #2ecc71;">
                                <i class="fas fa-check-circle"></i> All products have sufficient stock
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Sales Chart -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Monthly Sales Trend</h3>
    </div>
    <div class="card-body">
        <canvas id="salesChart" width="400" height="100"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const applyFilter = document.getElementById('applyFilter');
    const exportReport = document.getElementById('exportReport');
    
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    dateTo.value = today.toISOString().split('T')[0];
    dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
    
    // Apply filter functionality
    applyFilter.addEventListener('click', function() {
        const fromDate = dateFrom.value;
        const toDate = dateTo.value;
        
        if (!fromDate || !toDate) {
            showNotification('Please select both from and to dates', 'warning');
            return;
        }
        
        if (new Date(fromDate) > new Date(toDate)) {
            showNotification('From date cannot be later than to date', 'danger');
            return;
        }
        
        // Show loading
        this.innerHTML = '<span class="loading"></span> Filtering...';
        this.disabled = true;
        
        // Simulate filtering (in real app, this would make an AJAX request)
        setTimeout(() => {
            filterReportData(fromDate, toDate);
            this.innerHTML = '<i class="fas fa-filter"></i> Apply Filter';
            this.disabled = false;
            showNotification('Report filtered successfully', 'success');
        }, 1000);
    });
    
    // Export functionality
    exportReport.addEventListener('click', function() {
        const fromDate = dateFrom.value;
        const toDate = dateTo.value;
        
        exportReportData(fromDate, toDate);
    });
    
    function filterReportData(fromDate, toDate) {
        // This would typically make an AJAX request to filter data
        console.log(`Filtering data from ${fromDate} to ${toDate}`);
        
        // Update statistics based on date range
        updateFilteredStats(fromDate, toDate);
    }
    
    function updateFilteredStats(fromDate, toDate) {
        // Simulate updating statistics
        const statCards = document.querySelectorAll('.stat-card p');
        statCards.forEach(card => {
            card.style.opacity = '0.5';
            setTimeout(() => {
                card.style.opacity = '1';
            }, 500);
        });
    }
    
    function exportReportData(fromDate, toDate) {
        // Create CSV content
        let csv = 'Report Type,Value,Date Range\n';
        csv += `"Sales Report","Generated","${fromDate} to ${toDate}"\n\n`;
        
        // Add stock summary
        csv += 'Stock Summary by Category\n';
        csv += 'Category,Total Items,Total Stock,Low Stock Items\n';
        
        const stockTable = document.querySelector('.card:nth-child(3) .table tbody');
        const stockRows = stockTable.querySelectorAll('tr');
        stockRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                const rowData = Array.from(cells).map(cell => 
                    '"' + cell.textContent.trim().replace(/"/g, '""') + '"'
                ).join(',');
                csv += rowData + '\n';
            }
        });
        
        csv += '\nSales by Category\n';
        csv += 'Category,Total Sales,Revenue,Average Sale\n';
        
        const salesTable = document.querySelector('#salesByCategoryTable');
        const salesRows = salesTable.querySelectorAll('tr');
        salesRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                const rowData = Array.from(cells).map(cell => 
                    '"' + cell.textContent.trim().replace(/"/g, '""') + '"'
                ).join(',');
                csv += rowData + '\n';
            }
        });
        
        csv += '\nTop Performing Products\n';
        csv += 'Rank,Product,Category,Units Sold,Revenue,Current Stock\n';
        
        const topProductsTable = document.querySelector('#topProductsTable tbody');
        const topProductsRows = topProductsTable.querySelectorAll('tr');
        topProductsRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                const rowData = Array.from(cells).map(cell => 
                    '"' + cell.textContent.trim().replace(/"/g, '""') + '"'
                ).join(',');
                csv += rowData + '\n';
            }
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `business_report_${fromDate}_to_${toDate}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Report exported successfully!', 'success');
    }
    
    // Simple chart using Canvas
    function createSalesChart() {
        const canvas = document.getElementById('salesChart');
        const ctx = canvas.getContext('2d');
        
        // Sample data (in real app, this would come from server)
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const salesData = [1200, 1900, 3000, 5000, 2300, 3200];
        
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;
        const margin = 50;
        const chartWidth = canvasWidth - 2 * margin;
        const chartHeight = canvasHeight - 2 * margin;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);
        
        // Draw background
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvasWidth, canvasHeight);
        
        // Find max value for scaling
        const maxValue = Math.max(...salesData);
        
        // Draw grid lines
        ctx.strokeStyle = '#e1e8ed';
        ctx.lineWidth = 1;
        
        for (let i = 0; i <= 5; i++) {
            const y = margin + (i * chartHeight / 5);
            ctx.beginPath();
            ctx.moveTo(margin, y);
            ctx.lineTo(margin + chartWidth, y);
            ctx.stroke();
        }
        
        // Draw chart line
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        salesData.forEach((value, index) => {
            const x = margin + (index * chartWidth / (salesData.length - 1));
            const y = margin + chartHeight - (value / maxValue * chartHeight);
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Draw data points
        ctx.fillStyle = '#667eea';
        salesData.forEach((value, index) => {
            const x = margin + (index * chartWidth / (salesData.length - 1));
            const y = margin + chartHeight - (value / maxValue * chartHeight);
            
            ctx.beginPath();
            ctx.arc(x, y, 5, 0, 2 * Math.PI);
            ctx.fill();
        });
        
        // Draw labels
        ctx.fillStyle = '#333';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        
        months.forEach((month, index) => {
            const x = margin + (index * chartWidth / (months.length - 1));
            const y = canvasHeight - 20;
            ctx.fillText(month, x, y);
        });
        
        // Draw title
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Monthly Sales ($)', canvasWidth / 2, 30);
    }
    
    // Initialize chart
    createSalesChart();
    
    // Auto-refresh data every 5 minutes
    setInterval(function() {
        console.log('Auto-refreshing report data...');
        // In real app, this would refresh the data from server
    }, 300000); // 5 minutes
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="display: flex"] {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .stats-container {
        grid-template-columns: 1fr 1fr !important;
    }
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

#salesChart {
    border: 1px solid #e1e8ed;
    border-radius: 8px;
    background: white;
    max-width: 100%;
    height: auto;
}

.card-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Report filters styling */
div[style*="display: flex"] label {
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: block;
}

div[style*="display: flex"] input[type="date"] {
    margin-top: 0.25rem;
}

/* Enhanced button effects */
#applyFilter:hover, #exportReport:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Rank styling */
.table tbody tr:first-child {
    background: linear-gradient(135deg, rgba(241, 196, 15, 0.1), rgba(241, 196, 15, 0.05));
}

.table tbody tr:nth-child(2) {
    background: linear-gradient(135deg, rgba(149, 165, 166, 0.1), rgba(149, 165, 166, 0.05));
}

.table tbody tr:nth-child(3) {
    background: linear-gradient(135deg, rgba(205, 127, 50, 0.1), rgba(205, 127, 50, 0.05));
}
</style>

<?php include '../includes/footer.php'; ?>