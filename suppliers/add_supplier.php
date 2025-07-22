<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Supplier name is required";
    if (empty($contact)) $errors[] = "Contact number is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check for duplicate email
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "A supplier with this email already exists";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact, email, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $contact, $email, $address);
        
        if ($stmt->execute()) {
            header("Location: view_suppliers.php?success=added");
            exit();
        } else {
            $errors[] = "Error adding supplier: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-plus"></i> Add New Supplier</h2>

<div class="card">
    <div class="card-header">
        <h3>Supplier Information</h3>
        <a href="view_suppliers.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Suppliers
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
        
        <form method="POST" action="" id="supplierForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="name"><i class="fas fa-building"></i> Supplier Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Enter supplier name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact"><i class="fas fa-phone"></i> Contact Number *</label>
                        <input type="tel" id="contact" name="contact" class="form-control" required
                               value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>"
                               placeholder="Enter contact number">
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"
                                  placeholder="Enter supplier address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Supplier
                </button>
                <a href="view_suppliers.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Supplier Guidelines -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Supplier Guidelines</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div style="padding: 1rem; background: rgba(52, 152, 219, 0.1); border-radius: 8px;">
                <h4 style="color: #3498db; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> Required Information
                </h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Company/Supplier name</li>
                    <li>Valid contact number</li>
                    <li>Business email address</li>
                    <li>Physical address (recommended)</li>
                </ul>
            </div>
            
            <div style="padding: 1rem; background: rgba(46, 204, 113, 0.1); border-radius: 8px;">
                <h4 style="color: #2ecc71; margin-bottom: 1rem;">
                    <i class="fas fa-lightbulb"></i> Best Practices
                </h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Use professional email addresses</li>
                    <li>Include area code in phone numbers</li>
                    <li>Verify supplier information before adding</li>
                    <li>Keep contact details updated</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('supplierForm');
    const nameInput = document.getElementById('name');
    const contactInput = document.getElementById('contact');
    const emailInput = document.getElementById('email');
    
    // Real-time validation
    emailInput.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.style.borderColor = '#e74c3c';
            showNotification('Please enter a valid email address', 'warning');
        } else {
            this.style.borderColor = '#e1e8ed';
        }
    });
    
    contactInput.addEventListener('input', function() {
        // Remove non-numeric characters except +, -, (, ), and spaces
        this.value = this.value.replace(/[^+\-0-9() ]/g, '');
    });
    
    contactInput.addEventListener('blur', function() {
        if (this.value && !validatePhone(this.value)) {
            this.style.borderColor = '#f39c12';
            showNotification('Please check the phone number format', 'warning');
        } else {
            this.style.borderColor = '#e1e8ed';
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        const contact = contactInput.value.trim();
        const email = emailInput.value.trim();
        
        if (!name || !contact || !email) {
            e.preventDefault();
            showNotification('Please fill in all required fields', 'danger');
            return;
        }
        
        if (!validateEmail(email)) {
            e.preventDefault();
            showNotification('Please enter a valid email address', 'danger');
            emailInput.focus();
            return;
        }
        
        if (!validatePhone(contact)) {
            e.preventDefault();
            showNotification('Please enter a valid phone number', 'danger');
            contactInput.focus();
            return;
        }
    });
    
    // Auto-format company name
    nameInput.addEventListener('blur', function() {
        if (this.value) {
            // Capitalize first letter of each word
            this.value = this.value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
        }
    });
    
    // Suggestion for email based on company name
    nameInput.addEventListener('input', function() {
        const companyName = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
        if (companyName && !emailInput.value) {
            // Auto-suggest email domain
            const suggestion = `contact@${companyName}.com`;
            emailInput.placeholder = `e.g., ${suggestion}`;
        }
    });
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-group input:valid {
    border-color: #2ecc71;
}

.form-group input:invalid:not(:placeholder-shown) {
    border-color: #e74c3c;
}

/* Floating label effect */
.form-group {
    position: relative;
}

.form-control:focus + .floating-label,
.form-control:not(:placeholder-shown) + .floating-label {
    transform: translateY(-1.5rem) scale(0.8);
    background: white;
    padding: 0 0.5rem;
}

.floating-label {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    transition: all 0.2s ease;
    pointer-events: none;
    color: #666;
}
</style>

<?php include '../includes/footer.php'; ?>