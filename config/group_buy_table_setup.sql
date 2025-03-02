-- Drop existing tables if they exist
DROP TABLE IF EXISTS group_buy_transactions;
DROP TABLE IF EXISTS group_buy_participants;
DROP TABLE IF EXISTS group_buy;

-- Create Group Buy Table
CREATE TABLE group_buy (
    group_buy_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    creator_user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    min_participants INT NOT NULL,
    max_participants INT NOT NULL,
    current_participants INT DEFAULT 0,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    group_price DECIMAL(10,2) NOT NULL,
    start_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_datetime DATETIME NOT NULL,
    status ENUM('pending', 'active', 'successful', 'failed', 'canceled') DEFAULT 'active',
    
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (creator_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Create Group Buy Participants Table
CREATE TABLE group_buy_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    group_buy_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'canceled') DEFAULT 'pending',
    joined_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_buy_id) REFERENCES group_buy(group_buy_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    
    UNIQUE KEY unique_user_group_buy (group_buy_id, user_id)
) ENGINE=InnoDB;

-- Create Group Buy Transactions Table
CREATE TABLE group_buy_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    group_buy_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_type ENUM('create', 'join', 'leave', 'purchase') NOT NULL,
    status ENUM('success', 'failed', 'pending') NOT NULL,
    transaction_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_buy_id) REFERENCES group_buy(group_buy_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Optional: Add indexes for performance
CREATE INDEX idx_group_buy_product ON group_buy(product_id);
CREATE INDEX idx_group_buy_creator ON group_buy(creator_user_id);
CREATE INDEX idx_group_buy_participants_user ON group_buy_participants(user_id);
CREATE INDEX idx_group_buy_transactions_user ON group_buy_transactions(user_id);
