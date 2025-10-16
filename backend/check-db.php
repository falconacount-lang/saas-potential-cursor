<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all tables
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check if users table exists and has data
    if (in_array('users', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "\nUsers table has $count records\n";
        
        // Show users
        $stmt = $db->query("SELECT id, email, name, role FROM users");
        $users = $stmt->fetchAll();
        echo "Users:\n";
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, Email: {$user['email']}, Name: {$user['name']}, Role: {$user['role']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>