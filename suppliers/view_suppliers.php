<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
?>

<?php include '../includes/header.php'; ?>

<h2>Supplier Management</h2>

<div class="card">
    <div class="card-header">
        <h3>All Suppliers</h3>
        <?php if (isAdmin()): ?>
            <a href="add_supplier.php" class="btn btn-success">Add New Supplier</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Supplier <?php echo $_GET['success']; ?> successfully!</div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Date Added</th>
                        <?php if (isAdmin()): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM suppliers ORDER BY name");
                    
                    while ($row = $stmt->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['supplier_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        <?php if (isAdmin()): ?>
                            <td>
                                <a href="edit_supplier.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="delete_supplier.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>