<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

// Get stock statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(stock_quantity) as total_units,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock_quantity BETWEEN 1 AND 10 THEN 1 ELSE 0 END) as low_stock,
        SUM(stock_quantity * price) as total_value
    FROM products
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get critical products count
$critical_count = $stats['out_of_stock'] + $stats['low_stock'];
?>

<?php include '../includes/header.php'; ?>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2><i class="fas fa-boxes"></i> Stock Management</h2>

    <!-- Simple Stats -->
    <div class="simple-stats">
        <div class="stat-box">
            <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo number_format($stats['total_units']); ?></div>
            <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">$<?php echo number_format($stats['total_value'], 0); ?></div>
            <div class="stat-label">Stock Value</div>
        </div>
        <div class="stat-box alert">
            <div class="stat-number"><?php echo $critical_count; ?></div>
            <div class="stat-label">Need Attention</div>
        </div>
    </div>

    <!-- Main Actions -->
    <div class="actions-section">
        <h3>What would you like to do?</h3>
        
        <div class="action-grid">
            <!-- View Stock -->
            <a href="view_stock.php" class="action-btn view">
                <i class="fas fa-eye"></i>
                <span>View Stock Levels</span>
                <small>Browse all products</small>
            </a>

            <?php if (isAdmin()): ?>
            <!-- Manage Stock -->
            <a href="manage_stock.php" class="action-btn manage">
                <i class="fas fa-edit"></i>
                <span>Manage Stock</span>
                <small>Edit multiple items</small>
            </a>

            <!-- Quick Adjust -->
            <a href="adjust_stock.php" class="action-btn adjust">
                <i class="fas fa-plus-minus"></i>
                <span>Quick Adjust</span>
                <small>Single item changes</small>
            </a>

            <!-- Restock -->
            <a href="restock.php" class="action-btn restock">
                <i class="fas fa-plus"></i>
                <span>Add Stock</span>
                <small>Restock products</small>
            </a>

            <!-- Import/Export -->
            <a href="import_export.php" class="action-btn import">
                <i class="fas fa-file-csv"></i>
                <span>Import/Export</span>
                <small>Bulk operations</small>
            </a>
            <?php else: ?>
            <!-- Sales for non-admin -->
            <a href="../sales/make_sale.php" class="action-btn sale">
                <i class="fas fa-shopping-cart"></i>
                <span>Make Sale</span>
                <small>Process orders</small>
            </a>
            <?php endif; ?>

            <!-- Reports -->
            <a href="../reports/reports.php" class="action-btn reports">
                <i class="fas fa-chart-bar"></i>
                <span>View Reports</span>
                <small>Analytics & insights</small>
            </a>
        </div>
    </div>

    <!-- Quick Info -->
    <?php if ($critical_count > 0): ?>
    <div class="alert-section">
        <div class="alert-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Attention Required!</strong><br>
                You have <?php echo $critical_count; ?> products that are out of stock or running low.
            </div>
            <?php if (isAdmin()): ?>
            <a href="manage_stock.php" class="alert-btn">Fix Now</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity (Simple) -->
    <div class="recent-section">
        <h3>Recent Stock Changes</h3>
        <?php
        $recent = $conn->query("
            SELECT sm.*, p.name as product_name 
            FROM stock_movements sm 
            JOIN products p ON sm.product_id = p.product_id 
            ORDER BY sm.created_at DESC 
            LIMIT 5
        ");
        ?>
        
        <?php if ($recent->num_rows > 0): ?>
        <div class="recent-list">
            <?php while ($row = $recent->fetch_assoc()): ?>
            <div class="recent-item">
                <div class="recent-icon <?php echo $row['movement_type']; ?>">
                    <i class="fas fa-arrow-<?php echo $row['movement_type'] == 'in' ? 'up' : 'down'; ?>"></i>
                </div>
                <div class="recent-info">
                    <div class="recent-product"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    <div class="recent-details">
                        <?php echo $row['movement_type'] == 'in' ? 'Added' : 'Removed'; ?> 
                        <?php echo $row['quantity']; ?> units
                        <span class="recent-time">
                            - <?php echo date('M j, g:i A', strtotime($row['created_at'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p style="color: #666; text-align: center; padding: 2rem;">No recent stock changes</p>
        <?php endif; ?>
    </div>
</div>

<style>
/* Simple Stock Dashboard Styles */
.simple-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-box {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #3498db;
}

.stat-box.alert {
    border-left-color: #e74c3c;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 0.5rem;
}

.stat-box.alert .stat-number {
    color: #e74c3c;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.actions-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.actions-section h3 {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-size: 1.3rem;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-btn {
    display: block;
    padding: 1.5rem;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    text-align: center;
    transition: all 0.2s ease;
}

.action-btn:hover {
    text-decoration: none;
    color: #333;
    background: #e9ecef;
    border-color: #3498db;
    transform: translateY(-2px);
}

.action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
    color: #3498db;
}

.action-btn span {
    display: block;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.action-btn small {
    color: #666;
    font-size: 0.8rem;
}

.action-btn.view i { color: #17a2b8; }
.action-btn.manage i { color: #6f42c1; }
.action-btn.adjust i { color: #fd7e14; }
.action-btn.restock i { color: #28a745; }
.action-btn.import i { color: #6c757d; }
.action-btn.sale i { color: #20c997; }
.action-btn.reports i { color: #ffc107; }

.alert-section {
    margin-bottom: 2rem;
}

.alert-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-box i {
    font-size: 1.5rem;
    color: #e67e22;
}

.alert-box div {
    flex: 1;
    color: #856404;
}

.alert-btn {
    background: #e67e22;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.alert-btn:hover {
    background: #d35400;
    text-decoration: none;
    color: white;
}

.recent-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.recent-section h3 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.3rem;
}

.recent-list {
    max-height: 300px;
    overflow-y: auto;
}

.recent-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.recent-icon.in {
    background: #d4edda;
    color: #28a745;
}

.recent-icon.out {
    background: #f8d7da;
    color: #dc3545;
}

.recent-info {
    flex: 1;
}

.recent-product {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.recent-details {
    font-size: 0.9rem;
    color: #666;
}

.recent-time {
    color: #999;
    font-size: 0.8rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .simple-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-btn {
        padding: 1rem;
    }
    
    .action-btn i {
        font-size: 1.25rem;
    }
    
    .alert-box {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .simple-stats {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>