<?php
require_once 'includes/config.php';

echo "Testing database connection...<br>";

if ($conn->ping()) {
    echo "Connection is OK!";
} else {
    echo "Error: " . $conn->error;
}

// Test query
$result = $conn->query("SELECT * FROM users LIMIT 1");
if ($result) {
    echo "<br>Users table exists with " . $result->num_rows . " rows";
} else {
    echo "<br>Error with users table: " . $conn->error;
}
?>