<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $contact, $email);
    
    if ($stmt->execute()) {
        header("Location: view_suppliers.php?success=added");
        exit();
    } else {
        $error = "Error adding supplier: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<?php include '../includes/header.php'; ?>

<h2>Add New Supplier</h2>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Supplier Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Supplier</button>
            <a href="view_suppliers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>