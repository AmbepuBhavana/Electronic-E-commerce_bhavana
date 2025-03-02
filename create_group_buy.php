<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

try {
    // Start a transaction
    $conn->beginTransaction();

    // Get a product
    $product_stmt = $conn->query('SELECT id, name, price FROM products LIMIT 1');
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get a user as creator
    $user_stmt = $conn->query('SELECT id FROM users LIMIT 1');
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product || !$user) {
        throw new Exception("No products or users found");
    }
    
    // Prepare the create_group_buy procedure call
    $stmt = $conn->prepare("CALL create_group_buy(?, ?, ?, ?, ?, ?, ?, ?, ?, @group_buy_id)");
    $stmt->execute([
        $product['id'],           // product_id
        $user['id'],              // creator_user_id
        "Group Buy: {$product['name']}", // title
        "Discounted group purchase for {$product['name']}", // description
        3,                        // min_participants
        10,                       // max_participants
        'percentage',             // discount_type
        10,                       // discount_value (10% off)
        date('Y-m-d H:i:s', strtotime('+7 days')), // end_datetime
    ]);
    
    // Retrieve the created group buy ID
    $id_stmt = $conn->query("SELECT @group_buy_id AS group_buy_id");
    $group_buy_id = $id_stmt->fetch(PDO::FETCH_ASSOC)['group_buy_id'];
    
    // Update group buy status to active
    $conn->exec("UPDATE group_buy SET status = 'active' WHERE group_buy_id = $group_buy_id");
    
    // Commit the transaction
    $conn->commit();
    
    echo "Group Buy Created Successfully!\n";
    echo "Product: {$product['name']}\n";
    echo "Group Buy ID: $group_buy_id\n";
} catch (Exception $e) {
    // Rollback the transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "Error: " . $e->getMessage() . "\n";
    error_log($e->getMessage());
}
?>
