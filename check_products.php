<?php
include 'includes/db.php';

try {
    $stmt = $conn->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "No products found in the database.\n";
    } else {
        echo "Products in the database:\n";
        foreach ($products as $product) {
            echo "ID: {$product['id']}, Name: {$product['name']}, Price: \${$product['price']}, Image: {$product['image']}\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
