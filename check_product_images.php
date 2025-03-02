<?php
include 'includes/db.php';

try {
    $stmt = $conn->query("SELECT id, name, image FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>Product Image Diagnostic</h1>";
    
    foreach ($products as $product) {
        echo "<div style='border: 1px solid black; margin: 10px; padding: 10px;'>";
        echo "<h2>" . htmlspecialchars($product['name']) . "</h2>";
        
        echo "<h3>Database Image:</h3>";
        echo "<pre>" . htmlspecialchars($product['image'] ?? 'NO IMAGE') . "</pre>";
        
        $image_path = "images/" . $product['image'];
        echo "<h3>Constructed Image Path:</h3>";
        echo "<pre>" . htmlspecialchars($image_path) . "</pre>";
        
        echo "<h3>Image Exists:</h3>";
        echo "<pre>" . (file_exists($image_path) ? 'YES' : 'NO') . "</pre>";
        
        if (file_exists($image_path)) {
            echo "<h3>Image Preview:</h3>";
            echo "<img src='" . htmlspecialchars($image_path) . "' style='max-width: 200px; border: 1px solid red;'>";
        } else {
            echo "<h3>Image NOT FOUND</h3>";
        }
        
        echo "</div>";
    }
    
    echo "</body></html>";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
