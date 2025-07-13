<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2>Reports</h2>

<div class="stats-container">
    <div class="stat-card primary">
        <h3>Total Products</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM products");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
    
    <div class="stat-card success">
        <h3>Total Sales</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM sales");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
    
    <div class="stat-card warning">
        <h3>Total Revenue</h3>
        <p>
            $<?php
            $stmt = $conn->query("SELECT SUM(total_price) FROM sales");
            echo number_format($stmt->fetch_row()[0] ?? 0, 2);
            ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Stock Summary</h3>
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
                        GROUP BY c.name
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['total_items']; ?></td>
                        <td><?php echo $row['total_stock'] ?? 0; ?></td>
                        <td><?php echo $row['low_stock_items']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Sales by Category</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Sales</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT c.name, 
                               COUNT(s.sale_id) AS total_sales,
                               SUM(s.total_price) AS total_revenue
                        FROM categories c
                        LEFT JOIN products p ON c.category_id = p.category_id
                        LEFT JOIN sales s ON p.product_id = s.product_id
                        GROUP BY c.name
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['total_sales'] ?? 0; ?></td>
                        <td>$<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>