<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-tshirt"></i> Product Management</h2>

<div class="card">
    <div class="card-header">
        <h3>All Products</h3>
        <?php if (isAdmin()): ?>
            <a href="add_product.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Product <?php echo htmlspecialchars($_GET['success']); ?> successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Search and Filter -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="text" id="searchInput" placeholder="Search products..." class="form-control" style="max-width: 300px;">
            <select id="categoryFilter" class="form-control" style="max-width: 200px;">
                <option value="">All Categories</option>
                <?php
                $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                while ($category = $categories->fetch_assoc()):
                ?>
                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
            <select id="stockFilter" class="form-control" style="max-width: 200px;">
                <option value="">All Stock Levels</option>
                <option value="in-stock">In Stock (>20)</option>
                <option value="low-stock">Low Stock (1-20)</option>
                <option value="out-of-stock">Out of Stock (0)</option>
            </select>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="productsTable">
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
                        <?php if (isAdmin()): ?>
                            <th>Actions</th>
                        <?php endif; ?>
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
                        $stockClass = '';
                        if ($row['stock_quantity'] > 20) {
                            $stockClass = 'text-success';
                        } elseif ($row['stock_quantity'] > 0) {
                            $stockClass = 'text-warning';
                        } else {
                            $stockClass = 'text-danger';
                        }
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($row['category_name']); ?>" 
                        data-stock="<?php echo $row['stock_quantity']; ?>">
                        <td><?php echo $row['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td>
                            <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo strtolower($row['color']); ?>; border: 1px solid #ddd; border-radius: 3px; margin-right: 5px; vertical-align: middle;"></span>
                            <?php echo htmlspecialchars($row['color']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['brand']); ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td class="<?php echo $stockClass; ?>">
                            <strong><?php echo $row['stock_quantity']; ?></strong>
                            <?php if ($row['stock_quantity'] == 0): ?>
                                <i class="fas fa-exclamation-triangle" title="Out of stock"></i>
                            <?php elseif ($row['stock_quantity'] < 10): ?>
                                <i class="fas fa-exclamation-circle" title="Low stock"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <?php if (isAdmin()): ?>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" 
                                   class="btn btn-primary btn-sm" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" 
                                   class="btn btn-danger btn-sm btn-delete" title="Delete Product">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter = document.getElementById('stockFilter');
    const tableRows = document.querySelectorAll('#productsTable tbody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        const selectedStock = stockFilter.value;

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const category = row.getAttribute('data-category');
            const stock = parseInt(row.getAttribute('data-stock'));
            
            let showRow = true;

            // Search filter
            if (searchTerm && !text.includes(searchTerm)) {
                showRow = false;
            }

            // Category filter
            if (selectedCategory && category !== selectedCategory) {
                showRow = false;
            }

            // Stock filter
            if (selectedStock) {
                if (selectedStock === 'in-stock' && stock <= 20) showRow = false;
                if (selectedStock === 'low-stock' && (stock === 0 || stock > 20)) showRow = false;
                if (selectedStock === 'out-of-stock' && stock !== 0) showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });

        // Update table info
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none').length;
        updateTableInfo(visibleRows, tableRows.length);
    }

    function updateTableInfo(visible, total) {
        let infoElement = document.getElementById('tableInfo');
        if (!infoElement) {
            infoElement = document.createElement('div');
            infoElement.id = 'tableInfo';
            infoElement.style.marginTop = '1rem';
            infoElement.style.fontStyle = 'italic';
            infoElement.style.color = '#666';
            document.querySelector('.table-responsive').appendChild(infoElement);
        }
        infoElement.textContent = `Showing ${visible} of ${total} products`;
    }

    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    stockFilter.addEventListener('change', filterTable);

    // Initialize table info
    updateTableInfo(tableRows.length, tableRows.length);
});
</script>

<?php include '../includes/footer.php'; ?>