<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get product details
        $stmt = $conn->prepare("SELECT price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception("Not enough stock available");
        }
        
        // Calculate total price
        $total_price = $product['price'] * $quantity;
        
        // Record sale
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $product_id, $quantity, $total_price);
        $stmt->execute();
        $stmt->close();
        
        // Update stock
        $new_stock = $product['stock_quantity'] - $quantity;
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        header("Location: sales_history.php?success=sale recorded");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Get products with stock for dropdown
$products = $conn->query("SELECT product_id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2>Make a Sale</h2>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form id="sales-form" method="POST" action="">
            <div class="form-group">
                <label for="product">Product</label>
                <select id="product" name="product_id" class="form-control" required>
                    <option value="">Select Product</option>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['price']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Unit Price</label>
                <p id="price-display">$0.00</p>
            </div>
            
            <div class="form-group">
                <label>Total Price</label>
                <p id="total-display">$0.00</p>
            </div>
            
            <button type="submit" class="btn btn-primary">Complete Sale</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>