<?php
session_start();
include '../config/connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch active group buy opportunities with more details
$query = "SELECT 
    gp.*, 
    p.name, 
    p.image, 
    p.description, 
    (SELECT COUNT(*) FROM group_purchase_members gpm 
     WHERE gpm.group_purchase_id = gp.id AND gpm.status = 'confirmed') as confirmed_members,
    TIMESTAMPDIFF(HOUR, NOW(), gp.end_datetime) as hours_remaining
FROM 
    group_purchases gp
JOIN 
    products p ON gp.product_id = p.id
WHERE 
    gp.status = 'active'
ORDER BY 
    gp.discount_percentage DESC";

$result = mysqli_query($conn, $query);

// Check for query execution error
if (!$result) {
    $error_message = "Query failed: " . mysqli_error($conn);
    error_log($error_message);
    die($error_message);
}

// Check if any rows were returned
$group_buy_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Buy Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/group_buy.css">
</head>
<body>
    <div class="group-buy-container">
        <header class="group-buy-header">
            <div class="header-content">
                <h1>Group Buy Marketplace</h1>
                <p>Join forces, unlock massive discounts!</p>
            </div>
        </header>

        <?php if ($group_buy_count > 0): ?>
            <section class="group-buy-grid">
                <?php while($group = mysqli_fetch_assoc($result)): 
                    // Calculate progress percentage
                    $progress = ($group['confirmed_members'] / $group['max_participants']) * 100;
                    
                    // Determine time remaining status
                    $time_status = $group['hours_remaining'] > 24 ? 'good' : 
                                   ($group['hours_remaining'] > 12 ? 'warning' : 'critical');
                ?>
                <div class="group-buy-card">
                    <div class="card-header">
                        <h3><?= htmlspecialchars($group['name']) ?></h3>
                        <span class="discount-badge">
                            <?= number_format($group['discount_percentage'], 0) ?>% OFF
                        </span>
                    </div>
                    
                    <div class="card-image">
                        <img src="../images/<?= htmlspecialchars($group['image']) ?>" alt="<?= htmlspecialchars($group['name']) ?>">
                    </div>
                    
                    <div class="card-details">
                        <div class="price-section">
                            <span class="original-price">₹<?= number_format($group['base_price'], 2) ?></span>
                            <span class="group-price">₹<?= number_format($group['group_price'], 2) ?></span>
                        </div>
                        
                        <div class="progress-section">
                            <div class="progress-bar">
                                <div class="progress" style="width: <?= $progress ?>%"></div>
                            </div>
                            <div class="progress-info">
                                <span><?= $group['confirmed_members'] ?>/<?= $group['max_participants'] ?> Joined</span>
                                <span class="time-remaining time-<?= $time_status ?>">
                                    <i class="fas fa-clock"></i> 
                                    <?= $group['hours_remaining'] > 0 ? 
                                        floor($group['hours_remaining']) . 'h remaining' : 
                                        'Ending Soon!' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-description">
                            <?= htmlspecialchars(substr($group['description'], 0, 100)) ?>...
                        </div>
                        
                        <a href="group_buy_details.php?id=<?= $group['id'] ?>" class="join-button">
                            Join Group Buy
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </section>
        <?php else: ?>
            <div class="no-group-buys">
                <h2>No Active Group Buy Opportunities</h2>
                <p>We're working on bringing you exciting group deals soon!</p>
            </div>
        <?php endif; ?>

        <section class="how-it-works">
            <h2>How Group Buying Works</h2>
            <div class="steps-container">
                <div class="step">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Choose Product</h3>
                    <p>Browse amazing group deals</p>
                </div>
                <div class="step">
                    <i class="fas fa-users"></i>
                    <h3>Invite Friends</h3>
                    <p>Share and grow the group</p>
                </div>
                <div class="step">
                    <i class="fas fa-tag"></i>
                    <h3>Get Discounts</h3>
                    <p>Unlock lower prices together</p>
                </div>
                <div class="step">
                    <i class="fas fa-truck"></i>
                    <h3>Group Shipping</h3>
                    <p>Convenient delivery for all</p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
