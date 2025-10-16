<?php
/**
 * Simple Admin Setup Script
 * Creates admin user and required tables
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];
    
    // Create user_profiles table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        preferences TEXT,
        timezone TEXT DEFAULT 'UTC',
        language TEXT DEFAULT 'en',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    $results[] = "Created user_profiles table";
    
    // Create user_sessions table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token_hash TEXT NOT NULL,
        expires_at TEXT NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    $results[] = "Created user_sessions table";
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@adilgfx.com']);
    $adminUser = $stmt->fetch();
    
    if (!$adminUser) {
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (email, password, name, role, status, email_verified) 
            VALUES (?, ?, ?, 'admin', 'active', 1)
        ");
        $stmt->execute(['admin@adilgfx.com', $hashedPassword, 'Adil GFX Admin']);
        $adminId = $db->lastInsertId();
        $results[] = "Created admin user (ID: $adminId)";
    } else {
        $adminId = $adminUser['id'];
        $results[] = "Admin user already exists (ID: $adminId)";
    }
    
    // Create user profile for admin
    $stmt = $db->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$adminId]);
    if (!$stmt->fetch()) {
        $defaultPreferences = json_encode([
            'theme' => 'light',
            'notifications' => true,
            'language' => 'en',
            'timezone' => 'UTC'
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO user_profiles (user_id, preferences) 
            VALUES (?, ?)
        ");
        $stmt->execute([$adminId, $defaultPreferences]);
        $results[] = "Created admin user profile";
    } else {
        $results[] = "Admin user profile already exists";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin setup completed successfully',
        'results' => $results,
        'admin_credentials' => [
            'email' => 'admin@adilgfx.com',
            'password' => 'admin123',
            'url' => 'https://adilcreator.com/dashboard'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Setup failed: ' . $e->getMessage()
    ]);
}
?>