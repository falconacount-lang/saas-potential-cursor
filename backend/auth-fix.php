<?php
/**
 * Auth Fix - Creates missing tables and admin user
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Fixing authentication...\n";
    
    // Create user_profiles table
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
    echo "✅ Created user_profiles table\n";
    
    // Create user_sessions table
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
    echo "✅ Created user_sessions table\n";
    
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
        echo "✅ Created admin user (ID: $adminId)\n";
    } else {
        $adminId = $adminUser['id'];
        echo "✅ Admin user exists (ID: $adminId)\n";
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
        echo "✅ Created admin user profile\n";
    } else {
        echo "✅ Admin user profile exists\n";
    }
    
    echo "\n🎉 Authentication fix completed!\n";
    echo "📧 Admin login: admin@adilgfx.com\n";
    echo "🔑 Password: admin123\n";
    echo "🌐 Admin panel: https://adilcreator.com/dashboard\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>