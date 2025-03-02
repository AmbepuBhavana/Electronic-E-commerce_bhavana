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

try {
    // Fetch products and users
    $product_stmt = $conn->query('SELECT id, name, price FROM products LIMIT 5');
    $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $user_stmt = $conn->query('SELECT id FROM users LIMIT 5');
    $users = $user_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($products) < 1 || count($users) < 1) {
        throw new Exception("Not enough products or users to create group buys");
    }
    
    // Group Buy Configurations
    $group_buy_configs = [
        [
            'title' => 'Smart Home Tech Blowout',
            'description' => 'Group buy for the latest smart home technology',
            'min_participants' => 5,
            'max_participants' => 20,
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'days_to_end' => 7
        ],
        [
            'title' => 'Audio Lovers Unite',
            'description' => 'Exclusive group buy for premium audio gear',
            'min_participants' => 3,
            'max_participants' => 15,
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'days_to_end' => 10
        ],
        [
            'title' => 'Fitness Tech Challenge',
            'description' => 'Group purchase of cutting-edge fitness technology',
            'min_participants' => 4,
            'max_participants' => 12,
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'days_to_end' => 5
        ]
    ];
    
    // Prepare group buy creation statement
    $create_stmt = $conn->prepare("CALL create_group_buy(?, ?, ?, ?, ?, ?, ?, ?, ?, @group_buy_id)");
    
    // Store created group buys
    $created_group_buys = [];
    
    // Create group buys
    foreach ($group_buy_configs as $index => $config) {
        // Cycle through products and users
        $product = $products[$index % count($products)];
        $creator_user_id = $users[$index % count($users)];
        
        $create_stmt->execute([
            $product['id'],                   // product_id
            $creator_user_id,                 // creator_user_id
            $config['title'],                 // title
            $config['description'],           // description
            $config['min_participants'],      // min_participants
            $config['max_participants'],      // max_participants
            $config['discount_type'],         // discount_type
            $config['discount_value'],        // discount_value
            date('Y-m-d H:i:s', strtotime("+{$config['days_to_end']} days")) // end_datetime
        ]);
        
        // Retrieve the created group buy ID
        $id_stmt = $conn->query("SELECT @group_buy_id AS group_buy_id");
        $group_buy_id = $id_stmt->fetch(PDO::FETCH_ASSOC)['group_buy_id'];
        
        // Store group buy details
        $created_group_buys[] = [
            'id' => $group_buy_id,
            'title' => $config['title'],
            'product_name' => $product['name'],
            'creator_user_id' => $creator_user_id
        ];
    }
    
    // Output created group buys
    echo "Created Group Buys:\n";
    echo "-------------------\n";
    foreach ($created_group_buys as $group_buy) {
        echo "Group Buy ID: {$group_buy['id']}\n";
        echo "Title: {$group_buy['title']}\n";
        echo "Product: {$group_buy['product_name']}\n";
        echo "Creator User ID: {$group_buy['creator_user_id']}\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
