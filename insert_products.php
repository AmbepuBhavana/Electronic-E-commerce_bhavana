<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'includes/db.php';

// Additional products to insert
$additional_products = [
    [
        'name' => 'Apple MacBook Pro',
        'price' => 1999.99,
        'description' => 'Powerful laptop with M2 chip, 16GB RAM, 512GB SSD',
        'image' => 'images/macbook_pro.jpg'
    ],
    [
        'name' => 'Samsung 4K Smart TV',
        'price' => 799.99,
        'description' => '55-inch QLED 4K Ultra HD Smart TV with HDR',
        'image' => 'images/samsung_tv.jpg'
    ],
    [
        'name' => 'Sony PlayStation 5',
        'price' => 499.99,
        'description' => 'Next-gen gaming console with ultra-high speed SSD',
        'image' => 'images/ps5.jpg'
    ],
    [
        'name' => 'Apple iPhone 14 Pro',
        'price' => 1099.99,
        'description' => '6.1-inch Pro smartphone with Dynamic Island, 48MP camera',
        'image' => 'images/iphone14_pro.jpg'
    ],
    [
        'name' => 'DJI Mavic 3 Drone',
        'price' => 2049.99,
        'description' => 'Professional-grade camera drone with 4/3 CMOS Hasselblad Camera',
        'image' => 'images/dji_mavic3.jpg'
    ],
    [
        'name' => 'Bose QuietComfort Earbuds',
        'price' => 279.99,
        'description' => 'Wireless noise-cancelling earbuds with exceptional sound quality',
        'image' => 'images/bose_earbuds.jpg'
    ],
    [
        'name' => 'LG Refrigerator',
        'price' => 1299.99,
        'description' => 'InstaView Door-in-Door Refrigerator with craft ice',
        'image' => 'images/lg_refrigerator.jpg'
    ],
    [
        'name' => 'Kindle Paperwhite',
        'price' => 139.99,
        'description' => 'E-reader with 6.8" display, adjustable warm light, 8GB storage',
        'image' => 'images/kindle_paperwhite.jpg'
    ]
];

try {
    // Prepare insert statement
    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    
    // Track successful and failed inserts
    $successful_inserts = 0;
    $failed_inserts = 0;
    
    // Insert each product
    foreach ($additional_products as $product) {
        try {
            // Check if product already exists
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
            $check_stmt->execute([$product['name']]);
            $exists = $check_stmt->fetchColumn();
            
            if ($exists == 0) {
                $stmt->execute([
                    $product['name'], 
                    $product['price'], 
                    $product['description'], 
                    $product['image']
                ]);
                $successful_inserts++;
                echo "Added product: {$product['name']}<br>";
            } else {
                echo "Product already exists: {$product['name']}<br>";
            }
        } catch (PDOException $e) {
            $failed_inserts++;
            echo "Error adding product {$product['name']}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>Summary:<br>";
    echo "Total Products Attempted: " . count($additional_products) . "<br>";
    echo "Successful Inserts: $successful_inserts<br>";
    echo "Failed Inserts: $failed_inserts<br>";
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}
?>
