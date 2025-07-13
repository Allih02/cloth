<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2>Stock Management</h2>

<div class="card">
    <div class="card-header">
        <h3>Current Stock Levels</h3>
        <a href="restock.php" class="btn btn-success">Restock Products</a>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Stock <?php echo $_GET['success']; ?> successfully!</div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT p.product_id, p.name, p.size, p.color, p.stock_quantity, c.name AS category_name 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.stock_quantity ASC
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                        $status = '';
                        $class = '';
                        
                        if ($row['stock_quantity'] > 20) {
                            $status = 'In Stock';
                            $class = 'text-success';
                        } elseif ($row['stock_quantity'] > 0) {
                            $status = 'Low Stock';
                            $class = 'text-warning';
                        } else {
                            $status = 'Out of Stock';
                            $class = 'text-danger';
                        }
                    ?>
                    <tr>
                        <td><?php echo $row['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td><?php echo htmlspecialchars($row['color']); ?></td>
                        <td><?php echo $row['stock_quantity']; ?></td>
                        <td class="<?php echo $class; ?>"><?php echo $status; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>