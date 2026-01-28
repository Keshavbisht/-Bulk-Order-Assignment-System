<?php
/**
 * Test Database Connection
 * Run this to verify your database configuration is correct
 */

echo "Testing database connection...\n\n";

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "✅ Database connection successful!\n\n";
    
    // Test query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch();
    echo "📊 Orders in database: " . $result['count'] . "\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM couriers");
    $result = $stmt->fetch();
    echo "📊 Couriers in database: " . $result['count'] . "\n";
    
    echo "\n✅ All checks passed! You're ready to run the server.\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. MySQL is running\n";
    echo "2. Database 'order_assignment_db' exists\n";
    echo "3. Username and password in config/database.php are correct\n";
    echo "4. You have permission to access the database\n";
    exit(1);
}
