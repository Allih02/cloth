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
        <div class="row mb-3">
            <div class="col-12 col-md-4 col-lg-3 mb-2">
                <input type="text" id="searchInput" placeholder="Search products..." class="form-control">
            </div>
            <div class="col-12 col-md-4 col-lg-3 mb-2">
                <select id="categoryFilter" class="form-control">
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
            </div>
            <div class="col-12 col-md-4 col-lg-3 mb-2">
                <select id="stockFilter" class="form-control">
                    <option value="">All Stock Levels</option>
                    <option value="in-stock">In Stock (>20)</option>
                    <option value="low-stock">Low Stock (1-20)</option>
                    <option value="out-of-stock">Out of Stock (0)</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="productsTable">
                <thead>
                    <tr>
                        <th class="d-none d-md-table-cell">ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th class="d-none d-lg-table-cell">Category</th>
                        <th class="d-none d-md-table-cell">Size</th>
                        <th class="d-none d-md-table-cell">Color</th>
                        <th class="d-none d-lg-table-cell">Brand</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="d-none d-lg-table-cell">Supplier</th>
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
                        <td class="d-none d-md-table-cell"><?php echo $row['product_id']; ?></td>
                        <td class="product-image-cell">
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <div class="product-image-container">
                                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                                         class="product-image"
                                         onclick="openImageModal('<?php echo htmlspecialchars($row['image_path']); ?>', '<?php echo htmlspecialchars($row['name']); ?>')">
                                    <div class="image-overlay">
                                        <i class="fas fa-expand-alt"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="product-info">
                                <strong class="product-name"><?php echo htmlspecialchars($row['name']); ?></strong>
                                <div class="d-md-none product-meta-mobile">
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($row['category_name']); ?> • 
                                        <?php echo htmlspecialchars($row['size']); ?> • 
                                        <?php echo htmlspecialchars($row['color']); ?>
                                        <br>
                                        <span class="text-primary"><?php echo htmlspecialchars($row['brand']); ?></span>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($row['size']); ?></td>
                        <td class="d-none d-md-table-cell">
                            <div class="color-display">
                                <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo strtolower($row['color']); ?>; border: 1px solid #ddd; border-radius: 3px; margin-right: 5px; vertical-align: middle;"></span>
                                <?php echo htmlspecialchars($row['color']); ?>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($row['brand']); ?></td>
                        <td><strong>$<?php echo number_format($row['price'], 2); ?></strong></td>
                        <td class="<?php echo $stockClass; ?>">
                            <strong><?php echo number_format($row['stock_quantity']); ?></strong>
                            <?php if ($row['stock_quantity'] == 0): ?>
                                <i class="fas fa-exclamation-triangle" title="Out of stock"></i>
                            <?php elseif ($row['stock_quantity'] < 10): ?>
                                <i class="fas fa-exclamation-circle" title="Low stock"></i>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <?php if (isAdmin()): ?>
                            <td class="actions-cell">
                                <div class="btn-group-vertical d-md-none">
                                    <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-primary btn-sm mb-1" title="Edit Product">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-danger btn-sm btn-delete" title="Delete Product">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                                <div class="btn-group d-none d-md-flex">
                                    <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-primary btn-sm" title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-danger btn-sm btn-delete" title="Delete Product">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" style="display: none;">
    <div class="image-modal-content">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="">
        <div id="modalCaption" class="image-modal-caption"></div>
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

// Image modal functions
function openImageModal(imageSrc, productName) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const caption = document.getElementById('modalCaption');
    
    modal.style.display = 'block';
    modalImg.src = imageSrc;
    caption.textContent = productName;
    
    // Add keyboard event listener
    document.addEventListener('keydown', handleModalKeydown);
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    
    // Remove keyboard event listener
    document.removeEventListener('keydown', handleModalKeydown);
}

function handleModalKeydown(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
}

// Close modal when clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeImageModal();
    }
});
</script>

<style>
/* Product Image Styles */
.product-image-cell {
    width: 80px;
    padding: 0.5rem !important;
}

.product-image-container {
    position: relative;
    width: 60px;
    height: 60px;
    overflow: hidden;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e1e8ed;
}

.product-image-container:hover {
    transform: scale(1.05);
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.3s ease;
}

.product-image-container:hover .product-image {
    transform: scale(1.1);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    color: white;
    font-size: 0.8rem;
}

.product-image-container:hover .image-overlay {
    opacity: 1;
}

.product-image-placeholder {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border: 2px dashed #e1e8ed;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.7rem;
    text-align: center;
}

.product-image-placeholder i {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
    opacity: 0.5;
}

.product-image-placeholder span {
    font-size: 0.6rem;
    line-height: 1;
}

/* Image Modal Styles */
.image-modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.9);
    animation: fadeIn 0.3s ease;
}

.image-modal-content {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 800px;
    position: relative;
    top: 50%;
    transform: translateY(-50%);
    animation: zoomIn 0.3s ease;
}

.image-modal-content img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.image-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: #fff;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(0, 0, 0, 0.5);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-modal-close:hover,
.image-modal-close:focus {
    color: #ff6b6b;
    transform: scale(1.1);
    background: rgba(0, 0, 0, 0.7);
}

.image-modal-caption {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 800px;
    text-align: center;
    color: #ccc;
    padding: 10px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Color Display Enhancement */
.color-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.color-display span {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoomIn {
    from { 
        opacity: 0;
        transform: translateY(-50%) scale(0.7);
    }
    to { 
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}

/* Enhanced Mobile Responsive Styles */
.product-info {
    min-width: 0;
}

.product-name {
    display: block;
    margin-bottom: var(--space-xs);
    font-size: var(--text-base);
    line-height: var(--leading-tight);
}

.product-meta-mobile {
    margin-top: var(--space-xs);
    font-size: var(--text-xs);
    line-height: var(--leading-snug);
}

.actions-cell {
    min-width: 120px;
}

.btn-group-vertical .btn {
    border-radius: var(--radius-sm);
    margin-bottom: var(--space-xs);
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .product-image-container {
        width: 50px;
        height: 50px;
    }
    
    .product-image-placeholder {
        width: 50px;
        height: 50px;
        font-size: 0.6rem;
    }
    
    .product-image-placeholder i {
        font-size: 1rem;
    }
    
    .image-modal-content {
        width: 95%;
        margin: 5% auto;
        transform: none;
        top: auto;
    }
    
    .image-modal-close {
        top: 10px;
        right: 10px;
        font-size: 1.5rem;
        width: 35px;
        height: 35px;
    }
    
    .image-modal-caption {
        width: 95%;
        font-size: 1rem;
    }

    .product-name {
        font-size: var(--text-sm);
    }

    .actions-cell {
        min-width: 100px;
    }

    .btn-group-vertical .btn {
        font-size: var(--text-xs);
        padding: var(--space-xs) var(--space-sm);
    }
}

@media (max-width: 576px) {
    .product-image-container {
        width: 40px;
        height: 40px;
    }
    
    .product-image-placeholder {
        width: 40px;
        height: 40px;
        font-size: 0.5rem;
    }
    
    .product-image-placeholder i {
        font-size: 0.8rem;
    }

    .product-name {
        font-size: var(--text-xs);
    }

    .product-meta-mobile {
        font-size: 0.65rem;
    }

    .actions-cell {
        min-width: 80px;
    }

    .btn-group-vertical .btn {
        font-size: 0.65rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Table adjustments */
.table th:nth-child(2),
.table td:nth-child(2) {
    text-align: center;
    vertical-align: middle;
}

/* Enhanced hover effects for table rows */
.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.table tbody tr:hover .product-image-container {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}
</style>

<?php include '../includes/footer.php'; ?>