<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$success_message = '';
$errors = [];

// Handle confirm/cancel actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sale_id = intval($_POST['sale_id'] ?? 0);
    
    if ($sale_id <= 0) {
        $errors[] = "Invalid sale ID";
    } else {
        $conn->begin_transaction();
        
        try {
            // Get sale details
            $stmt = $conn->prepare("
                SELECT s.*, p.name as product_name, p.stock_quantity 
                FROM sales s 
                JOIN products p ON s.product_id = p.product_id 
                WHERE s.sale_id = ? AND s.sale_status = 'pending'
            ");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $sale = $result->fetch_assoc();
            $stmt->close();
            
            if (!$sale) {
                throw new Exception("Pending sale not found");
            }
            
            if ($action === 'confirm') {
                // Confirm the sale
                $stmt = $conn->prepare("
                    UPDATE sales 
                    SET sale_status = 'completed', confirmed_at = NOW(), confirmed_by = ? 
                    WHERE sale_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $sale_id);
                $stmt->execute();
                $stmt->close();
                
                // Update winger record
                $stmt = $conn->prepare("
                    UPDATE wingers 
                    SET total_pending_value = total_pending_value - ?, 
                        total_completed_sales = total_completed_sales + ?
                    WHERE name = ? AND contact = ?
                ");
                $stmt->bind_param("ddss", $sale['total_price'], $sale['total_price'], $sale['winger_name'], $sale['winger_contact']);
                $stmt->execute();
                $stmt->close();
                
                // Update stock movement record
                $stmt = $conn->prepare("
                    UPDATE stock_movements 
                    SET reason = 'Confirmed winger sale - " . addslashes($sale['winger_name']) . "' 
                    WHERE sale_id = ?
                ");
                $stmt->bind_param("i", $sale_id);
                $stmt->execute();
                $stmt->close();
                
                $success_message = "Sale confirmed successfully! Product: " . htmlspecialchars($sale['product_name']) . 
                                 ", Amount: " . number_format($sale['total_price']) . " TZS" .
                                 ", Winger: " . htmlspecialchars($sale['winger_name']);
                
            } elseif ($action === 'cancel') {
                // Cancel the sale and restore stock
                $stmt = $conn->prepare("UPDATE sales SET sale_status = 'cancelled' WHERE sale_id = ?");
                $stmt->bind_param("i", $sale_id);
                $stmt->execute();
                $stmt->close();
                
                // Restore stock
                $new_stock = $sale['stock_quantity'] + $sale['quantity'];
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                $stmt->bind_param("ii", $new_stock, $sale['product_id']);
                $stmt->execute();
                $stmt->close();
                
                // Add stock movement for restoration
                $restore_reason = "Sale cancelled - stock restored (Winger: " . $sale['winger_name'] . ")";
                $stmt = $conn->prepare("
                    INSERT INTO stock_movements (product_id, movement_type, quantity, reason, sale_id) 
                    VALUES (?, 'in', ?, ?, ?)
                ");
                $stmt->bind_param("iisi", $sale['product_id'], $sale['quantity'], $restore_reason, $sale_id);
                $stmt->execute();
                $stmt->close();
                
                // Update winger record
                $stmt = $conn->prepare("
                    UPDATE wingers 
                    SET total_pending_value = total_pending_value - ? 
                    WHERE name = ? AND contact = ?
                ");
                $stmt->bind_param("dss", $sale['total_price'], $sale['winger_name'], $sale['winger_contact']);
                $stmt->execute();
                $stmt->close();
                
                $success_message = "Sale cancelled and stock restored! Product: " . htmlspecialchars($sale['product_name']) . 
                                 ", Quantity: " . number_format($sale['quantity']) . " units restored" .
                                 ", Winger: " . htmlspecialchars($sale['winger_name']);
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get all pending sales
$pending_sales = $conn->query("
    SELECT s.*, p.name as product_name, p.price as original_price, 
           DATEDIFF(s.expected_return_date, CURDATE()) as days_remaining,
           DATEDIFF(CURDATE(), s.sale_date) as days_since_sale
    FROM sales s 
    JOIN products p ON s.product_id = p.product_id 
    WHERE s.sale_status = 'pending'
    ORDER BY s.expected_return_date ASC, s.sale_date ASC
");

// Get overdue sales
$overdue_sales = $conn->query("
    SELECT COUNT(*) as count, SUM(total_price) as total_value
    FROM sales 
    WHERE sale_status = 'pending' AND expected_return_date < CURDATE()
")->fetch_assoc();

// Get summary statistics
$pending_stats = $conn->query("
    SELECT 
        COUNT(*) as total_pending,
        SUM(total_price) as total_value,
        COUNT(CASE WHEN expected_return_date < CURDATE() THEN 1 END) as overdue_count,
        COUNT(CASE WHEN expected_return_date >= CURDATE() THEN 1 END) as on_track_count
    FROM sales 
    WHERE sale_status = 'pending'
")->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-clock"></i> Pending Sales Management</h2>

<!-- Summary Statistics -->
<div class="stats-container">
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pending_stats['total_pending']); ?></div>
            <div class="stat-label">Total Pending Sales</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pending_stats['overdue_count']); ?></div>
            <div class="stat-label">Overdue Sales</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pending_stats['on_track_count']); ?></div>
            <div class="stat-label">On Track</div>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pending_stats['total_value']); ?> TZS</div>
            <div class="stat-label">Pending Value</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Pending Sales (Winger Transactions)</h3>
        <div>
            <a href="make_sale.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Sale
            </a>
            <a href="wingers_list.php" class="btn btn-info">
                <i class="fas fa-users"></i> Manage Wingers
            </a>
        </div>
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
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Options -->
        <div class="filter-section">
            <div class="filter-controls">
                <select id="statusFilter" class="form-control">
                    <option value="">All Pending Sales</option>
                    <option value="overdue">Overdue Only</option>
                    <option value="due-today">Due Today</option>
                    <option value="due-soon">Due Within 3 Days</option>
                    <option value="on-track">On Track</option>
                </select>
                
                <input type="text" id="searchInput" placeholder="Search by winger name or product" class="form-control">
                
                <button id="exportBtn" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="pendingSalesTable">
                <thead>
                    <tr>
                        <th>Sale Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Total Value</th>
                        <th>Winger Details</th>
                        <th>Bond Info</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_sales->num_rows > 0): ?>
                        <?php while ($sale = $pending_sales->fetch_assoc()): ?>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            $status_icon = '';
                            
                            if ($sale['days_remaining'] < 0) {
                                $status_class = 'status-overdue';
                                $status_text = 'OVERDUE (' . abs($sale['days_remaining']) . ' days)';
                                $status_icon = 'fas fa-exclamation-triangle';
                            } elseif ($sale['days_remaining'] == 0) {
                                $status_class = 'status-due-today';
                                $status_text = 'DUE TODAY';
                                $status_icon = 'fas fa-clock';
                            } elseif ($sale['days_remaining'] <= 3) {
                                $status_class = 'status-due-soon';
                                $status_text = 'DUE IN ' . $sale['days_remaining'] . ' DAYS';
                                $status_icon = 'fas fa-hourglass-half';
                            } else {
                                $status_class = 'status-on-track';
                                $status_text = $sale['days_remaining'] . ' DAYS LEFT';
                                $status_icon = 'fas fa-check-circle';
                            }
                            ?>
                            <tr data-status="<?php echo $status_class; ?>" 
                                data-winger="<?php echo strtolower($sale['winger_name']); ?>"
                                data-product="<?php echo strtolower($sale['product_name']); ?>">
                                <td>
                                    <div class="sale-date">
                                        <?php echo date('M j, Y', strtotime($sale['sale_date'])); ?>
                                        <small class="text-muted d-block">
                                            <?php echo $sale['days_since_sale']; ?> days ago
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-info">
                                        <strong><?php echo htmlspecialchars($sale['product_name']); ?></strong>
                                        <small class="text-muted d-block">
                                            Unit Price: <?php echo number_format($sale['total_price'] / $sale['quantity']); ?> TZS
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="quantity-badge">
                                        <?php echo number_format($sale['quantity']); ?> units
                                    </span>
                                </td>
                                <td>
                                    <div class="total-value">
                                        <strong><?php echo number_format($sale['total_price']); ?> TZS</strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="winger-details">
                                        <strong><?php echo htmlspecialchars($sale['winger_name']); ?></strong>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($sale['winger_contact']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="bond-info">
                                        <strong><?php echo htmlspecialchars($sale['bond_item']); ?></strong>
                                        <small class="text-muted d-block">
                                            Value: <?php echo number_format($sale['bond_value']); ?> TZS
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="return-date">
                                        <?php echo date('M j, Y', strtotime($sale['expected_return_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm confirm-btn" 
                                                data-sale-id="<?php echo $sale['sale_id']; ?>"
                                                data-product="<?php echo htmlspecialchars($sale['product_name']); ?>"
                                                data-winger="<?php echo htmlspecialchars($sale['winger_name']); ?>"
                                                data-amount="<?php echo number_format($sale['total_price']); ?>">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                        
                                        <button class="btn btn-danger btn-sm cancel-btn" 
                                                data-sale-id="<?php echo $sale['sale_id']; ?>"
                                                data-product="<?php echo htmlspecialchars($sale['product_name']); ?>"
                                                data-winger="<?php echo htmlspecialchars($sale['winger_name']); ?>"
                                                data-quantity="<?php echo $sale['quantity']; ?>">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                        
                                        <button class="btn btn-info btn-sm details-btn" 
                                                data-sale-id="<?php echo $sale['sale_id']; ?>"
                                                data-notes="<?php echo htmlspecialchars($sale['notes'] ?? ''); ?>">
                                            <i class="fas fa-info"></i> Details
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-content">
                                    <i class="fas fa-check-circle"></i>
                                    <h3>No Pending Sales</h3>
                                    <p>All winger sales have been completed or there are no pending transactions.</p>
                                    <a href="make_sale.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create New Sale
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Action Confirmation Modal -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modalTitle"></h4>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
            <div id="modalDetails"></div>
        </div>
        <div class="modal-footer">
            <form id="actionForm" method="POST" style="display: inline;">
                <input type="hidden" name="sale_id" id="modalSaleId">
                <input type="hidden" name="action" id="modalAction">
                <button type="submit" class="btn" id="modalConfirmBtn">Confirm</button>
            </form>
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Sale Details</h4>
            <button class="close-btn" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="detailsContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<style>
/* Stats Container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid;
    transition: transform 0.2s ease;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.primary { border-left-color: #667eea; }
.stat-card.warning { border-left-color: #f39c12; }
.stat-card.success { border-left-color: #2ecc71; }
.stat-card.info { border-left-color: #17a2b8; }

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stat-card.primary .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
.stat-card.success .stat-icon { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.stat-card.info .stat-icon { background: linear-gradient(135deg, #17a2b8, #138496); }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filter Section */
.filter-section {
    margin-bottom: 2rem;
}

.filter-controls {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-controls .form-control {
    max-width: 200px;
}

/* Table Enhancements */
.table {
    font-size: 0.9rem;
}

.sale-date {
    min-width: 100px;
}

.product-info strong {
    color: #333;
}

.quantity-badge {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.total-value strong {
    color: #2ecc71;
    font-size: 1.1rem;
}

.winger-details strong {
    color: #333;
}

.bond-info strong {
    color: #e67e22;
}

.return-date {
    font-weight: 600;
    color: #333;
}

/* Status Badges */
.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.status-overdue {
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
    animation: pulse 2s infinite;
}

.status-due-today {
    background: rgba(243, 156, 18, 0.15);
    color: #f39c12;
}

.status-due-soon {
    background: rgba(255, 193, 7, 0.15);
    color: #ffc107;
}

.status-on-track {
    background: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.action-buttons .btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 4px;
}

.btn-sm {
    min-width: 70px;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e1e8ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    margin: 0;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: rgba(108, 117, 125, 0.1);
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e1e8ed;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-content i {
    font-size: 4rem;
    color: #e1e8ed;
    margin-bottom: 1rem;
}

.empty-content h3 {
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-content p {
    color: #9ca3af;
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-controls .form-control {
        max-width: none;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .modal-content {
        margin: 1rem;
        width: calc(100% - 2rem);
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .status-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const exportBtn = document.getElementById('exportBtn');
    const tableRows = document.querySelectorAll('#pendingSalesTable tbody tr:not(.empty-state)');
    
    // Filter functionality
    function filterTable() {
        const statusValue = statusFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            let showRow = true;
            
            // Status filter
            if (statusValue) {
                const rowStatus = row.getAttribute('data-status');
                switch (statusValue) {
                    case 'overdue':
                        showRow = rowStatus === 'status-overdue';
                        break;
                    case 'due-today':
                        showRow = rowStatus === 'status-due-today';
                        break;
                    case 'due-soon':
                        showRow = rowStatus === 'status-due-soon' || rowStatus === 'status-due-today';
                        break;
                    case 'on-track':
                        showRow = rowStatus === 'status-on-track';
                        break;
                }
            }
            
            // Search filter
            if (showRow && searchTerm) {
                const wingerName = row.getAttribute('data-winger');
                const productName = row.getAttribute('data-product');
                showRow = wingerName.includes(searchTerm) || productName.includes(searchTerm);
            }
            
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });
        
        updateTableInfo(visibleCount, tableRows.length);
    }
    
    function updateTableInfo(visible, total) {
        let infoElement = document.getElementById('tableInfo');
        if (!infoElement) {
            infoElement = document.createElement('div');
            infoElement.id = 'tableInfo';
            infoElement.style.cssText = `
                margin-top: 1rem;
                font-style: italic;
                color: #6c757d;
                text-align: center;
            `;
            document.querySelector('.table-responsive').appendChild(infoElement);
        }
        infoElement.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visible} of ${total} pending sales`;
    }
    
    // Event listeners
    statusFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
    
    // Initialize table info
    updateTableInfo(tableRows.length, tableRows.length);
    
    // Confirm/Cancel button handlers
    document.querySelectorAll('.confirm-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const saleId = this.getAttribute('data-sale-id');
            const product = this.getAttribute('data-product');
            const winger = this.getAttribute('data-winger');
            const amount = this.getAttribute('data-amount');
            
            showConfirmModal('confirm', saleId, {
                title: 'Confirm Sale',
                message: `Confirm this winger sale as completed?`,
                details: `
                    <strong>Product:</strong> ${product}<br>
                    <strong>Winger:</strong> ${winger}<br>
                    <strong>Amount:</strong> ${amount} TZS<br><br>
                    <div style="padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px; border-left: 4px solid #2ecc71;">
                        <strong>This action will:</strong><br>
                        • Mark the sale as completed<br>
                        • Add to confirmed revenue<br>
                        • Update winger's transaction history<br>
                        • Cannot be undone
                    </div>
                `,
                btnClass: 'btn-success',
                btnText: 'Confirm Sale'
            });
        });
    });
    
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const saleId = this.getAttribute('data-sale-id');
            const product = this.getAttribute('data-product');
            const winger = this.getAttribute('data-winger');
            const quantity = this.getAttribute('data-quantity');
            
            showConfirmModal('cancel', saleId, {
                title: 'Cancel Sale',
                message: `Cancel this winger sale and restore stock?`,
                details: `
                    <strong>Product:</strong> ${product}<br>
                    <strong>Winger:</strong> ${winger}<br>
                    <strong>Quantity:</strong> ${quantity} units<br><br>
                    <div style="padding: 1rem; background: rgba(231, 76, 60, 0.1); border-radius: 8px; border-left: 4px solid #e74c3c;">
                        <strong>This action will:</strong><br>
                        • Cancel the pending sale<br>
                        • Restore ${quantity} units to stock<br>
                        • Update winger's pending value<br>
                        • Cannot be undone
                    </div>
                `,
                btnClass: 'btn-danger',
                btnText: 'Cancel Sale'
            });
        });
    });
    
    // Details button handlers
    document.querySelectorAll('.details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const saleId = this.getAttribute('data-sale-id');
            const notes = this.getAttribute('data-notes');
            
            // Find the sale row
            const row = this.closest('tr');
            const cells = row.cells;
            
            const details = `
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Sale Date:</label>
                        <div>${cells[0].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Product:</label>
                        <div>${cells[1].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Quantity & Value:</label>
                        <div>${cells[2].textContent.trim()} - ${cells[3].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Winger:</label>
                        <div>${cells[4].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Bond/Collateral:</label>
                        <div>${cells[5].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Expected Return:</label>
                        <div>${cells[6].textContent.trim()}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Current Status:</label>
                        <div>${cells[7].innerHTML}</div>
                    </div>
                    ${notes ? `
                    <div>
                        <label style="font-weight: 600; color: #6c757d;">Notes:</label>
                        <div style="padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #667eea;">${notes}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            showDetailsModal(details);
        });
    });
    
    // Export functionality
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        let csv = 'Sale Date,Product,Quantity,Total Value,Winger Name,Winger Contact,Bond Item,Bond Value,Expected Return,Status\n';
        
        visibleRows.forEach(row => {
            const cells = Array.from(row.cells);
            const rowData = [
                cells[0].textContent.trim().split('\n')[0], // Sale date only
                cells[1].textContent.trim().split('\n')[0], // Product name only
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim().split('\n')[0], // Winger name only
                cells[4].textContent.trim().split('\n')[1]?.replace('📞 ', '') || '', // Contact only
                cells[5].textContent.trim().split('\n')[0], // Bond item only
                cells[5].textContent.trim().split('\n')[1]?.replace('Value: ', '') || '', // Bond value only
                cells[6].textContent.trim(),
                cells[7].textContent.trim()
            ].map(cell => `"${cell.replace(/"/g, '""')}"`);
            
            csv += rowData.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `pending_sales_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Pending sales exported successfully!', 'success');
    });
    
    // Auto-refresh every 60 seconds
    setInterval(() => {
        console.log('Auto-refreshing pending sales data...');
        // In production, this would refresh the data from server
        // For now, we'll just update the page counter
        const now = new Date();
        document.title = `Pending Sales (${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')})`;
    }, 60000);
});

function showConfirmModal(action, saleId, options) {
    const modal = document.getElementById('confirmModal');
    const title = document.getElementById('modalTitle');
    const message = document.getElementById('modalMessage');
    const details = document.getElementById('modalDetails');
    const saleIdInput = document.getElementById('modalSaleId');
    const actionInput = document.getElementById('modalAction');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    title.textContent = options.title;
    message.textContent = options.message;
    details.innerHTML = options.details;
    saleIdInput.value = saleId;
    actionInput.value = action;
    confirmBtn.className = 'btn ' + options.btnClass;
    confirmBtn.textContent = options.btnText;
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function showDetailsModal(content) {
    const modal = document.getElementById('detailsModal');
    const detailsContent = document.getElementById('detailsContent');
    
    detailsContent.innerHTML = content;
    modal.style.display = 'flex';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `<i class="fas fa-${getIconForType(type)}"></i> ${message}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
    
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
}

function getIconForType(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border-left: 4px solid;
    }
    
    .alert-success {
        background: rgba(46, 204, 113, 0.1);
        border-color: #2ecc71;
        color: #27ae60;
    }
    
    .alert-error {
        background: rgba(231, 76, 60, 0.1);
        border-color: #e74c3c;
        color: #c0392b;
    }
    
    .alert-warning {
        background: rgba(243, 156, 18, 0.1);
        border-color: #f39c12;
        color: #e67e22;
    }
    
    .alert-info {
        background: rgba(52, 152, 219, 0.1);
        border-color: #3498db;
        color: #2980b9;
    }
`;
document.head.appendChild(style);
</script>

<?php include '../includes/footer.php'; ?>