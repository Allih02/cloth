<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-history"></i> Sales History</h2>

<div class="card">
    <div class="card-header">
        <h3>All Sales Transactions</h3>
        <div>
            <a href="make_sale.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Make New Sale
            </a>
            <a href="pending_sales.php" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Sales
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Sale <?php echo htmlspecialchars($_GET['success']); ?>!
            </div>
        <?php endif; ?>
        
        <!-- Enhanced Filter Section -->
        <div class="filter-section">
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="dateFrom">From:</label>
                    <input type="date" id="dateFrom" class="form-control">
                </div>
                <div class="filter-group">
                    <label for="dateTo">To:</label>
                    <input type="date" id="dateTo" class="form-control">
                </div>
                <div class="filter-group">
                    <label for="saleTypeFilter">Sale Type:</label>
                    <select id="saleTypeFilter" class="form-control">
                        <option value="">All Sales</option>
                        <option value="regular">Regular Sales</option>
                        <option value="winger">Winger Sales</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" placeholder="Product or winger name" class="form-control">
                </div>
                <div class="filter-actions">
                    <button id="filterBtn" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button id="clearBtn" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button id="exportBtn" class="btn btn-warning">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Sales Summary -->
        <div class="stats-container">
            <div class="stat-box total">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="totalSales">0</div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>
            
            <div class="stat-box regular">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="regularSales">0</div>
                    <div class="stat-label">Regular Sales</div>
                </div>
            </div>
            
            <div class="stat-box winger">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="wingerSales">0</div>
                    <div class="stat-label">Winger Sales</div>
                </div>
            </div>
            
            <div class="stat-box revenue">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="totalRevenue">0 TZS</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <div class="stat-box completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="completedRevenue">0 TZS</div>
                    <div class="stat-label">Confirmed Revenue</div>
                </div>
            </div>
            
            <div class="stat-box pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="pendingRevenue">0 TZS</div>
                    <div class="stat-label">Pending Revenue</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="salesTable">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Sale Type</th>
                        <th>Status</th>
                        <th>Payment/Winger Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT s.*, p.name, p.price as original_price,
                               u.username as confirmed_by_user,
                               CASE 
                                   WHEN s.winger_name IS NOT NULL THEN 'winger'
                                   ELSE 'regular'
                               END as sale_type
                        FROM sales s 
                        JOIN products p ON s.product_id = p.product_id 
                        LEFT JOIN users u ON s.confirmed_by = u.user_id
                        ORDER BY s.sale_date DESC
                    ");
                    
                    $totalSales = 0;
                    $regularSales = 0;
                    $wingerSales = 0;
                    $totalRevenue = 0;
                    $completedRevenue = 0;
                    $pendingRevenue = 0;
                    
                    while ($row = $stmt->fetch_assoc()):
                        $unit_price = $row['total_price'] / $row['quantity'];
                        $totalSales++;
                        $totalRevenue += $row['total_price'];
                        
                        if ($row['sale_type'] == 'regular') {
                            $regularSales++;
                        } else {
                            $wingerSales++;
                        }
                        
                        if ($row['sale_status'] == 'completed') {
                            $completedRevenue += $row['total_price'];
                        } elseif ($row['sale_status'] == 'pending') {
                            $pendingRevenue += $row['total_price'];
                        }
                        
                        // Determine status styling
                        $status_class = '';
                        $status_icon = '';
                        switch ($row['sale_status']) {
                            case 'completed':
                                $status_class = 'status-completed';
                                $status_icon = 'fas fa-check-circle';
                                break;
                            case 'pending':
                                $status_class = 'status-pending';
                                $status_icon = 'fas fa-clock';
                                break;
                            case 'cancelled':
                                $status_class = 'status-cancelled';
                                $status_icon = 'fas fa-times-circle';
                                break;
                            default:
                                $status_class = 'status-completed';
                                $status_icon = 'fas fa-check-circle';
                        }
                        
                        // Check if overdue (for pending winger sales)
                        $is_overdue = false;
                        if ($row['sale_status'] == 'pending' && $row['expected_return_date']) {
                            $is_overdue = strtotime($row['expected_return_date']) < time();
                        }
                    ?>
                    <tr data-date="<?php echo date('Y-m-d', strtotime($row['sale_date'])); ?>"
                        data-type="<?php echo $row['sale_type']; ?>"
                        data-status="<?php echo $row['sale_status'] ?: 'completed'; ?>"
                        data-search="<?php echo strtolower($row['name'] . ' ' . ($row['winger_name'] ?? '')); ?>">
                        <td>
                            <span class="sale-id">#<?php echo $row['sale_id']; ?></span>
                        </td>
                        <td>
                            <div class="sale-datetime">
                                <?php echo date('M j, Y', strtotime($row['sale_date'])); ?>
                                <small class="time-display">
                                    <?php echo date('g:i A', strtotime($row['sale_date'])); ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <div class="product-info">
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <span class="quantity-badge">
                                <?php echo number_format($row['quantity']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="price-display">
                                <?php echo number_format($unit_price); ?> TZS
                            </span>
                        </td>
                        <td>
                            <div class="total-price">
                                <strong><?php echo number_format($row['total_price']); ?> TZS</strong>
                            </div>
                        </td>
                        <td>
                            <span class="sale-type-badge <?php echo $row['sale_type']; ?>">
                                <?php if ($row['sale_type'] == 'regular'): ?>
                                    <i class="fas fa-shopping-bag"></i> Regular
                                <?php else: ?>
                                    <i class="fas fa-handshake"></i> Winger
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?> <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                <i class="<?php echo $status_icon; ?>"></i>
                                <?php echo ucfirst($row['sale_status'] ?: 'completed'); ?>
                                <?php if ($is_overdue): ?>
                                    <small class="overdue-text">OVERDUE</small>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['sale_type'] == 'regular'): ?>
                                <div class="payment-info">
                                    <i class="fas fa-credit-card"></i>
                                    <?php echo htmlspecialchars($row['payment_method'] ?: 'CASH'); ?>
                                </div>
                            <?php else: ?>
                                <div class="winger-info">
                                    <div class="winger-name">
                                        <i class="fas fa-user"></i>
                                        <strong><?php echo htmlspecialchars($row['winger_name']); ?></strong>
                                    </div>
                                    <div class="winger-details">
                                        <small>
                                            <i class="fas fa-phone"></i> 
                                            <?php echo htmlspecialchars($row['winger_contact']); ?>
                                        </small>
                                    </div>
                                    <?php if ($row['expected_return_date']): ?>
                                        <div class="return-date">
                                            <small>
                                                <i class="fas fa-calendar"></i>
                                                Expected: <?php echo date('M j', strtotime($row['expected_return_date'])); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($row['bond_item']): ?>
                                        <div class="bond-info">
                                            <small>
                                                <i class="fas fa-shield-alt"></i>
                                                <?php echo htmlspecialchars($row['bond_item']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($row['sale_status'] == 'pending' && $row['sale_type'] == 'winger'): ?>
                                    <a href="pending_sales.php?highlight=<?php echo $row['sale_id']; ?>" 
                                       class="btn btn-warning btn-sm" title="Manage this pending sale">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-info btn-sm details-btn" 
                                        data-sale-id="<?php echo $row['sale_id']; ?>"
                                        title="View sale details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($row['sale_type'] == 'winger'): ?>
                                    <button class="btn btn-secondary btn-sm winger-history-btn" 
                                            data-winger-name="<?php echo htmlspecialchars($row['winger_name']); ?>"
                                            data-winger-contact="<?php echo htmlspecialchars($row['winger_contact']); ?>"
                                            title="View winger history">
                                        <i class="fas fa-history"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($totalSales == 0): ?>
                <div class="empty-state">
                    <div class="empty-content">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No sales recorded yet</h3>
                        <p>Start by making your first sale!</p>
                        <a href="make_sale.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Make Sale
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h4>Sale Details</h4>
            <button class="close-btn" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="detailsContent">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin"></i> Loading sale details...
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<!-- Winger History Modal -->
<div id="wingerHistoryModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h4>Winger Transaction History</h4>
            <button class="close-btn" onclick="closeWingerHistoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="wingerHistoryContent">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin"></i> Loading winger history...
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeWingerHistoryModal()">Close</button>
        </div>
    </div>
</div>

<style>
/* Enhanced Filter Section */
.filter-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid #e1e8ed;
}

.filter-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-actions .btn {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

/* Enhanced Stats Container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-box {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid;
    transition: transform 0.2s ease;
    gap: 0.75rem;
}

.stat-box:hover {
    transform: translateY(-2px);
}

.stat-box.total { border-left-color: #667eea; }
.stat-box.regular { border-left-color: #2ecc71; }
.stat-box.winger { border-left-color: #f39c12; }
.stat-box.revenue { border-left-color: #17a2b8; }
.stat-box.completed { border-left-color: #28a745; }
.stat-box.pending { border-left-color: #ffc107; }

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.stat-box.total .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-box.regular .stat-icon { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.stat-box.winger .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
.stat-box.revenue .stat-icon { background: linear-gradient(135deg, #17a2b8, #138496); }
.stat-box.completed .stat-icon { background: linear-gradient(135deg, #28a745, #1e7e34); }
.stat-box.pending .stat-icon { background: linear-gradient(135deg, #ffc107, #e0a800); }

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Enhanced Table Styles */
.table {
    font-size: 0.9rem;
}

.sale-id {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #667eea;
}

.sale-datetime {
    min-width: 100px;
}

.time-display {
    display: block;
    color: #6c757d;
    font-size: 0.8rem;
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

.price-display {
    font-family: 'Courier New', monospace;
    color: #6c757d;
}

.total-price strong {
    color: #2ecc71;
    font-size: 1rem;
}

/* Sale Type Badges */
.sale-type-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.sale-type-badge.regular {
    background: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
}

.sale-type-badge.winger {
    background: rgba(243, 156, 18, 0.15);
    color: #f39c12;
}

/* Status Badges */
.status-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    flex-direction: column;
}

.status-completed {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
}

.status-pending {
    background: rgba(255, 193, 7, 0.15);
    color: #ffc107;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

.status-badge.overdue {
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
    animation: pulse 2s infinite;
}

.overdue-text {
    font-size: 0.6rem;
    margin-top: 0.1rem;
}

/* Payment and Winger Info */
.payment-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    font-size: 0.85rem;
}

.winger-info {
    font-size: 0.85rem;
}

.winger-name {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
    color: #333;
}

.winger-details,
.return-date,
.bond-info {
    margin-bottom: 0.2rem;
    color: #6c757d;
}

.winger-details i,
.return-date i,
.bond-info i {
    width: 12px;
    text-align: center;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.action-buttons .btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
    min-width: 32px;
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
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-large {
    max-width: 900px;
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

/* Loading Content */
.loading-content {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.loading-content i {
    font-size: 2rem;
    margin-bottom: 1rem;
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
@media (max-width: 1200px) {
    .stats-container {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .filter-controls {
        grid-template-columns: 1fr;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
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
    
    .winger-info {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .filter-actions .btn {
        width: 100%;
    }
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const saleTypeFilter = document.getElementById('saleTypeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const filterBtn = document.getElementById('filterBtn');
    const clearBtn = document.getElementById('clearBtn');
    const exportBtn = document.getElementById('exportBtn');
    const tableRows = document.querySelectorAll('#salesTable tbody tr:not(.empty-state)');
    
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    dateTo.value = today.toISOString().split('T')[0];
    dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
    
    // Initialize statistics
    updateStatistics();
    
    // Filter functionality
    function filterSales() {
        const fromDate = dateFrom.value;
        const toDate = dateTo.value;
        const saleType = saleTypeFilter.value;
        const status = statusFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const rowDate = row.getAttribute('data-date');
            const rowType = row.getAttribute('data-type');
            const rowStatus = row.getAttribute('data-status');
            const searchData = row.getAttribute('data-search');
            
            let showRow = true;
            
            // Date filter
            if (fromDate && rowDate < fromDate) showRow = false;
            if (toDate && rowDate > toDate) showRow = false;
            
            // Sale type filter
            if (saleType && rowType !== saleType) showRow = false;
            
            // Status filter
            if (status && rowStatus !== status) showRow = false;
            
            // Search filter
            if (searchTerm && !searchData.includes(searchTerm)) showRow = false;
            
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });
        
        updateStatistics();
        updateTableInfo(visibleCount, tableRows.length);
    }
    
    function updateStatistics() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        let totalSales = visibleRows.length;
        let regularSales = 0;
        let wingerSales = 0;
        let totalRevenue = 0;
        let completedRevenue = 0;
        let pendingRevenue = 0;
        
        visibleRows.forEach(row => {
            const type = row.getAttribute('data-type');
            const status = row.getAttribute('data-status');
            const totalCell = row.cells[5].textContent.replace(/[^\d]/g, '');
            const total = parseInt(totalCell) || 0;
            
            totalRevenue += total;
            
            if (type === 'regular') {
                regularSales++;
            } else {
                wingerSales++;
            }
            
            if (status === 'completed') {
                completedRevenue += total;
            } else if (status === 'pending') {
                pendingRevenue += total;
            }
        });
        
        document.getElementById('totalSales').textContent = totalSales;
        document.getElementById('regularSales').textContent = regularSales;
        document.getElementById('wingerSales').textContent = wingerSales;
        document.getElementById('totalRevenue').textContent = totalRevenue.toLocaleString() + ' TZS';
        document.getElementById('completedRevenue').textContent = completedRevenue.toLocaleString() + ' TZS';
        document.getElementById('pendingRevenue').textContent = pendingRevenue.toLocaleString() + ' TZS';
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
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 8px;
            `;
            document.querySelector('.table-responsive').appendChild(infoElement);
        }
        infoElement.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visible} of ${total} sales transactions`;
    }
    
    function clearFilters() {
        dateFrom.value = '';
        dateTo.value = '';
        saleTypeFilter.value = '';
        statusFilter.value = '';
        searchInput.value = '';
        
        tableRows.forEach(row => {
            row.style.display = '';
        });
        
        updateStatistics();
        updateTableInfo(tableRows.length, tableRows.length);
    }
    
    function exportToCSV() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        let csv = 'Sale ID,Date,Time,Product,Quantity,Unit Price,Total Price,Sale Type,Status,Payment Method,Winger Name,Winger Contact,Expected Return,Bond Item,Notes\n';
        
        visibleRows.forEach(row => {
            const cells = Array.from(row.cells);
            const saleId = cells[0].textContent.trim();
            const dateTime = cells[1].textContent.trim().split('\n');
            const date = dateTime[0];
            const time = dateTime[1]?.trim() || '';
            const product = cells[2].textContent.trim();
            const quantity = cells[3].textContent.trim();
            const unitPrice = cells[4].textContent.trim();
            const totalPrice = cells[5].textContent.trim();
            const saleType = cells[6].textContent.trim();
            const status = cells[7].textContent.trim();
            
            // Extract payment/winger info
            const infoCell = cells[8];
            let paymentMethod = '';
            let wingerName = '';
            let wingerContact = '';
            let expectedReturn = '';
            let bondItem = '';
            
            if (row.getAttribute('data-type') === 'regular') {
                paymentMethod = infoCell.textContent.trim();
            } else {
                const wingerInfo = infoCell.textContent.trim();
                const lines = wingerInfo.split('\n').map(line => line.trim());
                wingerName = lines[0]?.replace(/👤|🤝/, '').trim() || '';
                wingerContact = lines[1]?.replace(/📞/, '').trim() || '';
                expectedReturn = lines[2]?.replace(/📅|Expected:/, '').trim() || '';
                bondItem = lines[3]?.replace(/🛡️/, '').trim() || '';
            }
            
            const rowData = [
                saleId, date, time, product, quantity, unitPrice, totalPrice, saleType, status,
                paymentMethod, wingerName, wingerContact, expectedReturn, bondItem, ''
            ].map(cell => `"${cell.replace(/"/g, '""')}"`);
            
            csv += rowData.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sales_history_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Sales history exported successfully!', 'success');
    }
    
    // Event listeners
    filterBtn.addEventListener('click', filterSales);
    clearBtn.addEventListener('click', clearFilters);
    exportBtn.addEventListener('click', exportToCSV);
    
    // Auto-filter when inputs change
    [dateFrom, dateTo, saleTypeFilter, statusFilter, searchInput].forEach(input => {
        input.addEventListener('change', filterSales);
        if (input.type === 'text') {
            input.addEventListener('input', filterSales);
        }
    });
    
    // Initialize with filter
    filterSales();
    
    // Details button handlers
    document.querySelectorAll('.details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const saleId = this.getAttribute('data-sale-id');
            showSaleDetails(saleId);
        });
    });
    
    // Winger history button handlers
    document.querySelectorAll('.winger-history-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const wingerName = this.getAttribute('data-winger-name');
            const wingerContact = this.getAttribute('data-winger-contact');
            showWingerHistory(wingerName, wingerContact);
        });
    });
    
    // Highlight sale if specified in URL
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        const targetRow = document.querySelector(`tr[data-sale-id="${highlightId}"]`);
        if (targetRow) {
            targetRow.style.background = 'rgba(255, 193, 7, 0.2)';
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                targetRow.style.background = '';
            }, 3000);
        }
    }
});

function showSaleDetails(saleId) {
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('detailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            Loading sale details...
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Find the sale row
    const saleRow = Array.from(document.querySelectorAll('#salesTable tbody tr')).find(row => {
        return row.cells[0].textContent.includes('#' + saleId);
    });
    
    if (saleRow) {
        setTimeout(() => {
            displaySaleDetails(saleRow, saleId);
        }, 300);
    } else {
        content.innerHTML = `
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Sale details not found.</p>
            </div>
        `;
    }
}

function displaySaleDetails(row, saleId) {
    const content = document.getElementById('detailsContent');
    const cells = row.cells;
    const saleType = row.getAttribute('data-type');
    const status = row.getAttribute('data-status');
    
    let detailsHtml = `
        <div class="sale-details">
            <div class="detail-header">
                <h3>Sale #${saleId}</h3>
                <span class="sale-type-badge ${saleType}">
                    ${saleType === 'regular' ? '<i class="fas fa-shopping-bag"></i> Regular Sale' : '<i class="fas fa-handshake"></i> Winger Sale'}
                </span>
            </div>
            
            <div class="detail-grid">
                <div class="detail-section">
                    <h4><i class="fas fa-calendar"></i> Transaction Details</h4>
                    <div class="detail-item">
                        <label>Date & Time:</label>
                        <span>${cells[1].textContent}</span>
                    </div>
                    <div class="detail-item">
                        <label>Product:</label>
                        <span>${cells[2].textContent}</span>
                    </div>
                    <div class="detail-item">
                        <label>Quantity:</label>
                        <span>${cells[3].textContent}</span>
                    </div>
                    <div class="detail-item">
                        <label>Unit Price:</label>
                        <span>${cells[4].textContent}</span>
                    </div>
                    <div class="detail-item">
                        <label>Total Amount:</label>
                        <span class="total-highlight">${cells[5].textContent}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span>${cells[7].innerHTML}</span>
                    </div>
                </div>
    `;
    
    if (saleType === 'regular') {
        detailsHtml += `
                <div class="detail-section">
                    <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                    <div class="detail-item">
                        <label>Payment Method:</label>
                        <span>${cells[8].textContent}</span>
                    </div>
                </div>
        `;
    } else {
        detailsHtml += `
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> Winger Information</h4>
                    <div class="winger-details-expanded">
                        ${cells[8].innerHTML}
                    </div>
                </div>
        `;
    }
    
    detailsHtml += `
            </div>
        </div>
    `;
    
    content.innerHTML = detailsHtml;
}

function showWingerHistory(wingerName, wingerContact) {
    const modal = document.getElementById('wingerHistoryModal');
    const content = document.getElementById('wingerHistoryContent');
    
    // Show loading
    content.innerHTML = `
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            Loading transaction history for ${wingerName}...
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Fetch transaction history
    fetch(`get_winger_history.php?name=${encodeURIComponent(wingerName)}&contact=${encodeURIComponent(wingerContact)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayWingerHistory(data.transactions, wingerName);
            } else {
                content.innerHTML = `
                    <div class="error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading transaction history: ${data.error}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="error-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading transaction history. Please try again.</p>
                </div>
            `;
        });
}

function displayWingerHistory(transactions, wingerName) {
    const content = document.getElementById('wingerHistoryContent');
    
    if (!transactions || transactions.length === 0) {
        content.innerHTML = `
            <div class="empty-content">
                <i class="fas fa-history"></i>
                <h3>No Transaction History</h3>
                <p>No transactions found for ${wingerName}</p>
            </div>
        `;
        return;
    }
    
    // Calculate summary stats
    const totalTransactions = transactions.length;
    const completedCount = transactions.filter(t => t.sale_status === 'completed').length;
    const pendingCount = transactions.filter(t => t.sale_status === 'pending').length;
    const cancelledCount = transactions.filter(t => t.sale_status === 'cancelled').length;
    const totalValue = transactions.reduce((sum, t) => sum + parseFloat(t.total_price), 0);
    const completedValue = transactions
        .filter(t => t.sale_status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.total_price), 0);
    
    let historyHtml = `
        <div class="winger-history">
            <div class="history-header">
                <h4>Transaction History for ${wingerName}</h4>
                <div class="history-summary">
                    <div class="summary-stats">
                        <div class="summary-stat">
                            <span class="stat-value">${totalTransactions}</span>
                            <span class="stat-label">Total Transactions</span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value">${completedCount}</span>
                            <span class="stat-label">Completed</span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value">${pendingCount}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value">${cancelledCount}</span>
                            <span class="stat-label">Cancelled</span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value">${totalValue.toLocaleString()} TZS</span>
                            <span class="stat-label">Total Value</span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value">${completedValue.toLocaleString()} TZS</span>
                            <span class="stat-label">Completed Value</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="history-timeline">
    `;
    
    transactions.forEach(transaction => {
        const statusClass = transaction.sale_status === 'completed' ? 'success' : 
                          transaction.sale_status === 'cancelled' ? 'danger' : 'warning';
        
        const statusIcon = transaction.sale_status === 'completed' ? 'check-circle' : 
                         transaction.sale_status === 'cancelled' ? 'times-circle' : 'clock';
        
        const isOverdue = transaction.sale_status === 'pending' && 
                         transaction.expected_return_date && 
                         new Date(transaction.expected_return_date) < new Date();
        
        historyHtml += `
            <div class="timeline-item">
                <div class="timeline-marker ${statusClass}">
                    <i class="fas fa-${statusIcon}"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <strong>${transaction.product_name}</strong>
                        <span class="timeline-date">${new Date(transaction.sale_date).toLocaleDateString()}</span>
                        ${isOverdue ? '<span class="overdue-flag"><i class="fas fa-exclamation-triangle"></i> OVERDUE</span>' : ''}
                    </div>
                    <div class="timeline-details">
                        <div class="detail-row">
                            <span>Quantity:</span> 
                            <span>${transaction.quantity} units</span>
                        </div>
                        <div class="detail-row">
                            <span>Total Value:</span> 
                            <span>${parseInt(transaction.total_price).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-row">
                            <span>Bond Item:</span> 
                            <span>${transaction.bond_item}</span>
                        </div>
                        <div class="detail-row">
                            <span>Bond Value:</span> 
                            <span>${parseInt(transaction.bond_value).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-row">
                            <span>Expected Return:</span> 
                            <span>${new Date(transaction.expected_return_date).toLocaleDateString()}</span>
                        </div>
                        <div class="detail-row">
                            <span>Status:</span> 
                            <span class="status-badge-small ${statusClass}">
                                <i class="fas fa-${statusIcon}"></i>
                                ${transaction.sale_status.toUpperCase()}
                            </span>
                        </div>
                        ${transaction.notes ? `
                        <div class="detail-row">
                            <span>Notes:</span> 
                            <span>${transaction.notes}</span>
                        </div>
                        ` : ''}
                        ${transaction.confirmed_at ? `
                        <div class="detail-row">
                            <span>Confirmed:</span> 
                            <span>${new Date(transaction.confirmed_at).toLocaleDateString()}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    historyHtml += '</div></div>';
    content.innerHTML = historyHtml;
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function closeWingerHistoryModal() {
    document.getElementById('wingerHistoryModal').style.display = 'none';
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

// Add additional styles for the details modal
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    /* Sale Details Styles */
    .sale-details {
        font-family: inherit;
    }
    
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e1e8ed;
    }
    
    .detail-header h3 {
        margin: 0;
        color: #333;
        font-size: 1.5rem;
    }
    
    .detail-grid {
        display: grid;
        gap: 2rem;
    }
    
    .detail-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .detail-section h4 {
        margin: 0 0 1rem 0;
        color: #333;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e1e8ed;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-item label {
        font-weight: 600;
        color: #6c757d;
        margin: 0;
    }
    
    .detail-item span {
        color: #333;
        font-weight: 500;
    }
    
    .total-highlight {
        color: #2ecc71 !important;
        font-size: 1.1rem !important;
        font-weight: bold !important;
    }
    
    .winger-details-expanded {
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .winger-details-expanded > div {
        margin-bottom: 0.5rem;
        padding: 0.5rem;
        background: white;
        border-radius: 4px;
    }
    
    /* History Modal Styles */
    .winger-history {
        font-family: inherit;
    }
    
    .history-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e1e8ed;
    }
    
    .history-header h4 {
        margin: 0 0 1rem 0;
        color: #333;
    }
    
    .history-summary {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }
    
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    .summary-stat {
        text-align: center;
        padding: 0.5rem;
        background: white;
        border-radius: 6px;
        border: 1px solid #e1e8ed;
    }
    
    .summary-stat .stat-value {
        display: block;
        font-weight: bold;
        font-size: 1.1rem;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .summary-stat .stat-label {
        display: block;
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .history-timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .history-timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e1e8ed;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .timeline-marker {
        position: absolute;
        left: -2rem;
        top: 1rem;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .timeline-marker.success { background: #2ecc71; }
    .timeline-marker.warning { background: #f39c12; }
    .timeline-marker.danger { background: #e74c3c; }
    
    .timeline-content {
        padding: 1.5rem;
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .timeline-header strong {
        color: #333;
        font-size: 1.1rem;
    }
    
    .timeline-date {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .overdue-flag {
        background: rgba(231, 76, 60, 0.15);
        color: #e74c3c;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .timeline-details {
        display: grid;
        gap: 0.5rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.25rem 0;
    }
    
    .detail-row span:first-child {
        color: #6c757d;
        font-weight: 500;
    }
    
    .detail-row span:last-child {
        color: #333;
        font-weight: 600;
    }
    
    .status-badge-small {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .status-badge-small.success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
    .status-badge-small.warning { background: rgba(243, 156, 18, 0.15); color: #f39c12; }
    .status-badge-small.danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
    
    .error-content {
        text-align: center;
        padding: 3rem;
        color: #e74c3c;
    }
    
    .error-content i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    /* Notifications */
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border-left: 4px solid;
    }
    
    .alert-success { background: rgba(46, 204, 113, 0.1); border-color: #2ecc71; color: #27ae60; }
    .alert-error { background: rgba(231, 76, 60, 0.1); border-color: #e74c3c; color: #c0392b; }
    .alert-warning { background: rgba(243, 156, 18, 0.1); border-color: #f39c12; color: #e67e22; }
    .alert-info { background: rgba(52, 152, 219, 0.1); border-color: #3498db; color: #2980b9; }
`;
document.head.appendChild(additionalStyles);
</script>

<?php include '../includes/footer.php'; ?>