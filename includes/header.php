<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Sub Jersey Store Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getBasePath(); ?>assests/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 2rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
        }

        .sidebar-header h1 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .sidebar-header h1 {
            font-size: 0;
            margin: 0;
        }

        .sidebar-header .subtitle {
            color: #bdc3c7;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .sidebar-header .subtitle {
            font-size: 0;
        }

        .sidebar-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #fff;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .sidebar-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .sidebar-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        /* Navigation Menu */
        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: #3498db;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: #fff;
            border-left-color: #3498db;
        }

        .nav-link i {
            width: 20px;
            margin-right: 1rem;
            text-align: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-link span {
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
            margin: 0;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 1rem;
        }

        .sidebar.collapsed .nav-link i {
            margin: 0;
        }

        /* Badge for notifications */
        .nav-badge {
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .nav-badge {
            display: none;
        }

        /* User Section */
        .user-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: #3498db;
            transform: translateX(5px);
        }

        .user-avatar {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #fff;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .user-name {
            color: inherit;
            font-weight: 600;
            font-size: 0.9rem;
            line-height: 1.2;
            margin-bottom: 0.1rem;
            transition: all 0.3s ease;
        }

        .user-role {
            color: #95a5a6;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .user-info {
            justify-content: center;
            padding: 1rem;
        }

        .sidebar.collapsed .user-avatar {
            margin: 0;
        }

        .sidebar.collapsed .user-details {
            opacity: 0;
            width: 0;
            margin: 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: rgba(231, 76, 60, 0.2);
            border: none;
            color: #e74c3c;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
            position: relative;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
            color: #fff;
            border-left-color: #e74c3c;
            transform: translateX(5px);
        }

        .logout-btn i {
            width: 20px;
            margin-right: 1rem;
            text-align: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .logout-btn span {
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .sidebar.collapsed .logout-btn {
            justify-content: center;
            padding: 1rem;
        }

        .sidebar.collapsed .logout-btn span {
            opacity: 0;
            width: 0;
            margin: 0;
        }

        .sidebar.collapsed .logout-btn i {
            margin: 0;
        }

        /* Tooltip for collapsed sidebar */
        .sidebar.collapsed .nav-link:hover::after,
        .sidebar.collapsed .user-info:hover::after,
        .sidebar.collapsed .logout-btn:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.85rem;
            white-space: nowrap;
            z-index: 1001;
            animation: fadeIn 0.2s ease;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Content Styles */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-card.primary {
            border-left: 5px solid #3498db;
        }

        .stat-card.success {
            border-left: 5px solid #2ecc71;
        }

        .stat-card.warning {
            border-left: 5px solid #f39c12;
        }

        .stat-card.danger {
            border-left: 5px solid #e74c3c;
        }

        .stat-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .stat-card p {
            font-size: 2rem;
            font-weight: bold;
            color: #34495e;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e1e8ed;
        }

        .table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border-color: #2ecc71;
            color: #27ae60;
        }

        .alert-danger {
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
            background: rgba(23, 162, 184, 0.1);
            border-color: #17a2b8;
            color: #138496;
        }

        /* Text Colors */
        .text-success {
            color: #2ecc71 !important;
        }

        .text-warning {
            color: #f39c12 !important;
        }

        .text-danger {
            color: #e74c3c !important;
        }

        .text-info {
            color: #17a2b8 !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .text-primary {
            color: #667eea !important;
        }

        /* Mobile Navigation Trigger */
        .mobile-nav-trigger {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1051; /* Higher than modal */
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            display: none;
        }

        .mobile-menu-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .mobile-menu-btn i {
            font-size: 18px;
        }

        /* Enhanced Mobile Responsive Design */
        @media (max-width: 1024px) {
            .mobile-nav-trigger {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: var(--z-modal);
                width: 280px;
                transition: transform var(--transition-normal);
            }

            .sidebar.collapsed {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                margin-top: 70px; /* Account for mobile nav trigger height */
                transition: margin-left var(--transition-normal);
            }

            .main-content.expanded {
                margin-left: 0;
            }

            /* Mobile overlay */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: calc(var(--z-modal) - 1);
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-normal);
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: var(--space-lg);
            }

            .card-header {
                flex-direction: column;
                gap: var(--space-lg);
                align-items: stretch;
                text-align: center;
            }

            .table-responsive {
                font-size: var(--text-sm);
            }

            /* Mobile-friendly button spacing */
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: var(--space-sm);
            }

            .btn-group .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .mobile-nav-trigger {
                padding: var(--space-sm) var(--space-md);
            }

            .mobile-menu-btn {
                padding: var(--space-sm) var(--space-md);
                font-size: var(--text-xs);
            }

            .main-content {
                margin-top: 60px; /* Smaller height for mobile */
                padding: var(--space-md);
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }

            .card-header {
                padding: var(--space-lg);
            }

            .card-body {
                padding: var(--space-lg);
            }

            .table th, .table td {
                padding: var(--space-sm);
                font-size: var(--text-xs);
            }

            /* Hide less important table columns on mobile */
            .table .d-none-mobile {
                display: none;
            }

            /* Responsive forms */
            .form-row {
                flex-direction: column;
                gap: var(--space-md);
            }

            .btn:not(.btn-sm) {
                padding: var(--space-lg) var(--space-xl);
                font-size: var(--text-base);
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: var(--space-md);
                padding-right: var(--space-md);
            }

            .card {
                margin-bottom: var(--space-lg);
            }

            .card-header {
                padding: var(--space-md);
            }

            .card-body {
                padding: var(--space-md);
            }

            .stats-container {
                gap: var(--space-sm);
            }

            .stat-card {
                padding: var(--space-lg);
                min-height: auto;
            }

            .btn {
                font-size: var(--text-sm);
                padding: var(--space-md) var(--space-lg);
            }

            /* Ultra-compact table for very small screens */
            .table th, .table td {
                padding: var(--space-xs);
                font-size: var(--text-xs);
            }
        }

        /* Tablet-specific optimizations */
        @media (min-width: 768px) and (max-width: 1023px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Large desktop optimizations */
        @media (min-width: 1440px) {
            .container {
                max-width: 1400px;
            }

            .sidebar {
                width: 320px;
            }

            .main-content {
                margin-left: 320px;
                padding: var(--space-4xl);
            }

            .stats-container {
                grid-template-columns: repeat(4, 1fr);
                gap: var(--space-2xl);
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Trigger -->
    <div class="mobile-nav-trigger" id="mobileNavTrigger">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </button>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <button class="sidebar-close d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
            <h1><i class="fas fa-store"></i> Super Sub Jersey Store</h1>
            <div class="subtitle">Management System</div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>index.php" class="nav-link" data-tooltip="Dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>products/view_products.php" class="nav-link" data-tooltip="Products">
                    <i class="fas fa-tshirt"></i>
                    <span>Products</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>stock/stock_dashboard.php" class="nav-link" data-tooltip="Stock Management">
                    <i class="fas fa-boxes"></i>
                    <span>Stock</span>
                    <?php
                    // Check for low stock items
                    $low_stock_count = 0;
                    if (isset($conn)) {
                        $result = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 10");
                        if ($result) {
                            $low_stock_count = $result->fetch_row()[0];
                        }
                    }
                    if ($low_stock_count > 0):
                    ?>
                    <span class="nav-badge"><?php echo $low_stock_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>sales/make_sale.php" class="nav-link" data-tooltip="Sales">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>suppliers/view_suppliers.php" class="nav-link" data-tooltip="Suppliers">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo getBasePath(); ?>reports/reports.php" class="nav-link" data-tooltip="Reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
        </nav>

        <!-- User Section -->
        <div class="user-section">
            <?php if (isLoggedIn()): ?>
                <div class="user-info" data-tooltip="<?php echo htmlspecialchars($_SESSION['username']) . ' (' . ucfirst($_SESSION['role']) . ')'; ?>">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
                    </div>
                </div>
                <a href="<?php echo getBasePath(); ?>auth/logout.php" class="logout-btn" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="<?php echo getBasePath(); ?>auth/login.php" class="logout-btn" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71;" data-tooltip="Login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <div class="container">

    <script>
        // Enhanced Sidebar toggle functionality with mobile support
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = window.innerWidth <= 1024;
            
            if (isMobile) {
                // Mobile behavior: slide in/out with overlay
                sidebar.classList.toggle('collapsed');
                overlay.classList.toggle('show');
                
                // Prevent body scroll when sidebar is open
                if (sidebar.classList.contains('collapsed')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            } else {
                // Desktop behavior: expand/collapse
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Store sidebar state in localStorage for desktop
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        }

        // Close sidebar when clicking overlay (mobile)
        function closeSidebarOnOverlay() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Enhanced responsive sidebar management
        function handleSidebarResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            const mobileNavTrigger = document.getElementById('mobileNavTrigger');
            const isMobile = window.innerWidth <= 1024;
            
            if (isMobile) {
                // Mobile: hide sidebar by default, show mobile trigger
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
                if (mobileNavTrigger) {
                    mobileNavTrigger.style.display = 'block';
                }
            } else {
                // Desktop: restore saved state, hide mobile trigger
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                }
                overlay.classList.remove('show');
                document.body.style.overflow = '';
                if (mobileNavTrigger) {
                    mobileNavTrigger.style.display = 'none';
                }
            }
        }

        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initial setup
            handleSidebarResize();
            
            // Set active navigation item
            setActiveNavItem();
            
            // Add overlay click handler
            document.getElementById('sidebarOverlay').addEventListener('click', closeSidebarOnOverlay);
            
            // Add escape key handler for mobile
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (window.innerWidth <= 1024 && sidebar.classList.contains('collapsed')) {
                        closeSidebarOnOverlay();
                    }
                }
            });

            // Ensure mobile navigation is visible on mobile devices
            const isMobile = window.innerWidth <= 1024;
            const mobileNavTrigger = document.getElementById('mobileNavTrigger');
            if (isMobile && mobileNavTrigger) {
                mobileNavTrigger.style.display = 'block';
            }
        });

        // Set active navigation item based on current page
        function setActiveNavItem() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                
                // Check if current path matches the link
                const linkPath = new URL(link.href).pathname;
                if (currentPath.includes(linkPath.split('/').pop().replace('.php', ''))) {
                    link.classList.add('active');
                }
            });

            // Special case for dashboard
            if (currentPath.endsWith('index.php') || currentPath.endsWith('/')) {
                document.querySelector('a[href*="index.php"]').classList.add('active');
            }
            
            // Special case for stock pages - all should highlight "Stock" menu item
            if (currentPath.includes('/stock/')) {
                document.querySelector('a[href*="stock_dashboard.php"]').classList.add('active');
            }
        }

        // Enhanced window resize handling
        let resizeTimeout;
        window.addEventListener('resize', function() {
            // Debounce resize events for better performance
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(handleSidebarResize, 150);
        });

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                ${message}
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 500px;
                animation: slideIn 0.5s ease;
                cursor: pointer;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
            
            // Click to dismiss
            notification.addEventListener('click', () => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
        }

        // Loading state for buttons
        function setLoadingState(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.setAttribute('data-original-text', button.innerHTML);
                button.innerHTML = '<span class="loading"></span> Loading...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
            }
        }

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        // Validate email
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Validate phone number
        function validatePhone(phone) {
            const re = /^[\+]?[1-9][\d]{0,15}$/;
            return re.test(phone.replace(/\s/g, ''));
        }
    </script>