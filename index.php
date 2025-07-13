<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();
?>

<?php include 'includes/header.php'; ?>

<h2>Dashboard</h2>

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
        <h3>Total Sales Today</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
    
    <div class="stat-card warning">
        <h3>Low Stock Items</h3>
        <p>
            <?php
            $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10");
            echo $stmt->fetch_row()[0];
            ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Sales</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
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
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['sale_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>