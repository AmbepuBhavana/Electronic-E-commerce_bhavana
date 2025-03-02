<?php
// Group Buy Database Setup and Validation Script

// Include main database connection
include_once 'database.php';

// Create group_buys table if not exists
$create_group_buys_table = "
CREATE TABLE IF NOT EXISTS group_buys (
    group_buy_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    max_participants INT NOT NULL,
    current_participants INT DEFAULT 0,
    start_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_datetime DATETIME NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    discount_percentage DECIMAL(5,2) NOT NULL,
    created_by INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_buy_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    group_buy_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_buy_id) REFERENCES group_buys(group_buy_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_participant (group_buy_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Execute table creation
if (mysqli_query($conn, $create_group_buys_table)) {
    echo "Group Buy tables created successfully.\n";
} else {
    echo "Error creating group buy tables: " . mysqli_error($conn) . "\n";
}

// Add necessary columns to products table if not exists
$alter_products_table = "
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS group_price DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS discount_percentage DECIMAL(5,2) NULL,
ADD COLUMN IF NOT EXISTS is_group_buy_enabled BOOLEAN DEFAULT FALSE;
";

if (mysqli_query($conn, $alter_products_table)) {
    echo "Products table updated successfully.\n";
} else {
    echo "Error updating products table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
