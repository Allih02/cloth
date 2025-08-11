<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-truck"></i> Supplier Management</h2>

<div class="card">
    <div class="card-header">
        <h3>All Suppliers</h3>
        <?php if (isAdmin()): ?>
            <a href="add_supplier.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Supplier
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Supplier <?php echo htmlspecialchars($_GET['success']); ?> successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Search -->
        <div style="margin-bottom: 2rem;">
            <input type="text" id="searchInput" placeholder="Search suppliers..." 
                   class="form-control" style="max-width: 300px;">
        </div>
        
        <div class="table-responsive">
            <table class="table" id="suppliersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Products Supplied</th>
                        <th>Date Added</th>
                        <?php if (isAdmin()): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT s.*, COUNT(p.product_id) as product_count
                        FROM suppliers s 
                        LEFT JOIN products p ON s.supplier_id = p.supplier_id
                        GROUP BY s.supplier_id
                        ORDER BY s.name
                    ");
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['supplier_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        </td>
                        <td>
                            <a href="tel:<?php echo $row['contact']; ?>" class="text-primary">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['contact']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="mailto:<?php echo $row['email']; ?>" class="text-primary">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background: #667eea; color: white; padding: 0.5rem;">
                                <?php echo $row['product_count']; ?> Products
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        <?php if (isAdmin()): ?>
                            <td>
                                <a href="edit_supplier.php?id=<?php echo $row['supplier_id']; ?>" 
                                   class="btn btn-primary btn-sm" title="Edit Supplier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_supplier.php?id=<?php echo $row['supplier_id']; ?>" 
                                   class="btn btn-danger btn-sm btn-delete" title="Delete Supplier">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <a href="supplier_products.php?id=<?php echo $row['supplier_id']; ?>" 
                                   class="btn btn-secondary btn-sm" title="View Products">
                                    <i class="fas fa-eye"></i>
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

<!-- Supplier Statistics -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Supplier Statistics</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <?php
            $supplierStats = $conn->query("
                SELECT 
                    COUNT(DISTINCT s.supplier_id) as total_suppliers,
                    COUNT(p.product_id) as total_products,
                    AVG(product_count.count) as avg_products_per_supplier
                FROM suppliers s
                LEFT JOIN products p ON s.supplier_id = p.supplier_id
                LEFT JOIN (
                    SELECT supplier_id, COUNT(*) as count 
                    FROM products 
                    GROUP BY supplier_id
                ) product_count ON s.supplier_id = product_count.supplier_id
            ")->fetch_assoc();
            ?>
            
            <div style="text-align: center; padding: 1.5rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #3498db;">Total Suppliers</h4>
                <p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $supplierStats['total_suppliers']; ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1.5rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #2ecc71;">Products Supplied</h4>
                <p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo $supplierStats['total_products']; ?>
                </p>
            </div>
            
            <div style="text-align: center; padding: 1.5rem; background: rgba(155, 89, 182, 0.1); border-radius: 8px;">
                <h4 style="margin: 0; color: #9b59b6;">Avg Products/Supplier</h4>
                <p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0 0 0;">
                    <?php echo number_format($supplierStats['avg_products_per_supplier'] ?? 0, 1); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#suppliersTable tbody tr');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const showRow = text.includes(searchTerm);
            
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        updateTableInfo(visibleCount, tableRows.length);
    });

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
        infoElement.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visible} of ${total} suppliers`;
    }

    // Initialize table info
    updateTableInfo(tableRows.length, tableRows.length);

    // Enhanced row hover effects
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            this.style.transition = 'all 0.2s ease';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = 'none';
        });
    });
});
</script>

<style>
.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.btn-sm {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

#searchInput:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}
</style>

<?php include '../includes/footer.php'; ?>