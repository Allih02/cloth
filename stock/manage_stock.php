<?php
// manage_stock.php - Interface for managing stock manually
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'stock_manager.php'; // Include the stock manager class
requireLogin();

$stock_manager = new StockManager();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            try {
                $product_data = [
                    'name' => trim($_POST['name']),
                    'category' => trim($_POST['category']),
                    'size' => trim($_POST['size']),
                    'color' => trim($_POST['color']),
                    'brand' => trim($_POST['brand']),
                    'price' => floatval($_POST['price']),
                    'stock' => intval($_POST['stock'])
                ];
                
                $product_id = $stock_manager->addProduct($product_data);
                $message = "Product added successfully with ID: " . $product_id;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update_stock':
            try {
                $product_id = $_POST['product_id'];
                $new_stock = intval($_POST['new_stock']);
                
                if ($stock_manager->updateStock($product_id, $new_stock)) {
                    $message = "Stock updated successfully for product ID: " . $product_id;
                } else {
                    $error = "Failed to update stock. Product not found.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Get current stock
$all_products = $stock_manager->getAllStock();
$categories = array_unique(array_column($all_products, 'category'));
$brands = array_unique(array_column($all_products, 'brand'));

// Filter products based on search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$filtered_products = $stock_manager->searchProducts($search, $category_filter);
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-boxes"></i> Stock Management</h2>

<!-- Quick Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <h4><i class="fas fa-tshirt"></i> Total Products</h4>
        <p><?php echo number_format(count($all_products)); ?></p>
    </div>
    <div class="stat-card">
        <h4><i class="fas fa-check-circle"></i> In Stock</h4>
        <p><?php echo number_format(count(array_filter($all_products, fn($p) => $p['stock'] > 0))); ?></p>
    </div>
    <div class="stat-card">
        <h4><i class="fas fa-exclamation-triangle"></i> Low Stock</h4>
        <p><?php echo number_format(count(array_filter($all_products, fn($p) => $p['stock'] > 0 && $p['stock'] <= 10))); ?></p>
    </div>
    <div class="stat-card">
        <h4><i class="fas fa-times-circle"></i> Out of Stock</h4>
        <p><?php echo number_format(count(array_filter($all_products, fn($p) => $p['stock'] == 0))); ?></p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Add New Product -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-plus"></i> Add New Product</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_product">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required 
                           placeholder="e.g., Manchester United Home Jersey 2024">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Jersey">Jersey</option>
                        <option value="Cap">Cap</option>
                        <option value="Shorts">Shorts</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="size">Size *</label>
                    <select id="size" name="size" class="form-control" required>
                        <option value="">Select Size</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="XXXL">XXXL</option>
                        <option value="One Size">One Size</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="color">Color *</label>
                    <input type="text" id="color" name="color" class="form-control" required 
                           placeholder="e.g., Red, Blue, White">
                </div>
                
                <div class="form-group">
                    <label for="brand">Brand *</label>
                    <select id="brand" name="brand" class="form-control" required>
                        <option value="">Select Brand</option>
                        <option value="Nike">Nike</option>
                        <option value="Adidas">Adidas</option>
                        <option value="Puma">Puma</option>
                        <option value="Umbro">Umbro</option>
                        <option value="New Balance">New Balance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (TZS) *</label>
                    <input type="number" id="price" name="price" class="form-control" required min="0" 
                           placeholder="e.g., 85000">
                </div>
                
                <div class="form-group">
                    <label for="stock">Initial Stock *</label>
                    <input type="number" id="stock" name="stock" class="form-control" required min="0" 
                           placeholder="e.g., 25">
                </div>
                
                <div class="form-group" style="display: flex; align-items: end;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filter and Search -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-search"></i> Search & Filter Products</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label for="search">Search Products</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by product name...">
                </div>
                
                <div class="form-group">
                    <label for="category_filter">Category</label>
                    <select id="category_filter" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="manage_stock.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Current Stock Display -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Current Stock 
            <span class="badge badge-info"><?php echo count($filtered_products); ?> products</span>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="stock-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_products)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 2rem; color: #666;">
                                <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No products found. Try adjusting your search criteria or add new products.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filtered_products as $product): 
                            $stock_status = '';
                            $stock_class = '';
                            if ($product['stock'] > 10) {
                                $stock_status = 'In Stock';
                                $stock_class = 'badge-success';
                            } elseif ($product['stock'] > 0) {
                                $stock_status = 'Low Stock';
                                $stock_class = 'badge-warning';
                            } else {
                                $stock_status = 'Out of Stock';
                                $stock_class = 'badge-danger';
                            }
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($product['id']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($product['category']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($product['size']); ?></td>
                            <td><?php echo htmlspecialchars($product['color']); ?></td>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                            <td><strong><?php echo number_format($product['price']); ?> TZS</strong></td>
                            <td>
                                <span class="stock-quantity" style="font-size: 1.1rem; font-weight: bold;">
                                    <?php echo number_format($product['stock']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $stock_class; ?>"><?php echo $stock_status; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary update-stock-btn" 
                                        data-id="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-current="<?php echo $product['stock']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Stock Update Modal -->
<div id="stock-update-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; min-width: 400px;">
        <h4><i class="fas fa-edit"></i> Update Stock</h4>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="product_id" id="modal-product-id">
            
            <div class="form-group">
                <label>Product</label>
                <p id="modal-product-name" style="font-weight: bold; margin: 0.5rem 0;"></p>
            </div>
            
            <div class="form-group">
                <label for="modal-current-stock">Current Stock</label>
                <input type="text" id="modal-current-stock" class="form-control" readonly>
            </div>
            
            <div class="form-group">
                <label for="new_stock">New Stock Quantity *</label>
                <input type="number" id="new_stock" name="new_stock" class="form-control" required min="0">
                <small class="form-text text-muted">Enter the new total stock quantity</small>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Stock
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Import/Export Section -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exchange-alt"></i> Import/Export Stock Data</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h5><i class="fas fa-download"></i> Export Current Stock</h5>
                <p>Download your current stock data as a JSON file for backup or external use.</p>
                <a href="export_stock.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Export Stock Data
                </a>
            </div>
            
            <div>
                <h5><i class="fas fa-upload"></i> Bulk Stock Update</h5>
                <p>Upload a CSV file to update multiple product stock levels at once.</p>
                <form action="import_stock.php" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                    <div class="form-group">
                        <input type="file" name="stock_file" class="form-control" accept=".csv" required>
                        <small class="form-text text-muted">CSV format: product_id,new_stock</small>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-upload"></i> Import Updates
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle update stock buttons
    document.querySelectorAll('.update-stock-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const currentStock = this.getAttribute('data-current');
            const productName = this.getAttribute('data-name');
            
            document.getElementById('modal-product-id').value = productId;
            document.getElementById('modal-product-name').textContent = productName;
            document.getElementById('modal-current-stock').value = new Intl.NumberFormat('en-US').format(currentStock);
            document.getElementById('new_stock').value = currentStock;
            
            document.getElementById('stock-update-modal').style.display = 'block';
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('stock-update-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Format price inputs
    document.getElementById('price').addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value && value >= 1000) {
            this.setAttribute('title', new Intl.NumberFormat('en-US').format(value) + ' TZS');
        }
    });
    
    // Auto-focus on new stock input when modal opens
    const modal = document.getElementById('stock-update-modal');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                if (modal.style.display === 'block') {
                    setTimeout(() => {
                        document.getElementById('new_stock').focus();
                        document.getElementById('new_stock').select();
                    }, 100);
                }
            }
        });
    });
    observer.observe(modal, { attributes: true });
});

function closeModal() {
    document.getElementById('stock-update-modal').style.display = 'none';
}

// ESC key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<style>
.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    color: #666;
}

.stat-card p {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
}

.badge {
    display: inline-block;
    padding: 0.25em 0.6em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.badge-primary { color: #fff; background-color: #007bff; }
.badge-success { color: #fff; background-color: #28a745; }
.badge-warning { color: #212529; background-color: #ffc107; }
.badge-danger { color: #fff; background-color: #dc3545; }
.badge-info { color: #fff; background-color: #17a2b8; }

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.stock-quantity {
    color: #495057;
}

code {
    padding: 0.2rem 0.4rem;
    font-size: 0.875em;
    color: #e83e8c;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: 2fr 1fr auto"] {
        grid-template-columns: 1fr !important;
    }
}

#stock-update-modal form {
    margin: 0;
}

.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}
</style>

<?php include '../includes/footer.php'; ?>