<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Check if there are any sales for this product
$stmt = $conn->prepare("SELECT COUNT(*) FROM sales WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($sales_count);
$stmt->fetch();
$stmt->close();

if ($sales_count > 0) {
    header("Location: view_products.php?error=Product cannot be deleted because it has sales records");
    exit();
}

// Delete the product
$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    header("Location: view_products.php?success=deleted");
} else {
    header("Location: view_products.php?error=Error deleting product");
}

$stmt->close();
exit();
?>