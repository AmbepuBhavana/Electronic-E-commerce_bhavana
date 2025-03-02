<?php
// Simulate database connection and group buy data
$group_buys = [
    [
        'id' => 1,
        'title' => 'Smart Home Tech Blowout',
        'product_name' => 'Smart Home Starter Kit',
        'description' => 'Complete smart home automation package with cutting-edge technology',
        'original_price' => 299.99,
        'group_price' => 254.99,
        'discount_percentage' => 15,
        'status' => 'active',
        'current_participants' => 7,
        'max_participants' => 20,
        'image' => 'uploads/smart_home_kit.jpg',
        'time_remaining' => '6 days 12 hours'
    ],
    [
        'id' => 2,
        'title' => 'Audio Lovers Unite',
        'product_name' => 'Premium Wireless Noise-Cancelling Headphones',
        'description' => 'High-end noise-cancelling headphones with exceptional sound quality',
        'original_price' => 249.99,
        'group_price' => 199.99,
        'discount_percentage' => 20,
        'status' => 'pending',
        'current_participants' => 2,
        'max_participants' => 15,
        'image' => 'uploads/noise_cancelling_headphones.jpg',
        'time_remaining' => '10 days 5 hours'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Buy Opportunities</title>
    <link rel="stylesheet" href="../css/group_buy.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="group-buy-container">
        <h1>Group Buy Opportunities</h1>
        
        <?php foreach ($group_buys as $group_buy): ?>
        <div class="group-buy-card">
            <div class="group-buy-header">
                <h2 class="group-buy-title"><?php echo htmlspecialchars($group_buy['title']); ?></h2>
                <span class="group-buy-status <?php echo strtolower($group_buy['status']); ?>">
                    <?php echo htmlspecialchars($group_buy['status']); ?>
                </span>
            </div>
            
            <div class="group-buy-body">
                <div class="group-buy-image">
                    <img src="<?php echo htmlspecialchars($group_buy['image']); ?>" alt="<?php echo htmlspecialchars($group_buy['product_name']); ?>">
                </div>
                
                <div class="group-buy-details">
                    <h3><?php echo htmlspecialchars($group_buy['product_name']); ?></h3>
                    <p><?php echo htmlspecialchars($group_buy['description']); ?></p>
                    
                    <div class="group-buy-price">
                        <div>
                            <span class="original-price">$<?php echo number_format($group_buy['original_price'], 2); ?></span>
                            <span class="group-price">$<?php echo number_format($group_buy['group_price'], 2); ?></span>
                        </div>
                        <span class="discount-badge">
                            <?php echo htmlspecialchars($group_buy['discount_percentage']); ?>% OFF
                        </span>
                    </div>
                    
                    <div class="group-buy-progress">
                        <div class="progress-bar">
                            <div 
                                class="progress-bar-fill" 
                                style="width: <?php echo ($group_buy['current_participants'] / $group_buy['max_participants']) * 100; ?>%"
                            ></div>
                        </div>
                        
                        <div class="participants-info">
                            <span><?php echo $group_buy['current_participants']; ?> / <?php echo $group_buy['max_participants']; ?> Participants</span>
                            <span class="time-remaining"><?php echo htmlspecialchars($group_buy['time_remaining']); ?> Left</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="group-buy-footer">
                <button 
                    class="join-button" 
                    <?php echo $group_buy['status'] !== 'active' ? 'disabled' : ''; ?>
                >
                    <?php echo $group_buy['status'] === 'active' ? 'Join Group Buy' : 'Not Available'; ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
