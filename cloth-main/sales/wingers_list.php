<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$success_message = '';
$errors = [];

// Handle winger operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_score') {
        $winger_id = intval($_POST['winger_id']);
        $new_score = floatval($_POST['reliability_score']);
        $notes = trim($_POST['notes']);
        
        if ($winger_id > 0 && $new_score >= 1.0 && $new_score <= 5.0) {
            $stmt = $conn->prepare("UPDATE wingers SET reliability_score = ?, notes = ? WHERE winger_id = ?");
            $stmt->bind_param("dsi", $new_score, $notes, $winger_id);
            
            if ($stmt->execute()) {
                $success_message = "Winger reliability score updated successfully!";
            } else {
                $errors[] = "Error updating winger: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Invalid winger ID or score (must be between 1.0 and 5.0)";
        }
    }
}

// Get all wingers with their statistics
$wingers = $conn->query("
    SELECT w.*,
           COUNT(s.sale_id) as total_transactions,
           COUNT(CASE WHEN s.sale_status = 'pending' THEN 1 END) as pending_count,
           COUNT(CASE WHEN s.sale_status = 'completed' THEN 1 END) as completed_count,
           COUNT(CASE WHEN s.sale_status = 'cancelled' THEN 1 END) as cancelled_count,
           AVG(DATEDIFF(s.confirmed_at, s.sale_date)) as avg_completion_days,
           MAX(s.sale_date) as last_transaction_date
    FROM wingers w
    LEFT JOIN sales s ON w.name = s.winger_name AND w.contact = s.winger_contact
    GROUP BY w.winger_id
    ORDER BY w.reliability_score DESC, w.total_completed_sales DESC
");

// Get summary statistics
$winger_stats = $conn->query("
    SELECT 
        COUNT(*) as total_wingers,
        AVG(reliability_score) as avg_score,
        SUM(total_pending_value) as total_pending,
        SUM(total_completed_sales) as total_completed,
        COUNT(CASE WHEN reliability_score >= 4.0 THEN 1 END) as reliable_count,
        COUNT(CASE WHEN reliability_score < 3.0 THEN 1 END) as unreliable_count
    FROM wingers
")->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-users"></i> Wingers Management</h2>

<!-- Summary Statistics -->
<div class="stats-container">
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($winger_stats['total_wingers']); ?></div>
            <div class="stat-label">Total Wingers</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($winger_stats['reliable_count']); ?></div>
            <div class="stat-label">Reliable (4.0+ Rating)</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($winger_stats['unreliable_count']); ?></div>
            <div class="stat-label">Need Attention (<3.0)</div>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($winger_stats['avg_score'], 1); ?>/5.0</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Wingers</h3>
        <div>
            <a href="pending_sales.php" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Sales
            </a>
            <a href="make_sale.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Sale
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
                <select id="scoreFilter" class="form-control">
                    <option value="">All Scores</option>
                    <option value="excellent">Excellent (4.5-5.0)</option>
                    <option value="good">Good (3.5-4.4)</option>
                    <option value="average">Average (2.5-3.4)</option>
                    <option value="poor">Poor (1.0-2.4)</option>
                </select>
                
                <select id="activityFilter" class="form-control">
                    <option value="">All Activity</option>
                    <option value="active">Active (Recent transactions)</option>
                    <option value="inactive">Inactive (No recent activity)</option>
                    <option value="pending">Has Pending Sales</option>
                </select>
                
                <input type="text" id="searchInput" placeholder="Search by name or contact" class="form-control">
                
                <button id="exportBtn" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="wingersTable">
                <thead>
                    <tr>
                        <th>Winger Info</th>
                        <th>Contact</th>
                        <th>Reliability Score</th>
                        <th>Transaction Summary</th>
                        <th>Financial Summary</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($wingers->num_rows > 0): ?>
                        <?php while ($winger = $wingers->fetch_assoc()): ?>
                            <?php
                            $score = floatval($winger['reliability_score']);
                            $score_class = '';
                            $score_text = '';
                            
                            if ($score >= 4.5) {
                                $score_class = 'score-excellent';
                                $score_text = 'Excellent';
                            } elseif ($score >= 3.5) {
                                $score_class = 'score-good';
                                $score_text = 'Good';
                            } elseif ($score >= 2.5) {
                                $score_class = 'score-average';
                                $score_text = 'Average';
                            } else {
                                $score_class = 'score-poor';
                                $score_text = 'Poor';
                            }
                            
                            $has_pending = $winger['pending_count'] > 0;
                            $is_active = $winger['last_transaction_date'] && 
                                        strtotime($winger['last_transaction_date']) > strtotime('-30 days');
                            ?>
                            <tr data-score="<?php echo $score; ?>" 
                                data-activity="<?php echo $is_active ? 'active' : 'inactive'; ?>"
                                data-pending="<?php echo $has_pending ? 'yes' : 'no'; ?>"
                                data-search="<?php echo strtolower($winger['name'] . ' ' . $winger['contact']); ?>">
                                <td>
                                    <div class="winger-info">
                                        <div class="winger-name">
                                            <strong><?php echo htmlspecialchars($winger['name']); ?></strong>
                                            <?php if ($has_pending): ?>
                                                <span class="pending-indicator" title="Has pending sales">
                                                    <i class="fas fa-clock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($winger['address']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($winger['address']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <a href="tel:<?php echo $winger['contact']; ?>" class="contact-link">
                                            <i class="fas fa-phone"></i> 
                                            <?php echo htmlspecialchars($winger['contact']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div class="score-display">
                                        <div class="score-value <?php echo $score_class; ?>">
                                            <?php echo number_format($score, 1); ?>/5.0
                                        </div>
                                        <div class="score-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $score ? 'filled' : 'empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="score-text"><?php echo $score_text; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="transaction-summary">
                                        <div class="summary-row">
                                            <span class="summary-label">Total:</span>
                                            <span class="summary-value"><?php echo number_format($winger['total_transactions']); ?></span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Completed:</span>
                                            <span class="summary-value text-success"><?php echo number_format($winger['completed_count']); ?></span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Pending:</span>
                                            <span class="summary-value text-warning"><?php echo number_format($winger['pending_count']); ?></span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Cancelled:</span>
                                            <span class="summary-value text-danger"><?php echo number_format($winger['cancelled_count']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="financial-summary">
                                        <div class="summary-row">
                                            <span class="summary-label">Pending:</span>
                                            <span class="summary-value text-warning">
                                                <?php echo number_format($winger['total_pending_value']); ?> TZS
                                            </span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Completed:</span>
                                            <span class="summary-value text-success">
                                                <?php echo number_format($winger['total_completed_sales']); ?> TZS
                                            </span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Total Value:</span>
                                            <span class="summary-value">
                                                <?php echo number_format($winger['total_pending_value'] + $winger['total_completed_sales']); ?> TZS
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="activity-info">
                                        <?php if ($winger['last_transaction_date']): ?>
                                            <div class="last-activity">
                                                <?php echo date('M j, Y', strtotime($winger['last_transaction_date'])); ?>
                                            </div>
                                            <small class="activity-status <?php echo $is_active ? 'text-success' : 'text-muted'; ?>">
                                                <?php 
                                                $days_ago = floor((time() - strtotime($winger['last_transaction_date'])) / (60*60*24));
                                                echo $days_ago . ' days ago';
                                                ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">No transactions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm edit-score-btn" 
                                                data-winger-id="<?php echo $winger['winger_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($winger['name']); ?>"
                                                data-score="<?php echo $winger['reliability_score']; ?>"
                                                data-notes="<?php echo htmlspecialchars($winger['notes'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        
                                        <button class="btn btn-info btn-sm view-history-btn" 
                                                data-winger-name="<?php echo htmlspecialchars($winger['name']); ?>"
                                                data-winger-contact="<?php echo htmlspecialchars($winger['contact']); ?>">
                                            <i class="fas fa-history"></i> History
                                        </button>
                                        
                                        <?php if ($has_pending): ?>
                                            <a href="pending_sales.php?winger=<?php echo urlencode($winger['name']); ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-clock"></i> Pending
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-content">
                                    <i class="fas fa-users"></i>
                                    <h3>No Wingers Found</h3>
                                    <p>No winger transactions have been recorded yet.</p>
                                    <a href="make_sale.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create First Winger Sale
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

<!-- Edit Score Modal -->
<div id="editScoreModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Edit Winger Reliability Score</h4>
            <button class="close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" id="editScoreForm">
            <input type="hidden" name="action" value="update_score">
            <input type="hidden" name="winger_id" id="editWingerId">
            
            <div class="modal-body">
                <div id="editWingerName" class="winger-display"></div>
                
                <div class="form-group">
                    <label for="reliability_score">Reliability Score (1.0 - 5.0)</label>
                    <div class="score-input-container">
                        <input type="number" name="reliability_score" id="editReliabilityScore" 
                               class="form-control" min="1.0" max="5.0" step="0.1" required>
                        <div class="score-preview" id="scorePreview">
                            <div class="preview-stars" id="previewStars"></div>
                            <div class="preview-text" id="previewText"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea name="notes" id="editNotes" class="form-control" rows="3" 
                              placeholder="Add notes about this winger's reliability..."></textarea>
                </div>
                
                <div class="score-guide">
                    <h5>Scoring Guide:</h5>
                    <div class="guide-item">
                        <span class="guide-score score-excellent">5.0 - 4.5</span>
                        <span class="guide-desc">Excellent - Always returns on time, very reliable</span>
                    </div>
                    <div class="guide-item">
                        <span class="guide-score score-good">4.4 - 3.5</span>
                        <span class="guide-desc">Good - Usually reliable, occasional delays</span>
                    </div>
                    <div class="guide-item">
                        <span class="guide-score score-average">3.4 - 2.5</span>
                        <span class="guide-desc">Average - Sometimes delays, requires follow-up</span>
                    </div>
                    <div class="guide-item">
                        <span class="guide-score score-poor">2.4 - 1.0</span>
                        <span class="guide-desc">Poor - Often late, unreliable</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Score
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h4>Transaction History</h4>
            <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="historyContent">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin"></i> Loading transaction history...
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeHistoryModal()">Close</button>
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
.stat-card.success { border-left-color: #2ecc71; }
.stat-card.warning { border-left-color: #f39c12; }
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
.stat-card.success .stat-icon { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
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

/* Table Styles */
.winger-info {
    min-width: 150px;
}

.winger-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.pending-indicator {
    color: #f39c12;
    animation: pulse 2s infinite;
}

.contact-link {
    color: #667eea;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.contact-link:hover {
    color: #4c63d2;
    text-decoration: underline;
}

/* Score Display */
.score-display {
    text-align: center;
}

.score-value {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.score-excellent { color: #2ecc71; }
.score-good { color: #27ae60; }
.score-average { color: #f39c12; }
.score-poor { color: #e74c3c; }

.score-stars {
    margin-bottom: 0.25rem;
}

.score-stars .fa-star {
    font-size: 0.8rem;
    margin: 0 1px;
}

.score-stars .fa-star.filled {
    color: #ffc107;
}

.score-stars .fa-star.empty {
    color: #e1e8ed;
}

.score-text {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Summary Displays */
.transaction-summary,
.financial-summary {
    font-size: 0.85rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.summary-label {
    color: #6c757d;
}

.summary-value {
    font-weight: 600;
}

/* Activity Info */
.activity-info {
    min-width: 100px;
}

.last-activity {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-status {
    font-size: 0.8rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-direction: column;
}

.action-buttons .btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 4px;
    white-space: nowrap;
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

.modal-large {
    max-width: 800px;
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

/* Edit Score Modal Specific */
.winger-display {
    padding: 1rem;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 600;
    color: #333;
}

.score-input-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    align-items: start;
}

.score-preview {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.preview-stars {
    margin-bottom: 0.5rem;
}

.preview-stars .fa-star {
    font-size: 1rem;
    margin: 0 2px;
}

.preview-text {
    font-size: 0.9rem;
    font-weight: 600;
}

.score-guide {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.score-guide h5 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 0.9rem;
}

.guide-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background: white;
    border-radius: 6px;
}

.guide-score {
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    min-width: 60px;
    text-align: center;
}

.guide-desc {
    flex: 1;
    margin-left: 1rem;
    font-size: 0.8rem;
    color: #6c757d;
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
    
    .score-input-container {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-content {
        margin: 1rem;
        width: calc(100% - 2rem);
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .summary-row {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .guide-item {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .guide-desc {
        margin-left: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreFilter = document.getElementById('scoreFilter');
    const activityFilter = document.getElementById('activityFilter');
    const searchInput = document.getElementById('searchInput');
    const exportBtn = document.getElementById('exportBtn');
    const tableRows = document.querySelectorAll('#wingersTable tbody tr:not(.empty-state)');
    
    // Filter functionality
    function filterTable() {
        const scoreValue = scoreFilter.value;
        const activityValue = activityFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            let showRow = true;
            
            // Score filter
            if (scoreValue) {
                const score = parseFloat(row.getAttribute('data-score'));
                switch (scoreValue) {
                    case 'excellent':
                        showRow = score >= 4.5;
                        break;
                    case 'good':
                        showRow = score >= 3.5 && score < 4.5;
                        break;
                    case 'average':
                        showRow = score >= 2.5 && score < 3.5;
                        break;
                    case 'poor':
                        showRow = score < 2.5;
                        break;
                }
            }
            
            // Activity filter
            if (showRow && activityValue) {
                const activity = row.getAttribute('data-activity');
                const pending = row.getAttribute('data-pending');
                
                switch (activityValue) {
                    case 'active':
                        showRow = activity === 'active';
                        break;
                    case 'inactive':
                        showRow = activity === 'inactive';
                        break;
                    case 'pending':
                        showRow = pending === 'yes';
                        break;
                }
            }
            
            // Search filter
            if (showRow && searchTerm) {
                const searchData = row.getAttribute('data-search');
                showRow = searchData.includes(searchTerm);
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
        infoElement.innerHTML = `<i class="fas fa-info-circle"></i> Showing ${visible} of ${total} wingers`;
    }
    
    // Event listeners for filters
    scoreFilter.addEventListener('change', filterTable);
    activityFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
    
    // Initialize table info
    updateTableInfo(tableRows.length, tableRows.length);
    
    // Edit Score button handlers
    document.querySelectorAll('.edit-score-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const wingerId = this.getAttribute('data-winger-id');
            const name = this.getAttribute('data-name');
            const score = this.getAttribute('data-score');
            const notes = this.getAttribute('data-notes');
            
            showEditScoreModal(wingerId, name, score, notes);
        });
    });
    
    // View History button handlers
    document.querySelectorAll('.view-history-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const wingerName = this.getAttribute('data-winger-name');
            const wingerContact = this.getAttribute('data-winger-contact');
            
            showHistoryModal(wingerName, wingerContact);
        });
    });
    
    // Export functionality
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        let csv = 'Name,Contact,Address,Reliability Score,Total Transactions,Completed,Pending,Cancelled,Pending Value,Completed Value,Last Activity\n';
        
        visibleRows.forEach(row => {
            const cells = Array.from(row.cells);
            const name = cells[0].textContent.trim().split('\n')[0];
            const contact = cells[1].textContent.trim().replace('📞 ', '');
            const address = cells[0].querySelector('small')?.textContent.replace('📍 ', '') || '';
            const score = cells[2].querySelector('.score-value').textContent.trim();
            
            // Extract transaction summary
            const transactionCells = cells[3].querySelectorAll('.summary-value');
            const total = transactionCells[0]?.textContent.trim() || '0';
            const completed = transactionCells[1]?.textContent.trim() || '0';
            const pending = transactionCells[2]?.textContent.trim() || '0';
            const cancelled = transactionCells[3]?.textContent.trim() || '0';
            
            // Extract financial summary
            const financialCells = cells[4].querySelectorAll('.summary-value');
            const pendingValue = financialCells[0]?.textContent.trim() || '0 TZS';
            const completedValue = financialCells[1]?.textContent.trim() || '0 TZS';
            
            const lastActivity = cells[5].textContent.trim().split('\n')[0];
            
            const rowData = [
                name, contact, address, score, total, completed, pending, cancelled,
                pendingValue, completedValue, lastActivity
            ].map(cell => `"${cell.replace(/"/g, '""')}"`);
            
            csv += rowData.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wingers_report_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Wingers data exported successfully!', 'success');
    });
    
    // Score input real-time preview
    const scoreInput = document.getElementById('editReliabilityScore');
    if (scoreInput) {
        scoreInput.addEventListener('input', updateScorePreview);
    }
});

function showEditScoreModal(wingerId, name, score, notes) {
    document.getElementById('editWingerId').value = wingerId;
    document.getElementById('editWingerName').textContent = name;
    document.getElementById('editReliabilityScore').value = score;
    document.getElementById('editNotes').value = notes;
    
    updateScorePreview();
    document.getElementById('editScoreModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editScoreModal').style.display = 'none';
}

function updateScorePreview() {
    const score = parseFloat(document.getElementById('editReliabilityScore').value) || 0;
    const starsContainer = document.getElementById('previewStars');
    const textContainer = document.getElementById('previewText');
    
    // Update stars
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        const filled = i <= score ? 'filled' : 'empty';
        starsHtml += `<i class="fas fa-star ${filled}"></i>`;
    }
    starsContainer.innerHTML = starsHtml;
    
    // Update text and color
    let text = '';
    let className = '';
    
    if (score >= 4.5) {
        text = 'Excellent';
        className = 'score-excellent';
    } else if (score >= 3.5) {
        text = 'Good';
        className = 'score-good';
    } else if (score >= 2.5) {
        text = 'Average';
        className = 'score-average';
    } else if (score >= 1.0) {
        text = 'Poor';
        className = 'score-poor';
    } else {
        text = 'Invalid';
        className = '';
    }
    
    textContainer.textContent = text;
    textContainer.className = `preview-text ${className}`;
}

function showHistoryModal(wingerName, wingerContact) {
    const modal = document.getElementById('historyModal');
    const content = document.getElementById('historyContent');
    
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
                displayHistory(data.transactions, wingerName);
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

function displayHistory(transactions, wingerName) {
    const content = document.getElementById('historyContent');
    
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
    
    let historyHtml = `
        <div class="history-header">
            <h4>Transaction History for ${wingerName}</h4>
            <p>${transactions.length} total transactions</p>
        </div>
        <div class="history-timeline">
    `;
    
    transactions.forEach(transaction => {
        const statusClass = transaction.sale_status === 'completed' ? 'success' : 
                          transaction.sale_status === 'cancelled' ? 'danger' : 'warning';
        
        const statusIcon = transaction.sale_status === 'completed' ? 'check-circle' : 
                         transaction.sale_status === 'cancelled' ? 'times-circle' : 'clock';
        
        historyHtml += `
            <div class="timeline-item">
                <div class="timeline-marker ${statusClass}">
                    <i class="fas fa-${statusIcon}"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <strong>${transaction.product_name}</strong>
                        <span class="timeline-date">${new Date(transaction.sale_date).toLocaleDateString()}</span>
                    </div>
                    <div class="timeline-details">
                        <div class="detail-row">
                            <span>Quantity:</span> <span>${transaction.quantity} units</span>
                        </div>
                        <div class="detail-row">
                            <span>Total Value:</span> <span>${parseInt(transaction.total_price).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-row">
                            <span>Bond Item:</span> <span>${transaction.bond_item}</span>
                        </div>
                        <div class="detail-row">
                            <span>Bond Value:</span> <span>${parseInt(transaction.bond_value).toLocaleString()} TZS</span>
                        </div>
                        <div class="detail-row">
                            <span>Expected Return:</span> <span>${new Date(transaction.expected_return_date).toLocaleDateString()}</span>
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
                            <span>Notes:</span> <span>${transaction.notes}</span>
                        </div>
                        ` : ''}
                        ${transaction.confirmed_at ? `
                        <div class="detail-row">
                            <span>Confirmed:</span> <span>${new Date(transaction.confirmed_at).toLocaleDateString()}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    historyHtml += '</div>';
    content.innerHTML = historyHtml;
}

function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
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

// Add notification and timeline styles
const style = document.createElement('style');
style.textContent = `
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
    
    .history-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e1e8ed;
    }
    
    .history-header h4 {
        margin: 0 0 0.5rem 0;
        color: #333;
    }
    
    .history-header p {
        margin: 0;
        color: #6c757d;
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
`;
document.head.appendChild(style);
</script>

<?php include '../includes/footer.php'; ?>