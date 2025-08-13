<?php
// Test database connection and user setup
require_once 'includes/config.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($conn->ping()) {
    echo "✅ Database connection is working!<br><br>";
} else {
    echo "❌ Database connection failed: " . $conn->error . "<br><br>";
    exit;
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ Users table exists<br>";
} else {
    echo "❌ Users table does not exist<br>";
    echo "Please run the database_schema.sql file<br><br>";
    exit;
}

// Check if admin user exists
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Admin user exists<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password Hash: " . substr($user['password'], 0, 20) . "...<br><br>";
    
    // Test password verification
    if (password_verify('admin123', $user['password'])) {
        echo "✅ Password 'admin123' is correct!<br>";
    } else {
        echo "❌ Password 'admin123' does not match<br>";
        echo "Let's reset the password...<br>";
        
        // Reset password
        $new_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->bind_param("s", $new_password);
        
        if ($stmt->execute()) {
            echo "✅ Password has been reset to 'admin123'<br>";
        } else {
            echo "❌ Failed to reset password: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
} else {
    echo "❌ Admin user does not exist<br>";
    echo "Creating admin user...<br>";
    
    // Create admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';
    $email = 'admin@clothingshop.com';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $email);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "❌ Failed to create admin user: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

// List all users
echo "<br><h3>All Users in Database:</h3>";
$result = $conn->query("SELECT username, role, email, created_at FROM users");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Role</th><th>Email</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found in database.";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #333;
}
table {
    background: white;
    border-collapse: collapse;
}
th {
    background: #667eea;
    color: white;
    padding: 10px;
}
td {
    padding: 8px;
}
</style>