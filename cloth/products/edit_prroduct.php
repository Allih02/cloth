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
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Product name is required";
    if ($category_id <= 0) $errors[] = "Please select a category";
    if (empty($size)) $errors[] = "Size is required";
    if (empty($color)) $errors[] = "Color is required";
    if (empty($brand)) $errors[] = "Brand is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if ($supplier_id <= 0) $errors[] = "Please select a supplier";
    
    if (empty($errors)) {
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
            $errors[] = "Error updating product: " . $stmt->error;
        }
        
        $stmt->close();
    }
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
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-edit"></i> Edit Product</h2>

<div class="card">
    <div class="card-header">
        <h3>Update Product Information</h3>
        <a href="view_products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="productForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($product['name']); ?>"
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-list"></i> Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="size"><i class="fas fa-ruler"></i> Size *</label>
                        <input type="text" id="size" name="size" class="form-control" required
                               value="<?php echo htmlspecialchars($product['size']); ?>"
                               placeholder="e.g., S, M, L, XL, 32, 10">
                    </div>
                    
                    <div class="form-group">
                        <label for="color"><i class="fas fa-palette"></i> Color *</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="color" name="color" class="form-control" required
                                   value="<?php echo htmlspecialchars($product['color']); ?>"
                                   placeholder="Enter color name">
                            <input type="color" id="colorPicker" style="width: 50px; height: 40px; border: none; border-radius: 5px;">
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label for="brand"><i class="fas fa-certificate"></i> Brand *</label>
                        <input type="text" id="brand" name="brand" class="form-control" required
                               value="<?php echo htmlspecialchars($product['brand']); ?>"
                               placeholder="Enter brand name">
                    </div>
                    
                    <div class="form-group">
                        <label for="price"><i class="fas fa-dollar-sign"></i> Price *</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required
                               value="<?php echo $product['price']; ?>"
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="supplier_id"><i class="fas fa-truck"></i> Supplier *</label>
                        <select id="supplier_id" name="supplier_id" class="form-control" required>
                            <option value="">Select Supplier</option>
                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>"
                                        <?php echo $supplier['supplier_id'] == $product['supplier_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Current Stock</label>
                        <div style="padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <strong><?php echo $product['stock_quantity']; ?> units</strong>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                                <i class="fas fa-info-circle"></i> Stock can be updated in the Stock Management section
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <a href="view_products.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="../stock/restock.php" class="btn btn-warning">
                    <i class="fas fa-boxes"></i> Manage Stock
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('color');
    const colorPicker = document.getElementById('colorPicker');
    const form = document.getElementById('productForm');
    
    // Initialize color picker with current color
    const currentColor = colorInput.value.toLowerCase();
    const hex = colorNameToHex(currentColor);
    if (hex) {
        colorPicker.value = hex;
    }
    
    // Color picker functionality
    colorPicker.addEventListener('change', function() {
        const hex = this.value;
        const colorName = hexToColorName(hex);
        colorInput.value = colorName;
    });
    
    colorInput.addEventListener('input', function() {
        const colorName = this.value.toLowerCase();
        const hex = colorNameToHex(colorName);
        if (hex) {
            colorPicker.value = hex;
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const price = parseFloat(document.getElementById('price').value);
        
        if (price <= 0) {
            e.preventDefault();
            showNotification('Price must be greater than 0', 'danger');
            return;
        }
        
        // Confirm update
        if (!confirm('Are you sure you want to update this product?')) {
            e.preventDefault();
        }
    });
    
    // Real-time price formatting
    document.getElementById('price').addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.setAttribute('title', `Price: $${value.toFixed(2)}`);
        }
    });
    
    function hexToColorName(hex) {
        const colors = {
            '#FF0000': 'Red',
            '#00FF00': 'Green',
            '#0000FF': 'Blue',
            '#FFFF00': 'Yellow',
            '#FF00FF': 'Magenta',
            '#00FFFF': 'Cyan',
            '#000000': 'Black',
            '#FFFFFF': 'White',
            '#808080': 'Gray',
            '#800000': 'Maroon',
            '#008000': 'Dark Green',
            '#000080': 'Navy',
            '#808000': 'Olive',
            '#800080': 'Purple',
            '#008080': 'Teal',
            '#C0C0C0': 'Silver',
            '#FFA500': 'Orange',
            '#A52A2A': 'Brown',
            '#FFC0CB': 'Pink'
        };
        
        return colors[hex.toUpperCase()] || hex;
    }
    
    function colorNameToHex(colorName) {
        const colors = {
            'red': '#FF0000',
            'green': '#00FF00',
            'blue': '#0000FF',
            'yellow': '#FFFF00',
            'magenta': '#FF00FF',
            'cyan': '#00FFFF',
            'black': '#000000',
            'white': '#FFFFFF',
            'gray': '#808080',
            'grey': '#808080',
            'maroon': '#800000',
            'dark green': '#008000',
            'navy': '#000080',
            'olive': '#808000',
            'purple': '#800080',
            'teal': '#008080',
            'silver': '#C0C0C0',
            'orange': '#FFA500',
            'brown': '#A52A2A',
            'pink': '#FFC0CB'
        };
        
        return colors[colorName.toLowerCase()];
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

.form-group input:focus,
.form-group select:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

#colorPicker {
    cursor: pointer;
    transition: transform 0.2s ease;
}

#colorPicker:hover {
    transform: scale(1.1);
}
</style>

<?php include '../includes/footer.php'; ?>