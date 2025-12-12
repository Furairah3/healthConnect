<?php
// healthconnect/debug.php
echo "<h1>Debug Information</h1>";

// Test database connection
echo "<h2>Database Test</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=healthconnect_db', 'root', '');
    echo "<p style='color:green;'>✓ Database connected</p>";
    
    // Test tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables found: " . count($tables) . "</p>";
    foreach ($tables as $table) {
        echo "<p>- " . htmlspecialchars($table) . "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test file paths
echo "<h2>File Path Test</h2>";
$paths = [
    'index.php' => __DIR__ . '/index.php',
    'database.php' => __DIR__ . '/app/config/database.php',
    'login.php' => __DIR__ . '/views/auth/login.php',
    'register.php' => __DIR__ . '/views/auth/register.php',
    'api/auth.php' => __DIR__ . '/api/auth.php'
];

foreach ($paths as $name => $path) {
    if (file_exists($path)) {
        echo "<p style='color:green;'>✓ $name exists at: " . htmlspecialchars($path) . "</p>";
    } else {
        echo "<p style='color:red;'>✗ $name NOT found at: " . htmlspecialchars($path) . "</p>";
    }
}

// Test session
echo "<h2>Session Test</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session status: " . session_status() . "</p>";

// Test PHP info
echo "<h2>PHP Configuration</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<p>Error Reporting: " . error_reporting() . "</p>";

// Test URL access
echo "<h2>URL Test Links</h2>";
$urls = [
    'Homepage' => 'http://' . $_SERVER['HTTP_HOST'] . '/healthconnect/index.php',
    'Login' => 'http://' . $_SERVER['HTTP_HOST'] . '/healthconnect/views/auth/login.php',
    'Register' => 'http://' . $_SERVER['HTTP_HOST'] . '/healthconnect/views/auth/register.php',
    'API Test' => 'http://' . $_SERVER['HTTP_HOST'] . '/healthconnect/api/auth.php?action=test'
];

foreach ($urls as $name => $url) {
    echo "<p><a href='" . htmlspecialchars($url) . "' target='_blank'>Test $name</a></p>";
}
?>