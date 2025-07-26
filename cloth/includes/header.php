<?php
// Check current page for active navigation
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    
    if (strpos($page, $currentDir) !== false || strpos($page, $currentPage) !== false) {
        return 'active';
    }
    return '';
}

// Get the correct base path for assets and links
function getCorrectBasePath() {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(trim($scriptPath, '/'), '/');
    
    if ($depth <= 1) {
        return './';
    } else {
        return str_repeat('../', $depth - 1);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Super Sub Jersey Store</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo getCorrectBasePath(); ?>assets/favicon.ico">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap Icons (fallback) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Responsive Styles -->
    <style>
        /* CSS Variables for Consistent Theming */
        :root {
            /* Primary Colors */
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --primary-light: #7c8ef5;
            --secondary-color: #764ba2;
            
            /* Status Colors */
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            /* Background Colors */
            --bg-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --bg-secondary: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --bg-dark: #1a202c;
            
            /* Text Colors */
            --text-primary: #1a202c;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --text-light: #ffffff;
            
            /* Border and Shadow */
            --border-color: #e2e8f0;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Typography */
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-size-4xl: 2.25rem;
            
            /* Sidebar */
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            
            /* Transitions */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Responsive Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            transition: all var(--transition-normal);
            overflow-y: auto;
            transform: translateX(0);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: var(--spacing-xl) var(--spacing-lg);
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
        }

        .sidebar-header h1 {
            color: var(--text-light);
            font-size: var(--font-size-xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            transition: all var(--transition-normal);
        }

        .sidebar.collapsed .sidebar-header h1 {
            font-size: 0;
            margin: 0;
        }

        .sidebar-header .subtitle {
            color: #bdc3c7;
            font-size: var(--font-size-sm);
            transition: all var(--transition-normal);
        }

        .sidebar.collapsed .sidebar-header .subtitle {
            font-size: 0;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: absolute;
            top: var(--spacing-md);
            right: var(--spacing-md);
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--text-light);
            padding: var(--spacing-sm);
            border-radius: 50%;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: var(--spacing-md);
            left: var(--spacing-md);
            z-index: 1001;
            background: var(--primary-color);
            color: var(--text-light);
            border: none;
            padding: var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            box-shadow: var(--shadow-md);
            width: 50px;
            height: 50px;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-lg);
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: var(--spacing-lg) 0;
        }

        .nav-item {
            margin: 0 var(--spacing-md) var(--spacing-xs);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            color: #bdc3c7;
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            transition: all var(--transition-fast);
            position: relative;
            gap: var(--spacing-md);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            transform: translateX(5px);
            text-decoration: none;
        }

        .nav-link.active {
            background: var(--primary-color);
            color: var(--text-light);
            box-shadow: var(--shadow-md);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: var(--font-size-lg);
        }

        .nav-link span {
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }

        /* Navigation Badge */
        .nav-badge {
            position: absolute;
            top: 50%;
            right: var(--spacing-md);
            transform: translateY(-50%);
            background: var(--danger-color);
            color: var(--text-light);
            font-size: var(--font-size-xs);
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }

        /* User Section */
        .user-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--spacing-lg);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            color: var(--text-light);
            padding: var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            transition: all var(--transition-fast);
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: var(--font-size-lg);
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: var(--font-size-sm);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: var(--font-size-xs);
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .logout-btn {
            background: none;
            border: none;
            color: #f87171;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            transition: all var(--transition-fast);
        }

        .logout-btn:hover {
            background: rgba(248, 113, 113, 0.1);
            transform: scale(1.1);
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: var(--spacing-xl);
            transition: margin-left var(--transition-normal);
            background: var(--bg-light);
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Page Content */
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Cards */
        .card {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: var(--spacing-lg);
            transition: all var(--transition-fast);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .card-header {
            padding: var(--spacing-lg) var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .card-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .card-title i {
            color: var(--primary-color);
        }

        .card-body {
            padding: var(--spacing-xl);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-lg);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            line-height: 1.5;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: var(--text-light);
        }

        .btn-secondary {
            background: var(--text-secondary);
            color: var(--text-light);
        }

        .btn-success {
            background: var(--success-color);
            color: var(--text-light);
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--text-light);
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--text-light);
        }

        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: var(--font-size-xs);
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-lg);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            font-size: var(--font-size-sm);
        }

        .table th {
            background: var(--bg-primary);
            color: var(--text-light);
            padding: var(--spacing-md) var(--spacing-lg);
            text-align: left;
            font-weight: 600;
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tr:hover {
            background: rgba(102, 126, 234, 0.02);
        }

        /* Forms */
        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 500;
            color: var(--text-primary);
            font-size: var(--font-size-sm);
        }

        .form-control {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-sm);
            transition: all var(--transition-fast);
            background: var(--bg-white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Alerts */
        .alert {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--border-radius-sm);
            margin-bottom: var(--spacing-lg);
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: #065f46;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: #991b1b;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-color: var(--warning-color);
            color: #92400e;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: var(--info-color);
            color: #1e40af;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        .animate-slideInUp { animation: slideInUp 0.5s ease-out; }

        /* Responsive Breakpoints */

        /* Large Desktop (1280px and up) */
        @media (min-width: 1280px) {
            .main-content {
                padding: var(--spacing-2xl);
            }
        }

        /* Tablet (768px and up) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
                padding: var(--spacing-lg);
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
            }
        }

        /* Mobile (767px and below) */
        @media (max-width: 767px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .sidebar {
                width: 100%;
                max-width: 300px;
                transform: translateX(-100%);
                z-index: 1002;
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: var(--spacing-lg) var(--spacing-md);
                padding-top: calc(var(--spacing-lg) + 70px);
            }
            
            .card-header {
                padding: var(--spacing-md);
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
            }
            
            .card-body {
                padding: var(--spacing-md);
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                margin-bottom: var(--spacing-sm);
            }
            
            .btn:last-child {
                margin-bottom: 0;
            }
            
            .table th,
            .table td {
                padding: var(--spacing-sm);
                font-size: var(--font-size-xs);
            }
            
            .mobile-hide {
                display: none;
            }
        }

        /* Extra Small Mobile (480px and below) */
        @media (max-width: 480px) {
            .main-content {
                padding: var(--spacing-md) var(--spacing-sm);
                padding-top: calc(var(--spacing-md) + 70px);
            }
            
            .card-header,
            .card-body {
                padding: var(--spacing-sm);
            }
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .d-flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .gap-1 { gap: var(--spacing-sm); }
        .gap-2 { gap: var(--spacing-md); }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: var(--spacing-sm); }
        .mb-2 { margin-bottom: var(--spacing-md); }
        .mb-3 { margin-bottom: var(--spacing-lg); }
        .mt-1 { margin-top: var(--spacing-sm); }
        .mt-2 { margin-top: var(--spacing-md); }
        .mt-3 { margin-top: var(--spacing-lg); }
        
        /* Text Colors */
        .text-primary { color: var(--primary-color); }
        .text-success { color: var(--success-color); }
        .text-warning { color: var(--warning-color); }
        .text-danger { color: var(--danger-color); }
        .text-muted { color: var(--text-muted); }
    </style>
    
    <!-- Page-specific styles -->
    <?php if (isset($additional_styles)): ?>
        <style><?php echo $additional_styles; ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link" style="position: absolute; top: -40px; left: 6px; background: var(--primary-color); color: var(--text-light); padding: 8px; text-decoration: none; border-radius: 4px; z-index: 10000;">Skip to main content</a>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <h1>Super Sub</h1>
            <p class="subtitle">Jersey Store</p>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <!-- Navigation Menu -->
        <div class="sidebar-nav">
            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                
                <!-- Dashboard -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>index.php" 
                       class="nav-link <?php echo isActivePage('index.php'); ?>" 
                       data-tooltip="Dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <!-- Products -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>products/view_products.php" 
                       class="nav-link <?php echo isActivePage('products'); ?>" 
                       data-tooltip="Products">
                        <i class="fas fa-tshirt"></i>
                        <span>Products</span>
                    </a>
                </div>
                
                <!-- Stock Management -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>stock/stock_dashboard.php" 
                       class="nav-link <?php echo isActivePage('stock'); ?>" 
                       data-tooltip="Stock Management">
                        <i class="fas fa-boxes"></i>
                        <span>Stock</span>
                        <?php
                        // Check for low stock items safely
                        $low_stock_count = 0;
                        if (isset($conn) && $conn) {
                            try {
                                $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10");
                                if ($result) {
                                    $low_stock_count = $result->fetch_row()[0];
                                }
                            } catch (Exception $e) {
                                // Silently handle error
                            }
                        }
                        if ($low_stock_count > 0):
                        ?>
                        <span class="nav-badge"><?php echo $low_stock_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Sales -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>sales/make_sale.php" 
                       class="nav-link <?php echo isActivePage('sales'); ?>" 
                       data-tooltip="Sales">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sales</span>
                    </a>
                </div>
                
                <!-- Suppliers -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>suppliers/view_suppliers.php" 
                       class="nav-link <?php echo isActivePage('suppliers'); ?>" 
                       data-tooltip="Suppliers">
                        <i class="fas fa-truck"></i>
                        <span>Suppliers</span>
                    </a>
                </div>
                
                <!-- Reports -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>reports/reports.php" 
                       class="nav-link <?php echo isActivePage('reports'); ?>" 
                       data-tooltip="Reports">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>
                
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <!-- Admin Only Sections -->
                
                <!-- User Management -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>admin/manage_users.php" 
                       class="nav-link <?php echo isActivePage('admin'); ?>" 
                       data-tooltip="User Management">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </div>
                
                <!-- System Settings -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>admin/settings.php" 
                       class="nav-link <?php echo isActivePage('settings'); ?>" 
                       data-tooltip="System Settings">
                        <i class="fas fa-cogs"></i>
                        <span>Settings</span>
                    </a>
                </div>
                
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Login/Register for non-authenticated users -->
                <div class="nav-item">
                    <a href="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>auth/login.php" 
                       class="nav-link <?php echo isActivePage('login'); ?>" 
                       data-tooltip="Login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- User Section -->
        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
        <div class="user-section">
            <div class="user-info" data-tooltip="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) . ' (' . ucfirst($_SESSION['role'] ?? 'User') . ')' : 'User Account'; ?>">
                <div class="user-avatar">
                    <?php echo isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'U'; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></div>
                    <div class="user-role"><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User'; ?></div>
                </div>
                <form method="POST" action="<?php echo function_exists('getBasePath') ? getBasePath() : './'; ?>auth/logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    
    <!-- Main Content Area -->
    <main class="main-content" id="main-content">
        <!-- Page Content Wrapper -->
        <div class="page-content">
            
            <!-- Page Header (optional) -->
            <?php if (isset($page_header) && $page_header): ?>
            <div class="card animate-fadeIn" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div style="flex: 1;">
                        <?php if (isset($page_title)): ?>
                        <h1 style="margin: 0; font-size: 2rem; font-weight: 700; display: flex; align-items: center; gap: 1rem;">
                            <?php if (isset($page_icon)): ?>
                            <i class="<?php echo $page_icon; ?>" style="color: var(--primary-color);"></i>
                            <?php endif; ?>
                            <?php echo $page_title; ?>
                        </h1>
                        <?php endif; ?>
                        
                        <?php if (isset($page_description)): ?>
                        <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 1.125rem;"><?php echo $page_description; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($page_actions)): ?>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <?php echo $page_actions; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Breadcrumb Navigation (optional) -->
            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
            <nav aria-label="Breadcrumb" style="margin-bottom: 1.5rem;">
                <ol style="display: flex; align-items: center; gap: 0.5rem; list-style: none; padding: 1rem; background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); margin: 0;">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if ($index > 0): ?>
                        <i class="fas fa-chevron-right" style="color: var(--text-muted); font-size: 0.75rem;"></i>
                        <?php endif; ?>
                        
                        <?php if (isset($crumb['url']) && $index !== count($breadcrumbs) - 1): ?>
                        <a href="<?php echo $crumb['url']; ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 500;"><?php echo $crumb['title']; ?></a>
                        <?php else: ?>
                        <span style="color: var(--text-secondary); font-weight: 500;"><?php echo $crumb['title']; ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <?php endif; ?>
            
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info'; ?> animate-slideInUp">
                <i class="fas fa-<?php echo isset($_SESSION['flash_type']) && $_SESSION['flash_type'] === 'success' ? 'check-circle' : (isset($_SESSION['flash_type']) && $_SESSION['flash_type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php 
                echo $_SESSION['flash_message']; 
                unset($_SESSION['flash_message']);
                if (isset($_SESSION['flash_type'])) {
                    unset($_SESSION['flash_type']);
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Success/Error Messages from URL parameters -->
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success animate-slideInUp">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger animate-slideInUp">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['warning'])): ?>
            <div class="alert alert-warning animate-slideInUp">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['warning']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['info'])): ?>
            <div class="alert alert-info animate-slideInUp">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars($_GET['info']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Main Page Content (this is where individual page content will go) -->
            
    <!-- JavaScript for responsive functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize responsive functionality
            initializeResponsiveFeatures();
            
            // Auto-hide flash messages
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                });
            }, 5000);
        });
        
        function initializeResponsiveFeatures() {
            // Sidebar functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            
            // Desktop sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    
                    // Update toggle icon
                    const icon = this.querySelector('i');
                    if (sidebar.classList.contains('collapsed')) {
                        icon.className = 'fas fa-chevron-right';
                    } else {
                        icon.className = 'fas fa-chevron-left';
                    }
                    
                    // Save state
                    try {
                        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                    } catch (e) {
                        // Handle localStorage errors silently
                    }
                });
            }
            
            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('mobile-visible');
                    
                    // Update mobile toggle icon
                    const icon = this.querySelector('i');
                    if (sidebar.classList.contains('mobile-visible')) {
                        icon.className = 'fas fa-times';
                        document.body.style.overflow = 'hidden'; // Prevent background scrolling
                    } else {
                        icon.className = 'fas fa-bars';
                        document.body.style.overflow = '';
                    }
                });
            }
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 767) {
                    if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        sidebar.classList.remove('mobile-visible');
                        document.body.style.overflow = '';
                        if (mobileMenuToggle) {
                            mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
                        }
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 767) {
                    sidebar.classList.remove('mobile-visible');
                    document.body.style.overflow = '';
                    if (mobileMenuToggle) {
                        mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
                    }
                }
            });
            
            // Restore sidebar state on desktop
            try {
                if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 767) {
                    sidebar.classList.add('collapsed');
                    if (sidebarToggle) {
                        sidebarToggle.querySelector('i').className = 'fas fa-chevron-right';
                    }
                }
            } catch (e) {
                // Handle localStorage errors silently
            }
            
            // Form enhancements
            enhanceForms();
            
            // Table enhancements
            enhanceTables();
            
            // Button loading states
            enhanceButtons();
            
            // Tooltip functionality (simple implementation)
            initializeTooltips();
        }
        
        function enhanceForms() {
            // Add loading states to submit buttons
            const forms = document.querySelectorAll('form');
            
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn && this.checkValidity()) {
                        submitBtn.style.opacity = '0.7';
                        submitBtn.style.pointerEvents = 'none';
                        submitBtn.disabled = true;
                        
                        // Add loading text if it's a button
                        if (submitBtn.tagName === 'BUTTON') {
                            const originalText = submitBtn.textContent;
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                            
                            // Restore on page unload (in case form submission fails)
                            window.addEventListener('beforeunload', function() {
                                submitBtn.innerHTML = originalText;
                                submitBtn.style.opacity = '';
                                submitBtn.style.pointerEvents = '';
                                submitBtn.disabled = false;
                            });
                        }
                    }
                });
                
                // Real-time validation
                const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
                inputs.forEach(function(input) {
                    input.addEventListener('blur', function() {
                        if (this.value.trim() === '' && this.hasAttribute('required')) {
                            this.style.borderColor = 'var(--danger-color)';
                        } else if (this.checkValidity()) {
                            this.style.borderColor = 'var(--success-color)';
                        } else {
                            this.style.borderColor = 'var(--danger-color)';
                        }
                    });
                    
                    input.addEventListener('input', function() {
                        this.style.borderColor = '';
                    });
                });
            });
        }
        
        function enhanceTables() {
            // Add responsive features to tables
            const tables = document.querySelectorAll('.table');
            
            tables.forEach(function(table) {
                // Add mobile-friendly scrolling indicators
                const wrapper = table.closest('.table-responsive');
                if (wrapper) {
                    wrapper.addEventListener('scroll', function() {
                        // Add visual indicators for scrollable content
                        if (this.scrollLeft > 0) {
                            this.classList.add('scrolled-left');
                        } else {
                            this.classList.remove('scrolled-left');
                        }
                        
                        if (this.scrollLeft < (this.scrollWidth - this.clientWidth)) {
                            this.classList.add('scrolled-right');
                        } else {
                            this.classList.remove('scrolled-right');
                        }
                    });
                }
                
                // Add row hover effects
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    row.addEventListener('mouseenter', function() {
                        this.style.transform = 'scale(1.01)';
                        this.style.zIndex = '1';
                    });
                    
                    row.addEventListener('mouseleave', function() {
                        this.style.transform = '';
                        this.style.zIndex = '';
                    });
                });
            });
        }
        
        function enhanceButtons() {
            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            
            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.4);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(function() {
                        if (ripple.parentNode) {
                            ripple.parentNode.removeChild(ripple);
                        }
                    }, 600);
                });
            });
            
            // Add ripple animation
            if (!document.getElementById('ripple-style')) {
                const style = document.createElement('style');
                style.id = 'ripple-style';
                style.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(4);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        }
        
        function initializeTooltips() {
            // Simple tooltip implementation
            const elementsWithTooltips = document.querySelectorAll('[data-tooltip]');
            
            elementsWithTooltips.forEach(function(element) {
                element.addEventListener('mouseenter', function() {
                    if (window.innerWidth > 767 && this.closest('.sidebar.collapsed')) {
                        showTooltip(this, this.getAttribute('data-tooltip'));
                    }
                });
                
                element.addEventListener('mouseleave', function() {
                    hideTooltip();
                });
            });
        }
        
        let tooltipElement = null;
        
        function showTooltip(element, text) {
            hideTooltip();
            
            tooltipElement = document.createElement('div');
            tooltipElement.textContent = text;
            tooltipElement.style.cssText = `
                position: fixed;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                z-index: 2000;
                pointer-events: none;
                white-space: nowrap;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                transform: translateY(-50%);
                opacity: 0;
                transition: opacity 0.2s ease;
            `;
            
            document.body.appendChild(tooltipElement);
            
            const rect = element.getBoundingClientRect();
            tooltipElement.style.left = (rect.right + 10) + 'px';
            tooltipElement.style.top = (rect.top + rect.height / 2) + 'px';
            
            // Fade in
            setTimeout(function() {
                if (tooltipElement) {
                    tooltipElement.style.opacity = '1';
                }
            }, 10);
        }
        
        function hideTooltip() {
            if (tooltipElement) {
                tooltipElement.remove();
                tooltipElement = null;
            }
        }
        
        // Global utility functions
        window.showAlert = function(message, type) {
            type = type || 'info';
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type + ' animate-slideInUp';
            alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-circle' : 'info-circle')) + '"></i> ' + message;
            
            const pageContent = document.querySelector('.page-content');
            if (pageContent) {
                pageContent.insertBefore(alertDiv, pageContent.firstChild);
                
                setTimeout(function() {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        if (alertDiv.parentNode) {
                            alertDiv.parentNode.removeChild(alertDiv);
                        }
                    }, 300);
                }, 5000);
            }
        };
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + navigation shortcuts
            if (e.altKey) {
                switch(e.key.toLowerCase()) {
                    case 's':
                        e.preventDefault();
                        const salesLink = document.querySelector('a[href*="sales/make_sale"]');
                        if (salesLink) salesLink.click();
                        break;
                    case 'p':
                        e.preventDefault();
                        const productsLink = document.querySelector('a[href*="products/view_products"]');
                        if (productsLink) productsLink.click();
                        break;
                    case 'i':
                        e.preventDefault();
                        const stockLink = document.querySelector('a[href*="stock/stock_dashboard"]');
                        if (stockLink) stockLink.click();
                        break;
                    case 'r':
                        e.preventDefault();
                        const reportsLink = document.querySelector('a[href*="reports/reports"]');
                        if (reportsLink) reportsLink.click();
                        break;
                }
            }
            
            // Escape to close mobile menu
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                if (sidebar && sidebar.classList.contains('mobile-visible')) {
                    sidebar.classList.remove('mobile-visible');
                    document.body.style.overflow = '';
                    if (mobileMenuToggle) {
                        mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
                    }
                }
            }
        });
        
        // Set active navigation based on current page
        function setActiveNavigation() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href.split('/').pop())) {
                    link.classList.add('active');
                }
            });
        }
        
        // Initialize
        setActiveNavigation();
        
        // Handle page visibility changes (for performance)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, pause any animations
                document.body.style.animationPlayState = 'paused';
            } else {
                // Page is visible, resume animations
                document.body.style.animationPlayState = 'running';
            }
        });
    </script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <script><?php echo $page_scripts; ?></script>
    <?php endif; ?>