<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$product_id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $size = trim($_POST['size']);
    $color = trim($_POST['color']);
    $brand = trim($_POST['brand']);
    $price = floatval($_POST['price']);
    $supplier_id = intval($_POST['supplier_id']);
    
    $stmt = $conn->prepare("
        UPDATE products 
        SET name = ?, category_id = ?, size = ?, color = ?, brand = ?, price = ?, supplier_id = ? 
        WHERE product_id = ?
    ");
    $stmt->bind_param("sisssdii", $name, $category_id, $size, $color, $brand, $price, $supplier_id, $product_id);
    
    if ($stmt->execute()) {
        header("Location: view_products.php?success=updated");
        exit();
    } else {
        $error = "Error updating product: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: view_products.php");
    exit();
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers");
?>

<?php include '../includes/header.php'; ?>

<h2>Edit Product</h2>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="size">Size</label>
                <input type="text" id="size" name="size" class="form-control" value="<?php echo htmlspecialchars($product['size']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="color">Color</label>
                <input type="text" id="color" name="color" class="form-control" value="<?php echo htmlspecialchars($product['color']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="brand">Brand</label>
                <input type="text" id="brand" name="brand" class="form-control" value="<?php echo htmlspecialchars($product['brand']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" class="form-control" value="<?php echo $product['price']; ?>" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="form-control" required>
                    <option value="">Select Supplier</option>
                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo $supplier['supplier_id'] == $product['supplier_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="view_products.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>