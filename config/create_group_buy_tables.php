<?php
// Use the main database connection
require_once '../includes/db.php';

try {
    // Disable error reporting and enable exception throwing
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Group Buy Table
    $create_group_buy_table = "
    CREATE TABLE IF NOT EXISTS group_buy (
        group_buy_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        creator_user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        base_price DECIMAL(10,2) NOT NULL,
        min_participants INT DEFAULT 5,
        max_participants INT DEFAULT 10,
        current_participants INT DEFAULT 0,
        start_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
        end_datetime DATETIME,
        discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL,
        group_price DECIMAL(10,2),
        status ENUM('pending', 'active', 'successful', 'failed', 'canceled') DEFAULT 'pending',
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (creator_user_id) REFERENCES users(id)
    )";

    // Create Group Buy Participants Table
    $create_participants_table = "
    CREATE TABLE IF NOT EXISTS group_buy_participants (
        participant_id INT AUTO_INCREMENT PRIMARY KEY,
        group_buy_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'canceled') DEFAULT 'pending',
        payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
        UNIQUE KEY unique_participant (group_buy_id, user_id),
        FOREIGN KEY (group_buy_id) REFERENCES group_buy(group_buy_id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";

    // Create Group Buy Transactions Table
    $create_transactions_table = "
    CREATE TABLE IF NOT EXISTS group_buy_transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        group_buy_id INT NOT NULL,
        user_id INT NOT NULL,
        transaction_type ENUM('join', 'leave', 'payment', 'refund') NOT NULL,
        amount DECIMAL(10,2),
        transaction_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('success', 'failed') NOT NULL,
        FOREIGN KEY (group_buy_id) REFERENCES group_buy(group_buy_id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";

    // Execute table creation
    $tables = [
        'Group Buy' => $create_group_buy_table,
        'Group Buy Participants' => $create_participants_table,
        'Group Buy Transactions' => $create_transactions_table
    ];

    $results = [];
    foreach ($tables as $name => $sql) {
        try {
            $conn->exec($sql);
            $results[$name] = true;
        } catch (PDOException $e) {
            $results[$name] = false;
            error_log("Error creating $name table: " . $e->getMessage());
        }
    }

    // Output results
    header('Content-Type: text/html');
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>Group Buy Tables Creation Results</h1>";
    echo "<ul>";
    foreach ($results as $name => $success) {
        $status = $success ? '✅ Created Successfully' : '❌ Creation Failed';
        echo "<li>$name Table: $status</li>";
    }
    echo "</ul>";
    echo "</body></html>";

} catch (PDOException $e) {
    // Log and display any connection or general errors
    error_log("Database Error: " . $e->getMessage());
    die("Database Error: " . $e->getMessage());
}
