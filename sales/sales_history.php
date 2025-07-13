<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2>Sales History</h2>

<div class="card">
    <div class="card-header">
        <h3>All Sales</h3>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Sale <?php echo $_GET['success']; ?>!</div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
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
                    
                    while ($row = $stmt->fetch_assoc()):
                        $unit_price = $row['total_price'] / $row['quantity'];
                    ?>
                    <tr>
                        <td><?php echo $row['sale_id']; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['sale_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>$<?php echo number_format($unit_price, 2); ?></td>
                        <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>