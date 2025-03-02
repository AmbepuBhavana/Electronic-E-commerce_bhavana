<?php
// Explicit error reporting
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

// Group Buy Products
$group_buy_products = [
    [
        'name' => 'Smart Home Starter Kit',
        'description' => 'Complete smart home automation package',
        'price' => 299.99,
        'category' => 'Electronics',
        'stock' => 50,
        'image' => 'smart_home_kit.jpg'
    ],
    [
        'name' => 'Premium Wireless Noise-Cancelling Headphones',
        'description' => 'High-end noise-cancelling headphones with long battery life',
        'price' => 249.99,
        'category' => 'Audio',
        'stock' => 30,
        'image' => 'noise_cancelling_headphones.jpg'
    ],
    [
        'name' => 'Fitness Smartwatch Pro',
        'description' => 'Advanced fitness tracking smartwatch with GPS and health monitoring',
        'price' => 199.99,
        'category' => 'Wearables',
        'stock' => 40,
        'image' => 'fitness_smartwatch.jpg'
    ],
    [
        'name' => '4K Ultra HD Smart TV',
        'description' => '55-inch 4K Smart TV with HDR and built-in streaming',
        'price' => 599.99,
        'category' => 'Electronics',
        'stock' => 20,
        'image' => '4k_smart_tv.jpg'
    ],
    [
        'name' => 'Portable Solar Power Bank',
        'description' => 'High-capacity solar charging power bank for outdoor enthusiasts',
        'price' => 79.99,
        'category' => 'Accessories',
        'stock' => 60,
        'image' => 'solar_power_bank.jpg'
    ]
];

try {
    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO products 
        (name, description, price, category, stock, image) 
        VALUES 
        (:name, :description, :price, :category, :stock, :image)");
    
    // Track successful and existing products
    $added_products = [];
    $existing_products = [];

    // Check for existing products first
    foreach ($group_buy_products as $product) {
        $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = :name");
        $check_stmt->execute(['name' => $product['name']]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            // Insert new product
            $stmt->execute($product);
            $added_products[] = $product['name'];
        } else {
            $existing_products[] = $product['name'];
        }
    }
    
    // Output results
    echo "Group Buy Products Report:\n";
    echo "---------------------\n";
    
    if (!empty($added_products)) {
        echo "Added Products:\n";
        foreach ($added_products as $product_name) {
            echo "- $product_name\n";
        }
    }
    
    if (!empty($existing_products)) {
        echo "\nExisting Products (Skipped):\n";
        foreach ($existing_products as $product_name) {
            echo "- $product_name\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error adding products: " . $e->getMessage() . "\n";
}
?>
