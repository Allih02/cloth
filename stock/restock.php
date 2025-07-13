<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Get current stock
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($current_stock);
    $stmt->fetch();
    $stmt->close();
    
    // Update stock
    $new_stock = $current_stock + $quantity;
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
    $stmt->bind_param("ii", $new_stock, $product_id);
    
    if ($stmt->execute()) {
        header("Location: view_stock.php?success=restocked");
        exit();
    } else {
        $error = "Error restocking product: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get products for dropdown
$products = $conn->query("SELECT product_id, name FROM products ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2>Restock Products</h2>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" class="form-control" required>
                    <option value="">Select Product</option>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <option value="<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantity">Quantity to Add</label>
                <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Restock</button>
            <a href="view_stock.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>