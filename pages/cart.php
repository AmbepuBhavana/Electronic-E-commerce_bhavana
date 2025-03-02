<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];  // Assuming user ID is stored in session

// Debugging: Print user ID and session information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debugging: Check if cart table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'cart'");
    $cart_table_exists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    echo "Error checking cart table: " . $e->getMessage();
    $cart_table_exists = false;
}

// If cart table doesn't exist, create it
if (!$cart_table_exists) {
    try {
        $conn->exec("CREATE TABLE cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        echo "Cart table created successfully!<br>";
    } catch (PDOException $e) {
        echo "Error creating cart table: " . $e->getMessage();
    }
}

// Handle Add to Cart with Quantity
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;  // Default to 1 if quantity is not set

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
}

// Handle Product Removal from Cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}

// Handle Quantity Update
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Update the quantity in the cart
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user_id, $product_id]);
}

// Fetch the user's cart items with product details
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debugging: Print cart items
echo "Debugging Information:<br>";
echo "User ID: " . $user_id . "<br>";
echo "Number of Cart Items: " . count($cart_items) . "<br>";
if (empty($cart_items)) {
    echo "Possible reasons for empty cart:<br>";
    echo "1. No products added to cart<br>";
    echo "2. User not logged in<br>";
    echo "3. Cart table issue<br>";
}

$total_cost = 0;  // Initialize total cost variable
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9f0f8 100%);
            font-family: 'Open Sans', sans-serif;
            color: #2c3e50;
        }
        .cart-container {
            max-width: 1100px;
            margin: 50px auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .cart-container:hover {
            box-shadow: 0 20px 45px rgba(0,0,0,0.15);
            transform: translateY(-10px);
        }
        .cart-header {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .cart-header h2 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .cart-items {
            padding: 30px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .cart-item-price {
            font-weight: 600;
            color: #27ae60;
            font-size: 1.2rem;
        }
        .cart-item-quantity {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .quantity-btn {
            background-color: #3498db;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .quantity-btn:hover {
            background-color: #2980b9;
        }
        .cart-summary {
            background-color: #f1f3f5;
            padding: 20px;
            text-align: right;
        }
        .cart-total {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2c3e50;
        }
        .btn-continue, .btn-checkout {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-continue {
            background-color: #3498db;
            color: white;
        }
        .btn-continue:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
        }
        .btn-checkout {
            background-color: #27ae60;
            color: white;
        }
        .btn-checkout:hover {
            background-color: #2ecc71;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <div class="cart-header">
            <h2>Your Shopping Cart</h2>
        </div>
        <div class="cart-items">
            <?php if (empty($cart_items)) : ?>
                <div class="empty-cart">
                    <p>Your cart is empty.</p>
                    <p>To add items:</p>
                    <ol>
                        <li>Go to the home page</li>
                        <li>Browse available products</li>
                        <li>Click "Add to Cart" on desired items</li>
                    </ol>
                    <a href="../index.php" class="btn-continue">Continue Shopping</a>
                </div>
            <?php else : ?>
                <?php foreach ($cart_items as $item) : ?>
                    <div class="cart-item">
                        <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                        <div class="cart-item-details">
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="cart-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            <form method="POST">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="update_quantity">Update</button>
                                <button type="submit" name="remove_from_cart">Remove</button>
                            </form>
                        </div>
                    </div>
                    <?php $total_cost += $item['price'] * $item['quantity']; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cart-summary">
            <div class="cart-total">
                Total: ₹<?php echo number_format($total_cost, 2); ?>
            </div>
            <a href="index.php" class="btn-continue">Continue Shopping</a>
            <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
        </div>
    </div>

    <script>
        function increaseQuantity() {
            // Implement quantity increase logic
        }

        function decreaseQuantity() {
            // Implement quantity decrease logic
        }
    </script>
</body>
</html>
