<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Explicit database connection
try {
    $host = 'localhost';
    $dbname = 'ecommerce_db';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

function getRandomProduct($conn) {
    $stmt = $conn->query('SELECT id, name, price FROM products ORDER BY RAND() LIMIT 1');
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRandomUser($conn) {
    $stmt = $conn->query('SELECT id FROM users ORDER BY RAND() LIMIT 1');
    return $stmt->fetch(PDO::FETCH_COLUMN);
}

try {
    // Get a random product and user
    $product = getRandomProduct($conn);
    $creator_user_id = getRandomUser($conn);

    // Prepare group buy creation
    $create_stmt = $conn->prepare("CALL create_group_buy(?, ?, ?, ?, ?, ?, ?, ?, ?, @group_buy_id)");
    
    $create_stmt->execute([
        $product['id'],                   // product_id
        $creator_user_id,                 // creator_user_id
        "Group Buy: {$product['name']}",  // title
        "Exclusive group buy for {$product['name']}",  // description
        3,                                // min_participants
        10,                               // max_participants
        'percentage',                     // discount_type
        15,                               // discount_value
        date('Y-m-d H:i:s', strtotime('+7 days')) // end_datetime
    ]);
    
    // Retrieve the created group buy ID
    $id_stmt = $conn->query("SELECT @group_buy_id AS group_buy_id");
    $group_buy_id = $id_stmt->fetch(PDO::FETCH_ASSOC)['group_buy_id'];
    
    // Prepare join group buy statement
    $join_stmt = $conn->prepare("CALL join_group_buy(?, ?)");
    
    // Join with multiple users
    $participants = [];
    for ($i = 0; $i < 3; $i++) {
        $participant_user_id = getRandomUser($conn);
        
        try {
            $join_stmt->execute([$group_buy_id, $participant_user_id]);
            $participants[] = $participant_user_id;
        } catch (PDOException $e) {
            echo "Error joining group buy for user $participant_user_id: " . $e->getMessage() . "\n";
        }
    }
    
    // Display results
    echo "Group Buy Created Successfully!\n";
    echo "Group Buy ID: $group_buy_id\n";
    echo "Product: {$product['name']} (Price: {$product['price']})\n";
    echo "Creator User ID: $creator_user_id\n";
    echo "Participants: " . implode(', ', $participants) . "\n";
    
    // Verify group buy details
    $verify_stmt = $conn->prepare("
        SELECT 
            gb.group_buy_id, 
            gb.title, 
            gb.current_participants,
            p.name as product_name,
            gb.group_price
        FROM group_buy gb
        JOIN products p ON gb.product_id = p.id
        WHERE gb.group_buy_id = ?
    ");
    $verify_stmt->execute([$group_buy_id]);
    $group_buy_details = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nGroup Buy Details:\n";
    print_r($group_buy_details);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
