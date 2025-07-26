<?php
// Configuration for logo
$logo_config = [
    'enable_logo' => true,
    'logo_url' => '../assets/images/logo.jpg', // Change this to your logo path
    'logo_alt' => 'Super Sub Jersey Store',
    'logo_width' => '100px',  // Set your preferred width
    'logo_height' => 'auto',  // Set your preferred height
    'fallback_to_icon' => true, // Show icon if logo not found
    'show_company_name' => true, // Show company name below logo
];

// Don't start session here - let config.php handle it
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Check what columns exist in the users table
            $columns_check = $conn->query("DESCRIBE users");
            $columns = [];
            while ($row = $columns_check->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            // Determine which password column to use
            $password_column = in_array('password_hash', $columns) ? 'password_hash' : 'password';
            
            // Check user credentials
            $stmt = $conn->prepare("SELECT user_id, username, $password_column as password_field, role, email FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Verify password (both hashed and plain text)
                $password_valid = password_verify($password, $user['password_field']) || $password === $user['password_field'];
                
                if ($password_valid) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login if column exists
                    if (in_array('last_login', $columns)) {
                        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $update_stmt->bind_param("i", $user['user_id']);
                        $update_stmt->execute();
                    }
                    
                    // Redirect to dashboard
                    header('Location: ../index.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = 'Database error. Please try again.';
        }
    }
}

// Create default admin user if no users exist
try {
    $check_users = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($check_users) {
        $user_count = $check_users->fetch_assoc()['count'];

        if ($user_count == 0) {
            $admin_username = 'admin';
            $admin_password = 'admin123';
            $admin_role = 'admin';
            $admin_email = 'admin@supersub.com';
            
            // Check password column
            $columns_check = $conn->query("DESCRIBE users");
            $columns = [];
            while ($row = $columns_check->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            if (in_array('password_hash', $columns)) {
                $password_to_store = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, email) VALUES (?, ?, ?, ?)");
            } else {
                $password_to_store = $admin_password;
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
            }
            
            $stmt->bind_param("ssss", $admin_username, $password_to_store, $admin_role, $admin_email);
            
            if ($stmt->execute()) {
                $success = 'Default admin account created! Username: admin, Password: admin123';
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    // Silently handle this error
}

// Check if logo exists
$logo_exists = $logo_config['enable_logo'] && file_exists($logo_config['logo_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Super Sub Jersey Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --text-primary: #1a202c;
            --text-secondary: #64748b;
            --text-light: #ffffff;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --transition-normal: 0.3s ease;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }

        /* Animated background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="0.5" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="0.3" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="0.4" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            position: relative;
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--text-light);
            text-align: center;
            padding: 2.5rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Logo Styles */
        .logo-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .company-logo {
            max-width: <?php echo $logo_config['logo_width']; ?>;
            height: <?php echo $logo_config['logo_height']; ?>;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .company-logo:hover {
            transform: scale(1.05) rotate(2deg);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--text-light);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .logo-placeholder:hover {
            transform: scale(1.05) rotate(-2deg);
            background: rgba(255, 255, 255, 0.3);
        }

        .company-info {
            position: relative;
            z-index: 2;
        }

        .company-name {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .company-tagline {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* Enhanced Form Styles */
        .login-body {
            padding: 2.5rem 2rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            padding-left: 3.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all var(--transition-normal);
            background: var(--bg-white);
            color: var(--text-primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 3.5rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
            transition: all var(--transition-normal);
        }

        .form-control:focus + .input-icon {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--text-light);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--text-light);
            transform: translateY(-2px);
        }

        /* Enhanced Alert Styles */
        .alert {
            padding: 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.75rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            animation: slideInUp 0.4s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border-color: var(--success-color);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            border-color: var(--danger-color);
            color: #991b1b;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .checkbox-wrapper label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: 500;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all var(--transition-normal);
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
            transform: translateX(2px);
        }

        .login-footer {
            padding: 2rem;
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .login-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            font-weight: 500;
        }

        .demo-credentials {
            background: var(--bg-white);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.25rem;
            text-align: left;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .demo-credentials h4 {
            color: var(--text-primary);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-credentials p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin: 0.5rem 0;
            font-family: 'Courier New', monospace;
        }

        .loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 3px solid transparent;
            border-top: 3px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 0.75rem;
            }

            .login-container {
                max-width: 100%;
            }

            .login-header {
                padding: 2rem 1.25rem;
            }

            .company-name {
                font-size: 1.8rem;
            }

            .company-logo {
                max-width: 80px;
            }

            .logo-placeholder {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }

            .login-body {
                padding: 2rem 1.5rem;
            }

            .form-control {
                padding: 0.875rem 1rem;
                padding-left: 3rem;
                font-size: 0.95rem;
            }

            .input-icon {
                left: 1rem;
                top: 3.25rem;
                font-size: 1rem;
            }

            .btn {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
            }

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .company-name {
                font-size: 1.6rem;
            }

            .login-body {
                padding: 1.5rem 1rem;
            }

            .demo-credentials {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Enhanced Login Header -->
        <div class="login-header">
            <!-- Logo Section -->
            <div class="logo-container">
                <?php if ($logo_exists): ?>
                    <img src="<?php echo $logo_config['logo_url']; ?>" 
                         alt="<?php echo $logo_config['logo_alt']; ?>" 
                         class="company-logo">
                <?php elseif ($logo_config['fallback_to_icon']): ?>
                    <div class="logo-placeholder">
                        <i class="fas fa-tshirt"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Company Info -->
            <?php if ($logo_config['show_company_name']): ?>
            <div class="company-info">
                <h1 class="company-name">Super Sub</h1>
                <p class="company-tagline">Jersey Store Management</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Login Form -->
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter your username or email"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required
                        autocomplete="username"
                    >
                    <span class="input-icon">
                        <i class="fas fa-user"></i>
                    </span>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <span class="input-icon">
                        <i class="fas fa-lock"></i>
                    </span>
                </div>

                <div class="remember-forgot">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link" onclick="alert('Please contact the administrator to reset your password.'); return false;">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
        </div>

        <!-- Enhanced Footer -->
        <div class="login-footer">
            <p>Welcome to Super Sub Jersey Store Management System</p>
            
            <?php if (isset($user_count) && ($user_count == 0 || $success)): ?>
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Default Admin Account</h4>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
                <p><em>Please change these credentials after first login</em></p>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem;">
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            // Enhanced form validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Reset previous states
                [usernameField, passwordField].forEach(field => {
                    field.style.borderColor = '';
                    field.style.transform = '';
                });
                
                // Validate fields
                if (!usernameField.value.trim()) {
                    showFieldError(usernameField, 'Username or email is required');
                    isValid = false;
                }
                
                if (!passwordField.value.trim()) {
                    showFieldError(passwordField, 'Password is required');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                
                // Show enhanced loading state
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
            });

            // Real-time validation
            [usernameField, passwordField].forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        showFieldError(this, this.getAttribute('placeholder'));
                    } else {
                        clearFieldError(this);
                    }
                });

                field.addEventListener('input', function() {
                    clearFieldError(this);
                });

                field.addEventListener('focus', function() {
                    clearFieldError(this);
                });
            });

            // Enhanced interaction effects
            function showFieldError(field, message) {
                field.style.borderColor = 'var(--danger-color)';
                field.style.transform = 'shake 0.5s ease-in-out';
                
                // Add shake animation
                field.animate([
                    { transform: 'translateX(0)' },
                    { transform: 'translateX(-10px)' },
                    { transform: 'translateX(10px)' },
                    { transform: 'translateX(-10px)' },
                    { transform: 'translateX(10px)' },
                    { transform: 'translateX(0)' }
                ], {
                    duration: 500,
                    easing: 'ease-in-out'
                });
            }

            function clearFieldError(field) {
                field.style.borderColor = '';
                field.style.transform = '';
            }

            // Auto-focus and smart navigation
            if (!usernameField.value) {
                usernameField.focus();
            } else if (!passwordField.value) {
                passwordField.focus();
            }

            // Enter key navigation
            usernameField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    passwordField.focus();
                }
            });

            // Enhanced remember me functionality
            const rememberCheckbox = document.getElementById('remember');
            
            // Load saved credentials
            try {
                if (localStorage.getItem('rememberMe') === 'true') {
                    const savedUsername = localStorage.getItem('savedUsername');
                    if (savedUsername) {
                        usernameField.value = savedUsername;
                        rememberCheckbox.checked = true;
                        passwordField.focus();
                    }
                }
            } catch (e) {
                console.log('LocalStorage not available');
            }

            // Save credentials on form submit
            form.addEventListener('submit', function() {
                try {
                    if (rememberCheckbox.checked) {
                        localStorage.setItem('rememberMe', 'true');
                        localStorage.setItem('savedUsername', usernameField.value);
                    } else {
                        localStorage.removeItem('rememberMe');
                        localStorage.removeItem('savedUsername');
                    }
                } catch (e) {
                    console.log('LocalStorage not available');
                }
            });

            // Enhanced visual feedback
            [usernameField, passwordField].forEach(field => {
                field.addEventListener('focus', function() {
                    this.parentNode.style.transform = 'scale(1.02)';
                    this.parentNode.style.transition = 'transform 0.2s ease';
                });

                field.addEventListener('blur', function() {
                    this.parentNode.style.transform = 'scale(1)';
                });
            });

            // Button hover enhancement
            loginBtn.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                }
            });

            loginBtn.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0) scale(1)';
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + Enter to submit
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });

            // Logo interaction
            const logo = document.querySelector('.company-logo, .logo-placeholder');
            if (logo) {
                logo.addEventListener('click', function() {
                    this.style.animation = 'bounce 0.6s ease';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 600);
                });
            }

            // Add loading overlay for better UX
            function showLoadingOverlay() {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(102, 126, 234, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    backdrop-filter: blur(4px);
                `;
                overlay.innerHTML = `
                    <div style="text-align: center; color: white;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p style="font-size: 1.2rem; font-weight: 600;">Signing you in...</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }

            // Show loading overlay on successful form submission
            form.addEventListener('submit', function(e) {
                if (this.checkValidity()) {
                    setTimeout(showLoadingOverlay, 100);
                }
            });
        });
    </script>
</body>
</html>