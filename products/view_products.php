<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2>Product Management</h2>

<div class="card">
    <div class="card-header">
        <h3>All Products</h3>
        <a href="add_product.php" class="btn btn-success">Add New Product</a>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Product <?php echo $_GET['success']; ?> successfully!</div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Supplier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT p.*, c.name AS category_name, s.name AS supplier_name 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.category_id 
                        JOIN suppliers s ON p.supplier_id = s.supplier_id 
                        ORDER BY p.product_id DESC
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td><?php echo htmlspecialchars($row['color']); ?></td>
                        <td><?php echo htmlspecialchars($row['brand']); ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['stock_quantity']; ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>