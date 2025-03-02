<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/db.php';
session_start();

// Ensure products table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image VARCHAR(255)
    )");
} catch (PDOException $e) {
    echo "Table creation error: " . $e->getMessage() . "<br>";
}

// Force add products if none exist
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $product_count = $stmt->fetchColumn();

    if ($product_count == 0) {
        // Predefined products to add
        $default_products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'price' => 79.99,
                'description' => 'High-quality wireless headphones with noise cancellation',
                'image' => 'product1.jpg'
            ],
            [
                'name' => 'Smart Fitness Tracker',
                'price' => 49.99,
                'description' => 'Advanced fitness tracker with heart rate monitor and GPS',
                'image' => 'product2.jpg'
            ],
            [
                'name' => 'Portable Power Bank',
                'price' => 29.99,
                'description' => '10000mAh portable charger with fast charging',
                'image' => 'product3.jpg'
            ],
            [
                'name' => 'Wireless Gaming Mouse',
                'price' => 59.99,
                'description' => 'Ergonomic wireless mouse with RGB lighting',
                'image' => 'product4.jpg'
            ],
            [
                'name' => 'Noise-Cancelling Earbuds',
                'price' => 99.99,
                'description' => 'True wireless earbuds with active noise cancellation',
                'image' => 'product5.png'
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
    }
} catch (PDOException $e) {
    echo "Error checking/adding products: " . $e->getMessage() . "<br>";
}

// Check if user is an admin
if (!isset($_SESSION['admin_id'])) {
    echo "Not authorized. Please log in as an admin.<br>";
    exit();
}

// Original product addition logic
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // Handle image upload
    $image = 'default.jpg'; // Default image
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "../images/";
        $target_file = $target_dir . basename($image);
        
        // Check if file already exists
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($imageFileType, $allowed_types)) {
            echo "Sorry, only JPG, JPEG, PNG, GIF & WebP files are allowed.";
            exit;
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = 'images/' . basename($image);
        } else {
            echo "Sorry, there was an error uploading your file.";
            exit;
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $description, $image]);
        echo "Product added successfully!";
    } catch (PDOException $e) {
        echo "Error adding product: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea {
            margin-bottom: 10px;
            padding: 10px;
        }
        button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Product</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Product Name" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <textarea name="description" placeholder="Product Description" required></textarea>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>
</body>
</html>
