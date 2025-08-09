<?php
// Fixed login.php - Replace your existing auth/login.php with this version
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Debug: Show what we received
    $debug_info .= "Received username: '" . htmlspecialchars($username) . "'<br>";
    $debug_info .= "Password length: " . strlen($password) . "<br>";
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Prepare statement to find user
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $debug_info .= "Query executed, found " . $result->num_rows . " user(s)<br>";
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $debug_info .= "User found: " . htmlspecialchars($user['username']) . " (ID: " . $user['user_id'] . ")<br>";
                $debug_info .= "Stored password hash: " . substr($user['password'], 0, 20) . "...<br>";
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $debug_info .= "Password verification: SUCCESS<br>";
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    $debug_info .= "Session variables set<br>";
                    
                    // Success! Redirect to dashboard
                    header("Location: ../index.php");
                    exit();
                } else {
                    $debug_info .= "Password verification: FAILED<br>";
                    
                    // Test if password might be plain text (for debugging)
                    if ($password === $user['password']) {
                        $debug_info .= "Password appears to be stored as plain text!<br>";
                        $error = "Password stored incorrectly. Please reset your password.";
                    } else {
                        $error = "Invalid username or password.";
                    }
                }
            } else {
                $debug_info .= "No user found with username: " . htmlspecialchars($username) . "<br>";
                $error = "Invalid username or password.";
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
            $debug_info .= "Exception: " . $e->getMessage() . "<br>";
        }
    }
}

// Check if we need to create default admin user
if (!$error && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $check_admin = $conn->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        if ($check_admin && $check_admin->fetch_row()[0] == 0) {
            // Create default admin user
            $admin_username = 'admin';
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $admin_role = 'admin';
            $admin_email = 'admin@clothingshop.com';
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $admin_username, $admin_password, $admin_role, $admin_email);
            
            if ($stmt->execute()) {
                $debug_info .= "Default admin user created successfully!<br>";
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently handle this error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Super Sub Jersey Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assests/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .login-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-body {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border-color: #e74c3c;
            color: #c0392b;
        }

        .alert-info {
            background: rgba(52, 152, 219, 0.1);
            border-color: #3498db;
            color: #2980b9;
            font-size: 0.85rem;
        }

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
            transform: translateY(-2px);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .demo-credentials {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }

        .demo-credentials h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .demo-credentials p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }

        .demo-credentials .note {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #495057;
        }

        .debug-info h4 {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .quick-login {
            text-align: center;
            margin-top: 1rem;
        }

        .quick-login button {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #27ae60;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .quick-login button:hover {
            background: #2ecc71;
            color: white;
        }

        /* Enhanced Mobile Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: var(--space-md);
                align-items: stretch;
            }

            .login-container {
                max-width: none;
                border-radius: var(--radius-lg);
                margin: auto 0;
            }

            .login-header {
                padding: var(--space-xl);
            }

            .login-header h2 {
                font-size: var(--text-xl);
            }

            .login-body {
                padding: var(--space-xl);
            }

            .form-control {
                padding: var(--space-lg);
                font-size: var(--text-base);
            }

            .btn {
                padding: var(--space-lg);
                font-size: var(--text-base);
            }

            .demo-credentials {
                padding: var(--space-lg);
            }

            .demo-credentials h4 {
                font-size: var(--text-sm);
            }

            .demo-credentials p {
                font-size: var(--text-xs);
            }
        }

        @media (max-width: 480px) {
            body {
                padding: var(--space-sm);
            }

            .login-container {
                border-radius: var(--radius);
            }

            .login-header {
                padding: var(--space-lg);
            }

            .login-header h2 {
                font-size: var(--text-lg);
            }

            .login-header p {
                font-size: var(--text-xs);
            }

            .login-body {
                padding: var(--space-lg);
            }

            .form-group {
                margin-bottom: var(--space-lg);
            }

            .demo-credentials {
                padding: var(--space-md);
            }
        }

        /* Landscape orientation on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding: var(--space-sm);
            }

            .login-container {
                margin: var(--space-sm) auto;
            }

            .login-header {
                padding: var(--space-lg);
            }

            .login-body {
                padding: var(--space-lg);
            }

            .demo-credentials {
                padding: var(--space-md);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-store"></i> Super Sub Jersey Store</h2>
            <p>Management System Login</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="login-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="demo-credentials">
                <h4><i class="fas fa-key"></i> Default Login Credentials:</h4>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
                <p class="note">
                    <i class="fas fa-info-circle"></i> Use these credentials to access the system
                </p>
            </div>
            
            <div class="quick-login">
                <button type="button" onclick="fillDemoCredentials()">
                    <i class="fas fa-magic"></i> Fill Demo Credentials
                </button>
            </div>
            
            <?php if ($debug_info && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="debug-info">
                    <h4><i class="fas fa-bug"></i> Debug Information:</h4>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('login-form');
            const loginBtn = document.getElementById('loginBtn');
            
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if (!username || !password) {
                    e.preventDefault();
                    alert('Please fill in all fields.');
                    return;
                }
                
                // Show loading state
                loginBtn.innerHTML = '<span class="loading"></span> Logging in...';
                loginBtn.disabled = true;
            });
            
            // Auto-focus username field
            document.getElementById('username').focus();
        });
        
        function fillDemoCredentials() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            
            // Add visual feedback
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Filled!';
            button.style.background = '#2ecc71';
            button.style.color = 'white';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 1500);
        }
        
        // Keyboard shortcut for demo credentials
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                fillDemoCredentials();
            }
        });
    </script>
</body>
</html>