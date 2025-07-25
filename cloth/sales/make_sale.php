<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$success_message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval(str_replace(',', '', $_POST['unit_price']));
    $sale_type = trim($_POST['sale_type']); // 'regular' or 'winger'
    
    // Regular sale fields
    $payment_method = trim($_POST['payment_method'] ?? '');
    
    // Winger sale fields
    $winger_name = trim($_POST['winger_name'] ?? '');
    $winger_contact = trim($_POST['winger_contact'] ?? '');
    $bond_item = trim($_POST['bond_item'] ?? '');
    $bond_value = floatval(str_replace(',', '', $_POST['bond_value'] ?? '0'));
    $expected_return_date = $_POST['expected_return_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if ($product_id <= 0) $errors[] = "Please select a product";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0";
    if ($unit_price <= 0) $errors[] = "Unit price must be greater than 0";
    
    if ($sale_type === 'regular') {
        if (empty($payment_method)) $errors[] = "Please select a payment method";
        $allowed_payment_methods = ['LIPA NUMBER', 'CASH', 'CRDB BANK'];
        if (!in_array($payment_method, $allowed_payment_methods)) {
            $errors[] = "Invalid payment method selected";
        }
    } elseif ($sale_type === 'winger') {
        if (empty($winger_name)) $errors[] = "Winger name is required";
        if (empty($winger_contact)) $errors[] = "Winger contact is required";
        if (empty($bond_item)) $errors[] = "Bond item description is required";
        if ($bond_value <= 0) $errors[] = "Bond value must be greater than 0";
        if (empty($expected_return_date)) $errors[] = "Expected return date is required";
        
        // Validate date is in the future
        if (strtotime($expected_return_date) <= time()) {
            $errors[] = "Expected return date must be in the future";
        }
    } else {
        $errors[] = "Please select sale type";
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Get product details with lock
            $stmt = $conn->prepare("SELECT name, price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
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
                throw new Exception("Not enough stock available. Current stock: " . number_format($product['stock_quantity']));
            }
            
            $total_price = $unit_price * $quantity;
            
            // Determine sale status
            $sale_status = ($sale_type === 'winger') ? 'pending' : 'completed';
            
            // Record sale
            if ($sale_type === 'regular') {
                $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price, payment_method, sale_status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iidss", $product_id, $quantity, $total_price, $payment_method, $sale_status);
            } else {
                $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total_price, sale_status, winger_name, winger_contact, bond_item, bond_value, expected_return_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidssssdss", $product_id, $quantity, $total_price, $sale_status, $winger_name, $winger_contact, $bond_item, $bond_value, $expected_return_date, $notes);
            }
            
            $stmt->execute();
            $sale_id = $conn->insert_id;
            $stmt->close();
            
            // Update stock (both regular and winger sales reduce stock immediately)
            $new_stock = $product['stock_quantity'] - $quantity;
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->bind_param("ii", $new_stock, $product_id);
            $stmt->execute();
            $stmt->close();
            
            // Record stock movement
            $movement_reason = ($sale_type === 'winger') ? "Winger sale (pending) - {$winger_name}" : "Regular sale";
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reason, sale_id) VALUES (?, 'out', ?, ?, ?)");
            $stmt->bind_param("iisi", $product_id, $quantity, $movement_reason, $sale_id);
            $stmt->execute();
            $stmt->close();
            
            // Update or create winger record
            if ($sale_type === 'winger') {
                $stmt = $conn->prepare("SELECT winger_id FROM wingers WHERE name = ? AND contact = ?");
                $stmt->bind_param("ss", $winger_name, $winger_contact);
                $stmt->execute();
                $winger_result = $stmt->get_result();
                $stmt->close();
                
                if ($winger_result->num_rows > 0) {
                    // Update existing winger
                    $stmt = $conn->prepare("UPDATE wingers SET total_pending_value = total_pending_value + ? WHERE name = ? AND contact = ?");
                    $stmt->bind_param("dss", $total_price, $winger_name, $winger_contact);
                } else {
                    // Create new winger
                    $stmt = $conn->prepare("INSERT INTO wingers (name, contact, total_pending_value) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssd", $winger_name, $winger_contact, $total_price);
                }
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            
            if ($sale_type === 'regular') {
                $success_message = "Regular sale completed successfully! Product: " . htmlspecialchars($product['name']) . 
                                 ", Quantity: " . number_format($quantity) . 
                                 ", Total: " . number_format($total_price) . " TZS" .
                                 ", Payment: " . htmlspecialchars($payment_method);
            } else {
                $success_message = "Winger sale recorded successfully! Product: " . htmlspecialchars($product['name']) . 
                                 ", Quantity: " . number_format($quantity) . 
                                 ", Total: " . number_format($total_price) . " TZS" .
                                 ", Winger: " . htmlspecialchars($winger_name) .
                                 ", Expected Return: " . date('M j, Y', strtotime($expected_return_date)) .
                                 " - Status: PENDING";
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Get products with stock
$products = $conn->query("SELECT product_id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name");

// Get existing wingers for quick selection
$wingers = $conn->query("SELECT * FROM wingers ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-shopping-cart"></i> Make a Sale</h2>

<div class="card">
    <div class="card-header">
        <h3>Process Sale Transaction</h3>
        <div>
            <a href="pending_sales.php" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Sales
            </a>
            <a href="sales_history.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> Sales History
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
        
        <form id="sales-form" method="POST" action="">
            <!-- Sale Type Selection -->
            <div class="sale-type-selection">
                <h4><i class="fas fa-exchange-alt"></i> Sale Type</h4>
                <div class="sale-type-options">
                    <div class="sale-type-option">
                        <input type="radio" id="regular_sale" name="sale_type" value="regular" required checked>
                        <label for="regular_sale" class="sale-type-label">
                            <div class="sale-type-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="sale-type-info">
                                <div class="sale-type-name">Regular Sale</div>
                                <div class="sale-type-desc">Immediate payment and completion</div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="sale-type-option">
                        <input type="radio" id="winger_sale" name="sale_type" value="winger" required>
                        <label for="winger_sale" class="sale-type-label">
                            <div class="sale-type-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="sale-type-info">
                                <div class="sale-type-name">Winger Sale</div>
                                <div class="sale-type-desc">Product taken with bond/collateral</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-left">
                    <!-- Product Selection -->
                    <div class="form-group">
                        <label for="product_id"><i class="fas fa-tshirt"></i> Select Product *</label>
                        <select id="product_id" name="product_id" class="form-control" required>
                            <option value="">Choose Product</option>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Stock: <?php echo $product['stock_quantity']; ?>) - <?php echo number_format($product['price']); ?> TZS
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" required placeholder="Enter quantity">
                    </div>
                    
                    <div class="form-group">
                        <label for="unit_price"><i class="fas fa-money-bill-wave"></i> Unit Price (TZS) *</label>
                        <input type="text" id="unit_price" name="unit_price" class="form-control price-input" required placeholder="Enter unit price">
                    </div>
                </div>
                
                <div class="form-right">
                    <!-- Regular Sale Fields -->
                    <div id="regular-sale-fields" class="conditional-fields">
                        <div class="form-group">
                            <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method *</label>
                            <div class="payment-methods">
                                <div class="payment-option" data-method="LIPA NUMBER">
                                    <input type="radio" id="lipa" name="payment_method" value="LIPA NUMBER">
                                    <label for="lipa" class="payment-label">
                                        <i class="fas fa-mobile-alt"></i> LIPA NUMBER
                                    </label>
                                </div>
                                <div class="payment-option" data-method="CASH">
                                    <input type="radio" id="cash" name="payment_method" value="CASH">
                                    <label for="cash" class="payment-label">
                                        <i class="fas fa-money-bill-wave"></i> CASH
                                    </label>
                                </div>
                                <div class="payment-option" data-method="CRDB BANK">
                                    <input type="radio" id="crdb" name="payment_method" value="CRDB BANK">
                                    <label for="crdb" class="payment-label">
                                        <i class="fas fa-university"></i> CRDB BANK
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Winger Sale Fields -->
                    <div id="winger-sale-fields" class="conditional-fields" style="display: none;">
                        <div class="form-group">
                            <label for="winger_select"><i class="fas fa-user"></i> Select Existing Winger</label>
                            <select id="winger_select" class="form-control">
                                <option value="">Choose existing winger or enter new</option>
                                <?php 
                                $wingers->data_seek(0);
                                while ($winger = $wingers->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($winger['name']); ?>" 
                                            data-contact="<?php echo htmlspecialchars($winger['contact']); ?>"
                                            data-score="<?php echo $winger['reliability_score']; ?>">
                                        <?php echo htmlspecialchars($winger['name']); ?> 
                                        (Score: <?php echo $winger['reliability_score']; ?>/5.0)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="winger_name"><i class="fas fa-user"></i> Winger Name *</label>
                            <input type="text" id="winger_name" name="winger_name" class="form-control" placeholder="Enter winger's full name">
                        </div>
                        
                        <div class="form-group">
                            <label for="winger_contact"><i class="fas fa-phone"></i> Winger Contact *</label>
                            <input type="tel" id="winger_contact" name="winger_contact" class="form-control" placeholder="Enter phone number">
                        </div>
                        
                        <div class="form-group">
                            <label for="bond_item"><i class="fas fa-shield-alt"></i> Bond/Collateral Item *</label>
                            <input type="text" id="bond_item" name="bond_item" class="form-control" placeholder="e.g., National ID, Phone, Watch">
                        </div>
                        
                        <div class="form-group">
                            <label for="bond_value"><i class="fas fa-gem"></i> Bond Value (TZS) *</label>
                            <input type="text" id="bond_value" name="bond_value" class="form-control price-input" placeholder="Estimated value of bond item">
                        </div>
                        
                        <div class="form-group">
                            <label for="expected_return_date"><i class="fas fa-calendar-alt"></i> Expected Return Date *</label>
                            <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="notes"><i class="fas fa-comment"></i> Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Additional notes about the winger or transaction"></textarea>
                        </div>
                    </div>
                    
                    <!-- Total Display -->
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> Total Amount</label>
                        <div id="total-display" class="total-display">0 TZS</div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                    <i class="fas fa-shopping-cart"></i> <span id="submit-text">Complete Sale</span>
                </button>
                <button type="button" class="btn btn-secondary" id="reset-btn">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Today's Sales Statistics -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Today's Sales Overview</h3>
    </div>
    <div class="card-body">
        <div class="stats-container">
            <div class="stat-box completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE() AND sale_status = 'completed'");
                        echo number_format($stmt->fetch_row()[0]);
                        ?>
                    </div>
                    <div class="stat-label">Completed Sales</div>
                </div>
            </div>
            
            <div class="stat-box pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE() AND sale_status = 'pending'");
                        echo number_format($stmt->fetch_row()[0]);
                        ?>
                    </div>
                    <div class="stat-label">Pending (Winger) Sales</div>
                </div>
            </div>
            
            <div class="stat-box revenue">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE() AND sale_status = 'completed'");
                        echo number_format($stmt->fetch_row()[0] ?? 0);
                        ?> TZS
                    </div>
                    <div class="stat-label">Confirmed Revenue</div>
                </div>
            </div>
            
            <div class="stat-box potential">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->query("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = CURDATE() AND sale_status = 'pending'");
                        echo number_format($stmt->fetch_row()[0] ?? 0);
                        ?> TZS
                    </div>
                    <div class="stat-label">Potential Revenue</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Sale Type Selection */
.sale-type-selection {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.sale-type-selection h4 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sale-type-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.sale-type-option {
    position: relative;
}

.sale-type-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.sale-type-label {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    gap: 1rem;
}

.sale-type-label:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-2px);
}

.sale-type-option input[type="radio"]:checked + .sale-type-label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.sale-type-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.sale-type-option[data-type="regular"] .sale-type-icon,
.sale-type-option:first-child .sale-type-icon {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.sale-type-option[data-type="winger"] .sale-type-icon,
.sale-type-option:last-child .sale-type-icon {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.sale-type-name {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.sale-type-desc {
    font-size: 0.9rem;
    color: #6c757d;
}

.sale-type-option input[type="radio"]:checked + .sale-type-label .sale-type-name {
    color: #667eea;
}

.sale-type-option input[type="radio"]:checked + .sale-type-label .sale-type-icon {
    transform: scale(1.1);
}

/* Form Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.conditional-fields {
    transition: all 0.3s ease;
}

.conditional-fields.show {
    display: block !important;
}

/* Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-label {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    gap: 0.75rem;
}

.payment-label:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.payment-option input[type="radio"]:checked + .payment-label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    font-weight: 600;
}

/* Enhanced Stats */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-box {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 12px;
    border-left: 4px solid;
    transition: transform 0.2s ease;
    gap: 1rem;
}

.stat-box:hover {
    transform: translateY(-2px);
}

.stat-box.completed {
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
    border-left-color: #2ecc71;
}

.stat-box.pending {
    background: linear-gradient(135deg, rgba(243, 156, 18, 0.1), rgba(243, 156, 18, 0.05));
    border-left-color: #f39c12;
}

.stat-box.revenue {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.05));
    border-left-color: #667eea;
}

.stat-box.potential {
    background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(155, 89, 182, 0.05));
    border-left-color: #9b59b6;
}

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

.stat-box.completed .stat-icon {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.stat-box.pending .stat-icon {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-box.revenue .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-box.potential .stat-icon {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
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

/* Price Input Styling */
.price-input {
    font-family: 'Courier New', monospace;
    text-align: right;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Total Display */
.total-display {
    padding: 1rem;
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
    border: 2px solid #2ecc71;
    border-radius: 12px;
    font-size: 1.8rem;
    font-weight: bold;
    color: #2ecc71;
    text-align: center;
    transition: all 0.3s ease;
}

.total-display.updating {
    transform: scale(1.05);
}

/* Form Actions */
.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e1e8ed;
    display: flex;
    gap: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sale-type-options {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Utility Functions
function formatTZS(amount) {
    return new Intl.NumberFormat('en-US').format(Math.round(amount)) + ' TZS';
}

function formatPrice(price) {
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function parsePrice(priceString) {
    return parseFloat(priceString.replace(/,/g, '')) || 0;
}

function addCommasToInput(input) {
    let value = input.value.replace(/,/g, '');
    let cursorPos = input.selectionStart;
    let valueBeforeCursor = input.value.substring(0, cursorPos).replace(/,/g, '');
    
    let formattedValue = formatPrice(value);
    let commasBeforeCursor = (valueBeforeCursor.match(/,/g) || []).length;
    let newCommasBeforeCursor = (formattedValue.substring(0, valueBeforeCursor.length + commasBeforeCursor).match(/,/g) || []).length;
    let newCursorPos = cursorPos + (newCommasBeforeCursor - commasBeforeCursor);
    
    input.value = formattedValue;
    input.setSelectionRange(newCursorPos, newCursorPos);
}

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const salesForm = document.getElementById('sales-form');
    const saleTypeRadios = document.querySelectorAll('input[name="sale_type"]');
    const regularFields = document.getElementById('regular-sale-fields');
    const wingerFields = document.getElementById('winger-sale-fields');
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalDisplay = document.getElementById('total-display');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const resetBtn = document.getElementById('reset-btn');
    const wingerSelect = document.getElementById('winger_select');
    const wingerNameInput = document.getElementById('winger_name');
    const wingerContactInput = document.getElementById('winger_contact');
    const bondValueInput = document.getElementById('bond_value');
    const expectedReturnDate = document.getElementById('expected_return_date');
    
    // Functions
    function toggleSaleTypeFields() {
        const saleType = document.querySelector('input[name="sale_type"]:checked').value;
        
        if (saleType === 'regular') {
            regularFields.style.display = 'block';
            wingerFields.style.display = 'none';
            submitText.textContent = 'Complete Sale';
            
            // Make regular fields required
            document.querySelectorAll('#regular-sale-fields input[type="radio"]').forEach(input => {
                input.required = true;
            });
            
            // Make winger fields not required
            document.querySelectorAll('#winger-sale-fields input, #winger-sale-fields textarea').forEach(input => {
                input.required = false;
            });
            
        } else {
            regularFields.style.display = 'none';
            wingerFields.style.display = 'block';
            submitText.textContent = 'Record Winger Sale';
            
            // Make winger fields required
            wingerNameInput.required = true;
            wingerContactInput.required = true;
            document.getElementById('bond_item').required = true;
            bondValueInput.required = true;
            expectedReturnDate.required = true;
            
            // Make regular fields not required
            document.querySelectorAll('#regular-sale-fields input[type="radio"]').forEach(input => {
                input.required = false;
            });
        }
        
        updateSubmitButton();
    }
    
    function calculateTotal() {
        const unitPrice = parsePrice(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value) || 0;
        const total = unitPrice * quantity;
        
        totalDisplay.textContent = formatTZS(total);
        
        if (total > 0) {
            totalDisplay.classList.add('updating');
            setTimeout(() => totalDisplay.classList.remove('updating'), 200);
        }
    }
    
    function updateProductInfo() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const productPrice = parseFloat(selectedOption.getAttribute('data-price'));
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            
            // Auto-fill unit price if empty
            if (!unitPriceInput.value.trim()) {
                unitPriceInput.value = formatPrice(productPrice);
            }
            
            // Update quantity constraints
            quantityInput.setAttribute('max', stock);
            if (parseInt(quantityInput.value) > stock) {
                quantityInput.value = stock;
                showNotification(`Quantity adjusted to maximum available: ${stock}`, 'warning');
            }
        }
        
        calculateTotal();
        updateSubmitButton();
    }
    
    function updateSubmitButton() {
        const saleType = document.querySelector('input[name="sale_type"]:checked')?.value;
        const hasProduct = productSelect.value !== '';
        const hasQuantity = quantityInput.value !== '' && parseInt(quantityInput.value) > 0;
        const hasUnitPrice = unitPriceInput.value !== '' && parsePrice(unitPriceInput.value) > 0;
        
        let hasPaymentInfo = false;
        
        if (saleType === 'regular') {
            hasPaymentInfo = document.querySelector('input[name="payment_method"]:checked') !== null;
        } else if (saleType === 'winger') {
            hasPaymentInfo = wingerNameInput.value.trim() !== '' &&
                            wingerContactInput.value.trim() !== '' &&
                            document.getElementById('bond_item').value.trim() !== '' &&
                            bondValueInput.value !== '' &&
                            parsePrice(bondValueInput.value) > 0 &&
                            expectedReturnDate.value !== '';
        }
        
        submitBtn.disabled = !(hasProduct && hasQuantity && hasUnitPrice && hasPaymentInfo);
    }
    
    function resetForm() {
        salesForm.reset();
        document.getElementById('regular_sale').checked = true;
        toggleSaleTypeFields();
        totalDisplay.textContent = '0 TZS';
        updateSubmitButton();
        productSelect.focus();
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
    
    function validateForm() {
        const saleType = document.querySelector('input[name="sale_type"]:checked')?.value;
        const quantity = parseInt(quantityInput.value) || 0;
        const unitPrice = parsePrice(unitPriceInput.value);
        
        if (!productSelect.value) {
            showNotification('Please select a product', 'error');
            return false;
        }
        
        if (quantity <= 0) {
            showNotification('Please enter a valid quantity', 'error');
            quantityInput.focus();
            return false;
        }
        
        if (unitPrice <= 0) {
            showNotification('Please enter a valid unit price', 'error');
            unitPriceInput.focus();
            return false;
        }
        
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = parseInt(selectedOption.getAttribute('data-stock'));
        if (quantity > stock) {
            showNotification(`Not enough stock available. Maximum: ${stock}`, 'error');
            quantityInput.focus();
            return false;
        }
        
        if (saleType === 'regular') {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                showNotification('Please select a payment method', 'error');
                return false;
            }
        } else if (saleType === 'winger') {
            if (!wingerNameInput.value.trim()) {
                showNotification('Please enter winger name', 'error');
                wingerNameInput.focus();
                return false;
            }
            
            if (!wingerContactInput.value.trim()) {
                showNotification('Please enter winger contact', 'error');
                wingerContactInput.focus();
                return false;
            }
            
            if (!document.getElementById('bond_item').value.trim()) {
                showNotification('Please enter bond item description', 'error');
                document.getElementById('bond_item').focus();
                return false;
            }
            
            const bondValue = parsePrice(bondValueInput.value);
            if (bondValue <= 0) {
                showNotification('Please enter a valid bond value', 'error');
                bondValueInput.focus();
                return false;
            }
            
            if (!expectedReturnDate.value) {
                showNotification('Please select expected return date', 'error');
                expectedReturnDate.focus();
                return false;
            }
            
            // Check if return date is in the future
            if (new Date(expectedReturnDate.value) <= new Date()) {
                showNotification('Expected return date must be in the future', 'error');
                expectedReturnDate.focus();
                return false;
            }
        }
        
        return true;
    }
    
    function confirmSale() {
        const saleType = document.querySelector('input[name="sale_type"]:checked').value;
        const productName = productSelect.options[productSelect.selectedIndex].textContent.split(' (')[0];
        const quantity = parseInt(quantityInput.value);
        const unitPrice = parsePrice(unitPriceInput.value);
        const total = unitPrice * quantity;
        
        let confirmMessage = `Confirm ${saleType} sale:\n\nProduct: ${productName}\nQuantity: ${quantity}\nUnit Price: ${formatTZS(unitPrice)}\nTotal: ${formatTZS(total)}\n`;
        
        if (saleType === 'regular') {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            confirmMessage += `Payment Method: ${paymentMethod}\n`;
        } else {
            const wingerName = wingerNameInput.value;
            const bondItem = document.getElementById('bond_item').value;
            const bondValue = parsePrice(bondValueInput.value);
            const returnDate = expectedReturnDate.value;
            
            confirmMessage += `Winger: ${wingerName}\nBond Item: ${bondItem}\nBond Value: ${formatTZS(bondValue)}\nExpected Return: ${new Date(returnDate).toLocaleDateString()}\n\nNote: This sale will be marked as PENDING until confirmed.`;
        }
        
        confirmMessage += '\n\nProceed with this sale?';
        
        return confirm(confirmMessage);
    }
    
    // Event Listeners
    
    // Sale type change
    saleTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleSaleTypeFields);
    });
    
    // Product selection
    productSelect.addEventListener('change', updateProductInfo);
    
    // Quantity input
    quantityInput.addEventListener('input', function() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (selectedOption) {
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            const quantity = parseInt(this.value);
            
            if (quantity > stock) {
                this.value = stock;
                showNotification(`Maximum available: ${stock}`, 'warning');
            }
        }
        
        calculateTotal();
        updateSubmitButton();
    });
    
    // Unit price input with comma formatting
    unitPriceInput.addEventListener('input', function() {
        addCommasToInput(this);
        calculateTotal();
        updateSubmitButton();
    });
    
    // Bond value input with comma formatting
    bondValueInput.addEventListener('input', function() {
        addCommasToInput(this);
        updateSubmitButton();
    });
    
    // Payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', updateSubmitButton);
    });
    
    // Winger selection from dropdown
    wingerSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            wingerNameInput.value = this.value;
            wingerContactInput.value = selectedOption.getAttribute('data-contact') || '';
            
            const score = parseFloat(selectedOption.getAttribute('data-score'));
            if (score < 3.0) {
                showNotification(`Warning: This winger has a low reliability score (${score}/5.0)`, 'warning');
            }
        }
        updateSubmitButton();
    });
    
    // Winger fields input
    [wingerNameInput, wingerContactInput, document.getElementById('bond_item'), expectedReturnDate].forEach(input => {
        input.addEventListener('input', updateSubmitButton);
        input.addEventListener('change', updateSubmitButton);
    });
    
    // Set minimum date for expected return
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    expectedReturnDate.min = tomorrow.toISOString().split('T')[0];
    
    // Reset button
    resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        resetForm();
        showNotification('Form reset successfully', 'info');
    });
    
    // Form submission
    salesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        if (!confirmSale()) {
            return;
        }
        
        // Show loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Clean price values for submission
        const cleanUnitPrice = parsePrice(unitPriceInput.value);
        const cleanBondValue = parsePrice(bondValueInput.value);
        
        // Create form data
        const formData = new FormData(this);
        formData.set('unit_price', cleanUnitPrice.toString());
        if (bondValueInput.value) {
            formData.set('bond_value', cleanBondValue.toString());
        }
        
        // Submit form
        fetch(this.action || window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            document.documentElement.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showNotification('Error processing sale. Please try again.', 'error');
        });
    });
    
    // Initialize
    toggleSaleTypeFields();
    updateSubmitButton();
    
    // Auto-focus
    setTimeout(() => {
        productSelect.focus();
    }, 500);
    
    // Add notification styles
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
    `;
    document.head.appendChild(style);
    
    console.log('Enhanced Make Sale with Winger Support initialized');
});
</script>

<?php include '../includes/footer.php'; ?>