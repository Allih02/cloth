<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing Shop Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Clothing Shop Management</h1>
            <nav>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="../products/view_products.php"><i class="fas fa-tshirt"></i> Products</a></li>
                    <li><a href="../stock/view_stock.php"><i class="fas fa-boxes"></i> Stock</a></li>
                    <li><a href="../sales/make_sale.php"><i class="fas fa-shopping-cart"></i> Sales</a></li>
                    <li><a href="../suppliers/view_suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                    <li><a href="../reports/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">