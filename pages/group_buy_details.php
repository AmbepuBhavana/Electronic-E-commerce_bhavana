<?php
session_start();
include '../config/connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if group buy ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid group buy ID");
}

$group_buy_id = intval($_GET['id']);

// Fetch detailed group buy information
$query = "SELECT 
    gp.*, 
    p.name AS product_name, 
    p.image AS product_image, 
    p.description AS product_description,
    p.specifications,
    (SELECT COUNT(*) FROM group_purchase_members gpm 
     WHERE gpm.group_purchase_id = gp.id AND gpm.status = 'confirmed') as confirmed_members,
    TIMESTAMPDIFF(HOUR, NOW(), gp.end_datetime) as hours_remaining
FROM 
    group_purchases gp
JOIN 
    products p ON gp.product_id = p.id
WHERE 
    gp.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $group_buy_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Group buy opportunity not found");
}

$group = $result->fetch_assoc();

// Calculate progress
$progress = ($group['confirmed_members'] / $group['max_participants']) * 100;

// Determine time remaining status
$time_status = $group['hours_remaining'] > 24 ? 'good' : 
               ($group['hours_remaining'] > 12 ? 'warning' : 'critical');

// Fetch current group members
$members_query = "SELECT u.name, u.profile_picture 
                  FROM group_purchase_members gpm
                  JOIN users u ON gpm.user_id = u.id
                  WHERE gpm.group_purchase_id = ? 
                  AND gpm.status = 'confirmed'
                  LIMIT 10";
$members_stmt = $conn->prepare($members_query);
$members_stmt->bind_param('i', $group_buy_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['product_name']) ?> - Group Buy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/group_buy_details.css">
</head>
<body>
    <div class="group-buy-container">
        <div class="group-buy-header">
            <div class="product-image">
                <img src="../images/<?= htmlspecialchars($group['product_image']) ?>" alt="<?= htmlspecialchars($group['product_name']) ?>">
                <div class="discount-badge">
                    <?= number_format($group['discount_percentage'], 0) ?>% OFF
                </div>
            </div>
            
            <div class="product-details">
                <h1><?= htmlspecialchars($group['product_name']) ?></h1>
                <p class="product-description"><?= htmlspecialchars($group['product_description']) ?></p>
                
                <div class="price-section">
                    <div class="original-price">
                        <span>Original Price</span>
                        <strong>₹<?= number_format($group['base_price'], 2) ?></strong>
                    </div>
                    <div class="group-price">
                        <span>Group Price</span>
                        <strong>₹<?= number_format($group['group_price'], 2) ?></strong>
                    </div>
                    <div class="savings">
                        <span>You Save</span>
                        <strong>₹<?= number_format($group['base_price'] - $group['group_price'], 2) ?></strong>
                    </div>
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
                
                <div class="group-buy-actions">
                    <?php if ($group['status'] == 'active' && $group['confirmed_members'] < $group['max_participants']): ?>
                        <button class="join-button" id="join-group-buy">
                            <i class="fas fa-users"></i> Join Group Buy
                        </button>
                    <?php else: ?>
                        <button class="join-button disabled" disabled>
                            Group Buy Closed
                        </button>
                    <?php endif; ?>
                    <button class="share-button">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                </div>
            </div>
        </div>
        
        <div class="group-buy-content">
            <div class="product-specifications">
                <h2>Product Specifications</h2>
                <div class="specs-grid">
                    <?php 
                    // Parse specifications (assuming JSON format)
                    $specs = json_decode($group['specifications'], true);
                    if ($specs): 
                        foreach ($specs as $key => $value):
                    ?>
                        <div class="spec-item">
                            <span class="spec-name"><?= htmlspecialchars($key) ?></span>
                            <span class="spec-value"><?= htmlspecialchars($value) ?></span>
                        </div>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                        <p>No specifications available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="group-members">
                <h2>Group Members (<?= $group['confirmed_members'] ?>)</h2>
                <div class="members-grid">
                    <?php while($member = $members_result->fetch_assoc()): ?>
                        <div class="member-card">
                            <img src="<?= htmlspecialchars($member['profile_picture'] ?? '../images/default-avatar.png') ?>" 
                                 alt="<?= htmlspecialchars($member['name']) ?>">
                            <span><?= htmlspecialchars($member['name']) ?></span>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($group['confirmed_members'] > 10): ?>
                        <div class="more-members">
                            +<?= $group['confirmed_members'] - 10 ?> more
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="how-group-buy-works">
            <h2>How Group Buy Works</h2>
            <div class="steps-container">
                <div class="step">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Select Product</h3>
                    <p>Choose the product you want to buy</p>
                </div>
                <div class="step">
                    <i class="fas fa-users"></i>
                    <h3>Invite Friends</h3>
                    <p>Share the group buy link</p>
                </div>
                <div class="step">
                    <i class="fas fa-tag"></i>
                    <h3>Unlock Discount</h3>
                    <p>Get lower price when group fills</p>
                </div>
                <div class="step">
                    <i class="fas fa-truck"></i>
                    <h3>Group Shipping</h3>
                    <p>Convenient delivery for all</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/group_buy_details.js"></script>
</body>
</html>

<?php
// Close database connections
$stmt->close();
$members_stmt->close();
$conn->close();
?>
