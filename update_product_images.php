<?php
include 'includes/db.php';

try {
    // Get image files (excluding cart-icon)
    $image_dir = __DIR__ . '/images';
    $image_files = array_values(array_filter(scandir($image_dir), function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'png']) 
               && $file !== 'cart-icon.png';
    }));

    // Fetch products
    $stmt = $conn->query("SELECT id FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Assign images to products
    foreach ($products as $index => $id) {
        // Ensure we cycle through images if more products than images
        $image = $image_files[$index % count($image_files)];
        
        $update = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
        $update->execute([$image, $id]);
        
        echo "Updated product $id with image $image<br>";
    }

    echo "Images updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
