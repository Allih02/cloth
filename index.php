<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();
?>

<?php include 'includes/header.php'; ?>

<style>
:root {
    --primary-color: #2563eb;
    --primary-dark: #1d4ed8;
    --secondary-color: #64748b;
    --success-color: #059669;
    --warning-color: #d97706;
    --danger-color: #dc2626;
    --background-light: #f8fafc;
    --background-white: #ffffff;
    --border-color: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --border-radius: 12px;
    --border-radius-sm: 8px;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

.dashboard-container {
    max-width: 100vw;
    height: 100vh;
    padding: 1rem;
    background-color: var(--background-light);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Hero Section with Make Sale Button */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 1rem;
    color: white;
    position: relative;
    overflow: hidden;
    min-height: 140px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="0.5" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="0.3" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="0.4" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.1;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.hero-subtitle {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
}

.make-sale-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 1.5rem 3rem;
    border-radius: 50px;
    font-size: 1.25rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.make-sale-btn:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 20px 40px rgba(16, 185, 129, 0.6);
    background: linear-gradient(135deg, #059669, #047857);
}

.make-sale-btn i {
    font-size: 1.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Compact Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: var(--background-white);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-height: 120px;
}

.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent-color), var(--accent-light));
}

.stat-card.primary {
    --accent-color: var(--primary-color);
    --accent-light: #3b82f6;
}

.stat-card.success {
    --accent-color: var(--success-color);
    --accent-light: #10b981;
}

.stat-card.warning {
    --accent-color: var(--warning-color);
    --accent-light: #f59e0b;
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-card.primary .stat-icon {
    background: linear-gradient(135deg, var(--primary-color), #3b82f6);
}

.stat-card.success .stat-icon {
    background: linear-gradient(135deg, var(--success-color), #10b981);
}

.stat-card.warning .stat-icon {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
}

.stat-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    line-height: 1;
}

/* Compact Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    flex: 1;
    min-height: 0;
}

.card {
    background: var(--background-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    flex-shrink: 0;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-title i {
    color: var(--primary-color);
}

.card-body {
    padding: 0;
    flex: 1;
    min-height: 0;
    overflow: auto;
}

.table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.table th {
    background: #f8fafc;
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border-color);
}

.table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: var(--text-primary);
}

.table tr:hover {
    background-color: #f8fafc;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 0.75rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), #3b82f6);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
    color: white;
}

.btn-secondary {
    background: linear-gradient(135deg, var(--secondary-color), #6b7280);
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

.status-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.status-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Quick Actions Compact */
.quick-actions-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
}

.action-btn {
    background: linear-gradient(135deg, var(--primary-color), #3b82f6);
    color: white;
    padding: 1rem;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    min-height: 80px;
    justify-content: center;
}

.action-btn.warning {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
}

.action-btn.secondary {
    background: linear-gradient(135deg, var(--secondary-color), #6b7280);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.action-btn i {
    font-size: 1.25rem;
}

@media (max-width: 1400px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .content-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 1024px) {
    .hero-section {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
        padding: 1.5rem;
    }
    
    .hero-title {
        font-size: 2rem;
        justify-content: center;
    }
    
    .make-sale-btn {
        padding: 1.25rem 2.5rem;
        font-size: 1.1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 0.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 1rem;
        min-height: 100px;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .content-grid {
        gap: 0.5rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
    }
    
    .quick-actions-container {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="dashboard-container">
    <!-- Hero Section with Prominent Make Sale Button -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard Overview
            </h1>
            <p class="hero-subtitle">Monitor your business performance and take quick actions</p>
        </div>
        <a href="sales/make_sale.php" class="make-sale-btn">
            <i class="fas fa-shopping-cart"></i>
            Make Sale
        </a>
    </div>

    <!-- Compact Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div>
                    <h3 class="stat-title">Total Products</h3>
                </div>
            </div>
            <p class="stat-value">
                <?php
                $stmt = $conn->query("SELECT COUNT(*) FROM products");
                echo number_format($stmt->fetch_row()[0]);
                ?>
            </p>
        </div>
        
        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h3 class="stat-title">Sales Today</h3>
                </div>
            </div>
            <p class="stat-value">
                <?php
                $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()");
                echo number_format($stmt->fetch_row()[0]);
                ?>
            </p>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h3 class="stat-title">Low Stock Items</h3>
                </div>
            </div>
            <p class="stat-value">
                <?php
                $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10");
                echo number_format($stmt->fetch_row()[0]);
                ?>
            </p>
        </div>
        
        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <h3 class="stat-title">Today's Revenue</h3>
                </div>
            </div>
            <p class="stat-value">
                TZS <?php
                $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE()");
                echo number_format($stmt->fetch_row()[0] ?? 0, 0);
                ?>
            </p>
        </div>
    </div>

    <!-- Compact Content Grid -->
    <div class="content-grid">
        <!-- Recent Sales -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    Recent Sales
                </h3>
                <a href="sales/sales_history.php" class="btn btn-primary">View All</a>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT s.*, p.name 
                            FROM sales s 
                            JOIN products p ON s.product_id = p.product_id 
                            ORDER BY s.sale_date DESC 
                            LIMIT 5
                        ");
                        
                        if ($stmt->num_rows > 0):
                            while ($row = $stmt->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><strong><?php echo number_format($row['quantity']); ?></strong></td>
                            <td><strong>TZS <?php echo number_format($row['total_price'], 0); ?></strong></td>
                            <td><?php echo date('M j, g:i A', strtotime($row['sale_date'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <div>No sales recorded yet</div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Low Stock Alert
                </h3>
                <a href="stock/view_stock.php" class="btn btn-warning">Manage Stock</a>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT name, stock_quantity 
                            FROM products 
                            WHERE stock_quantity < 10 
                            ORDER BY stock_quantity ASC 
                            LIMIT 5
                        ");
                        
                        if ($stmt->num_rows > 0):
                            while ($row = $stmt->fetch_assoc()):
                                $isOutOfStock = $row['stock_quantity'] == 0;
                                $statusText = $isOutOfStock ? 'Out of Stock' : 'Low Stock';
                                $statusClass = $isOutOfStock ? 'status-danger' : 'status-warning';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><strong><?php echo number_format($row['stock_quantity']); ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <div style="color: var(--success-color);">All products have sufficient stock</div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="quick-actions-container">
                    <?php if (isAdmin()): ?>
                    <a href="products/add_product.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                    
                    <a href="stock/restock.php" class="action-btn warning">
                        <i class="fas fa-boxes"></i>
                        Restock
                    </a>
                    
                    <a href="suppliers/add_supplier.php" class="action-btn secondary">
                        <i class="fas fa-truck"></i>
                        Add Supplier
                    </a>
                    
                    <a href="reports/dashboard.php" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                    <?php else: ?>
                    <a href="products/view_products.php" class="action-btn">
                        <i class="fas fa-eye"></i>
                        View Products
                    </a>
                    
                    <a href="sales/sales_history.php" class="action-btn secondary">
                        <i class="fas fa-history"></i>
                        Sales History
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>