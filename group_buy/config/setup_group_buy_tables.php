<?php
// Remove previous include and use direct connection from db.php
require_once '../includes/db.php';

// Group Buy Main Table
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

// Group Buy Participants Table
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

// Group Buy Transactions Table
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

// Create group_buys table if not exists
$create_group_buys_table = "CREATE TABLE IF NOT EXISTS group_buys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    product_image VARCHAR(255),
    current_participants INT DEFAULT 0,
    max_participants INT NOT NULL,
    target_price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    // Execute table creation
    $conn->exec($create_group_buy_table);
    $conn->exec($create_participants_table);
    $conn->exec($create_transactions_table);
    
    if ($conn->query($create_group_buys_table) === FALSE) {
        echo "Error creating group_buys table: " . $conn->error;
    } else {
        echo "Group Buy tables created successfully.";
    }
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}

$conn = null;
?>
