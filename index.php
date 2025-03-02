<?php
// Start the session and check if the user is logged in
session_start();

// Logout Logic - placed at the top of the script
if (isset($_POST['logout'])) {
    session_unset(); // Remove all session variables
    session_destroy(); // Destroy the session
    header("Location: pages/login.php"); // Redirect to login page
    exit(); // Make sure no further code is executed after redirection
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: pages/login.php");
    exit();
}

// Include database connection
include 'includes/db.php';

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Check if product is already in the user's cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update quantity if the product is already in the cart
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        // Add new product to the cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    // Redirect to prevent form resubmission
    header("Location: index.php?cart_added=1");
    exit();
}

// Fetch products from the database
$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group Buy Section
try {
    $group_buy_query = "
        SELECT gb.*, p.name AS product_name, p.image AS product_image, p.description AS product_description
        FROM group_buy gb
        JOIN products p ON gb.product_id = p.id
        WHERE gb.status IN ('pending', 'active')
        AND gb.end_datetime > NOW()
        LIMIT 4
    ";
    $group_buy_result = $conn->query($group_buy_query);
} catch (PDOException $e) {
    // Check if table doesn't exist
    if ($e->getCode() == '42S02') {
        // Create group buy tables
        $create_group_buy_table = "
        CREATE TABLE group_buy (
            group_buy_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            creator_user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            base_price DECIMAL(10,2) NOT NULL,
            min_participants INT DEFAULT 5,
            max_participants INT DEFAULT 10,
            current_participants INT DEFAULT 0,
            start_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_datetime DATETIME,
            discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
            discount_value DECIMAL(10,2) NOT NULL,
            group_price DECIMAL(10,2),
            status ENUM('pending', 'active', 'successful', 'failed', 'canceled') DEFAULT 'pending',
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (creator_user_id) REFERENCES users(id)
        )";
        
        $conn->exec($create_group_buy_table);
        
        // Rerun the query
        $group_buy_result = $conn->query($group_buy_query);
    } else {
        // For other database errors
        error_log("Database Error: " . $e->getMessage());
        $group_buy_result = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Open+Sans:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9f0f8 100%);
            color: #2c3e50;
            line-height: 1.6;
            letter-spacing: -0.015em;
            min-height: 100vh;
            background-attachment: fixed;
        }
        .product-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .product-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 600px;
            border: 1px solid #e1e5eb;
            transition: all 0.3s ease;
        }
        .product-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        .product-image-container {
            background-color: #f4f6f9;
            border-bottom: 1px solid #e9ecef;
            position: relative;
            width: 100%;
            height: 450px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .product-image:hover {
            transform: scale(1.1);
        }
        .product-details {
            background-color: #f8f9fa;
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
        }
        .product-name {
            color: #2c3e50;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.04em;
            margin-bottom: 8px;
        }
        .product-price {
            color: #27ae60;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .product-description {
            font-family: 'Open Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: #34495e;
            margin-bottom: 12px;
            line-height: 1.7;
            background-color: #f0f4f8;
            padding: 12px;
            border-radius: 8px;
            border-left: 5px solid #3498db;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            letter-spacing: -0.015em;
        }
        .product-actions {
            background-color: #f1f3f5;
            border-top: 1px solid #e9ecef;
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .quantity-input-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        .quantity-input-container label {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .quantity-input {
            display: flex;
            align-items: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .quantity-input input {
            width: 40px;
            text-align: center;
            border: none;
            padding: 5px;
            font-size: 0.9rem;
        }
        .quantity-btn {
            background-color: #f8f9fa;
            border: none;
            padding: 5px 8px;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .cart-success {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn-professional {
            position: relative;
            padding: 12px 30px;
            border: none;
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 0.9rem;
            background: linear-gradient(45deg, #4ecdc4, #45b7d1);
            background-size: 200% auto;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
            overflow: hidden;
            cursor: pointer;
        }
        .btn-professional::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255,255,255,0.3), transparent);
            transition: all 0.3s ease;
        }
        .btn-professional:hover {
            background-position: right center;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            transform: translateY(-3px);
        }
        .btn-professional:hover::before {
            left: 100%;
        }
        .btn-professional:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }
        .group-buy-section {
            background-color: #f9f9f9;
            padding: 50px 0;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .view-all-group-buys {
            color: #4CAF50;
            text-decoration: none;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
        }

        .view-all-group-buys i {
            margin-left: 5px;
        }

        .group-buy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .group-buy-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .group-buy-card:hover {
            transform: scale(1.05);
        }

        .group-buy-image {
            position: relative;
            height: 250px;
        }

        .group-buy-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .group-buy-discount {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff5722;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .group-buy-details {
            padding: 20px;
        }

        .group-buy-details h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .group-buy-stats {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .btn-join-group-buy {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn-join-group-buy:hover {
            background-color: #45a049;
        }

        .btn-join-group-buy i {
            margin-left: 5px;
        }
        .group-buy-menu-item {
            position: relative;
        }
        
        .group-buy-link {
            display: flex;
            align-items: center;
            color: #4CAF50;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .group-buy-link:hover {
            color: #45a049;
            transform: scale(1.05);
        }
        
        .group-buy-badge {
            background-color: #ff5722;
            color: white;
            font-size: 0.7rem;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 5px;
            position: absolute;
            top: -10px;
            right: -15px;
        }
        
        .group-buy-link i {
            margin-right: 5px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <h1 style="font-size: 3em; text-align: center; color: white; margin-bottom: 20px;">Welcome to Our Store</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="pages/cart.php">Cart</a></li>
                    <li class="group-buy-menu-item">
                        <a href="pages/join_group_buy.php" class="group-buy-link">
                            <i class="fas fa-users"></i> Group Buy
                            <span class="group-buy-badge">New</span>
                        </a>
                    </li>
                    <li><a href="pages/login.php">Login</a></li>
                    <li><a href="pages/register.php">Register</a></li>
                    <!-- Logout button -->
                    <li>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="logout" class="logout-button">Logout</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php 
        // Display cart success message
        if (isset($_GET['cart_added'])) {
            echo '<div class="cart-success">Product added to cart successfully!</div>';
        }
        ?>

        <main>
            <h2 class="my-4">Products</h2>
            <div class="product-container">
                <div class="product-grid">
                    <?php if (empty($products)) : ?>
                        <p class="text-center">No products available.</p>
                    <?php else : ?>
                        <?php foreach ($products as $product) : ?>
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img src="images/<?= basename($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                                </div>
                                <div class="product-details">
                                    <div>
                                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                        <p class="product-price">₹<?= number_format($product['price'], 2) ?></p>
                                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                    </div>
                                </div>
                                <div class="product-actions">
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <div class="quantity-input-container">
                                            <label for="quantity-<?= $product['id'] ?>">Quantity:</label>
                                            <div class="quantity-input">
                                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                                <input type="number" 
                                                       id="quantity-<?= $product['id'] ?>" 
                                                       name="quantity" 
                                                       value="1" 
                                                       min="1" 
                                                       max="10"
                                                       class="form-control w-50 mb-2">
                                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                                            </div>
                                        </div>
                                        <button type="submit" 
                                                name="add_to_cart" 
                                                class="btn-professional">
                                            Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php if ($group_buy_result && $group_buy_result->num_rows > 0): ?>
    <section class="group-buy-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-users"></i> Group Buy Opportunities
                <a href="pages/join_group_buy.php" class="view-all-group-buys">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </h2>
            <div class="group-buy-grid">
                <?php while($group_buy = $group_buy_result->fetch_assoc()): ?>
                    <div class="group-buy-card">
                        <div class="group-buy-image">
                            <img src="<?= htmlspecialchars($group_buy['product_image']) ?>" alt="<?= htmlspecialchars($group_buy['product_name']) ?>">
                            <div class="group-buy-discount">
                                <?= $group_buy['discount_type'] == 'percentage' 
                                    ? htmlspecialchars($group_buy['discount_value']) . '% OFF' 
                                    : '$' . htmlspecialchars($group_buy['discount_value']) . ' OFF' ?>
                            </div>
                        </div>
                        <div class="group-buy-details">
                            <h3><?= htmlspecialchars($group_buy['title']) ?></h3>
                            <p><?= htmlspecialchars($group_buy['description']) ?></p>
                            <div class="group-buy-stats">
                                <span>
                                    <i class="fas fa-users"></i> 
                                    <?= htmlspecialchars($group_buy['current_participants']) ?>/<?= htmlspecialchars($group_buy['max_participants']) ?> 
                                    Participants
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i> 
                                    Ends: <?= date('M d, H:i', strtotime($group_buy['end_datetime'])) ?>
                                </span>
                            </div>
                            <a href="pages/join_group_buy.php?id=<?= htmlspecialchars($group_buy['group_buy_id']) ?>" class="btn-join-group-buy">
                                Join Group Buy <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer>
        <p>&copy; <?= date('Y') ?> Online Store. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(input, change) {
            const quantityInput = input.parentElement.querySelector('input[type="number"]');
            let currentValue = parseInt(quantityInput.value);
            let newValue = currentValue + change;
            
            // Ensure value is within min and max
            newValue = Math.max(parseInt(quantityInput.min), Math.min(newValue, parseInt(quantityInput.max)));
            
            quantityInput.value = newValue;
        }
    </script>
</body>
</html>
