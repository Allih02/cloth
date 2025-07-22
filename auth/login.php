<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        
        $stmt->close();
    }
}
?>

<?php?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div class="card" style="width: 100%; max-width: 400px;">
        <div class="card-header">
            <h2><i class="fas fa-sign-in-alt"></i> Login to System</h2>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; color: #2c3e50;">Demo Credentials:</h4>
                <p style="margin: 0.5rem 0;"><strong>Username:</strong> admin</p>
                <p style="margin: 0.5rem 0;"><strong>Password:</strong> admin123</p>
                <p style="margin: 0.5rem 0; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-info-circle"></i> Use these credentials to test the system
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!username || !password) {
            e.preventDefault();
            showNotification('Please fill in all fields.', 'danger');
            return;
        }
        
        // Show loading state
        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
        setLoadingState(submitBtn, true);
    });
    
    // Auto-fill demo credentials
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            showNotification('Demo credentials filled!', 'success');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>