<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host", $user, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $conn->exec("CREATE DATABASE IF NOT EXISTS ecommerce");
    echo "Database created successfully<br>";
    
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=ecommerce", $user, $password);
    
    // Create users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(255) NOT NULL
    )");
    echo "Users table created successfully<br>";
    
    // Create products table
    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image VARCHAR(255)
    )");
    echo "Products table created successfully<br>";
    
    // Add default admin user
    $default_admin_email = 'admin@example.com';
    $default_admin_password = password_hash('AdminPass123!', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$default_admin_email, $default_admin_password]);
    echo "Default admin user created successfully!<br>";
    
    // Insert default products if none exist
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $product_count = $stmt->fetchColumn();
    
    if ($product_count == 0) {
        $default_products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'price' => 79.99,
                'description' => 'High-quality wireless headphones with noise cancellation',
                'image' => 'images/product1.jpg'
            ],
            [
                'name' => 'Smart Fitness Tracker',
                'price' => 49.99,
                'description' => 'Advanced fitness tracker with heart rate monitor and GPS',
                'image' => 'images/product2.jpg'
            ],
            [
                'name' => 'Portable Power Bank',
                'price' => 29.99,
                'description' => '10000mAh portable charger with fast charging',
                'image' => 'images/product3.jpg'
            ],
            [
                'name' => 'Wireless Gaming Mouse',
                'price' => 59.99,
                'description' => 'Ergonomic wireless mouse with RGB lighting',
                'image' => 'images/product4.jpg'
            ],
            [
                'name' => 'Noise-Cancelling Earbuds',
                'price' => 99.99,
                'description' => 'True wireless earbuds with active noise cancellation',
                'image' => 'images/product5.png'
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
        
        foreach ($default_products as $product) {
            try {
                $stmt->execute([
                    $product['name'], 
                    $product['price'], 
                    $product['description'], 
                    $product['image']
                ]);
                echo "Added product: {$product['name']}<br>";
            } catch (PDOException $e) {
                echo "Error adding product {$product['name']}: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "Default products added successfully!<br>";
    } else {
        echo "Products already exist. No new products added.<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
