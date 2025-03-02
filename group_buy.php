<?php
session_start();
// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once 'config/database.php';
require_once 'includes/header.php';

// Sanitize input functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fetch featured group deals with comprehensive details and error handling
try {
    $featured_deals_query = "
    SELECT 
        p.product_id, 
        p.product_name, 
        p.product_image, 
        p.original_price, 
        p.group_price, 
        p.discount_percentage,
        gb.group_buy_id,
        gb.max_participants,
        gb.current_participants,
        gb.end_datetime,
        DATEDIFF(gb.end_datetime, NOW()) as days_left,
        (gb.current_participants * 100 / gb.max_participants) as participation_percentage
    FROM 
        products p
    JOIN 
        group_buys gb ON p.product_id = gb.product_id
    WHERE 
        gb.status = 'active' 
        AND gb.end_datetime > NOW()
    ORDER BY 
        participation_percentage ASC, 
        days_left ASC
    LIMIT 10";

    $featured_deals_result = mysqli_query($conn, $featured_deals_query);

    if (!$featured_deals_result) {
        throw new Exception("Error fetching group buy deals: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log($e->getMessage());
    $featured_deals_result = [];
}

// Fetch categories for filtering
$categories_query = "SELECT DISTINCT category FROM products";
$categories_result = mysqli_query($conn, $categories_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Buy - Collective Savings</title>
    <!-- Enhanced Styling -->
    <link rel="stylesheet" href="css/group_buy.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container group-buy-container">
        <header class="group-buy-header">
            <h1>Unlock Massive Savings Together!</h1>
            <p>Join group buys and get incredible discounts when more people participate.</p>
        </header>

        <!-- Advanced Filtering -->
        <section class="group-buy-filters">
            <div class="filter-container">
                <select id="category-filter" class="form-control">
                    <option value="">All Categories</option>
                    <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo htmlspecialchars($category['category']); ?>">
                            <?php echo htmlspecialchars($category['category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select id="discount-filter" class="form-control">
                    <option value="">All Discounts</option>
                    <option value="10">10% and above</option>
                    <option value="20">20% and above</option>
                    <option value="30">30% and above</option>
                </select>
                <select id="availability-filter" class="form-control">
                    <option value="">All Availability</option>
                    <option value="open">Spots Available</option>
                    <option value="almost_full">Almost Full</option>
                </select>
            </div>
        </section>

        <!-- Group Buy Deals Grid -->
        <section class="group-buy-deals">
            <?php if (!empty($featured_deals_result)): ?>
                <div class="deals-grid">
                    <?php while ($deal = mysqli_fetch_assoc($featured_deals_result)): ?>
                        <div class="deal-card" 
                             data-category="<?= sanitize_input($deal['product_name']) ?>"
                             data-discount="<?= $deal['discount_percentage'] ?>"
                             data-availability="<?= $deal['current_participants'] >= $deal['max_participants'] ? 'full' : 'open' ?>">
                            <img src="<?= sanitize_input($deal['product_image']) ?>" alt="<?= sanitize_input($deal['product_name']) ?>">
                            <div class="deal-details">
                                <h3><?= sanitize_input($deal['product_name']) ?></h3>
                                <div class="price-info">
                                    <span class="original-price">$<?= number_format($deal['original_price'], 2) ?></span>
                                    <span class="group-price">$<?= number_format($deal['group_price'], 2) ?></span>
                                    <span class="discount-badge"><?= $deal['discount_percentage'] ?>% OFF</span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?= $deal['participation_percentage'] ?>%"></div>
                                    <span class="progress-text"><?= $deal['current_participants'] ?>/<?= $deal['max_participants'] ?> Joined</span>
                                </div>
                                <div class="deal-timer">
                                    <span>Ends in: <?= $deal['days_left'] ?> days</span>
                                </div>
                                <button class="btn btn-primary join-group-buy" 
                                        data-group-buy-id="<?= $deal['group_buy_id'] ?>">
                                    Join Group Buy
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-deals-message">
                    <p>No active group buy deals at the moment. Check back soon!</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Enhanced JavaScript for Filtering and Interaction -->
    <script>
        // Simple client-side filtering
        document.addEventListener('DOMContentLoaded', function() {
            const categoryFilter = document.getElementById('category-filter');
            const discountFilter = document.getElementById('discount-filter');
            const availabilityFilter = document.getElementById('availability-filter');
            const dealCards = document.querySelectorAll('.deal-card');

            function filterDeals() {
                dealCards.forEach(card => {
                    const category = categoryFilter.value;
                    const discount = discountFilter.value;
                    const availability = availabilityFilter.value;

                    const cardCategory = card.getAttribute('data-category');
                    const cardDiscount = parseInt(card.getAttribute('data-discount'));
                    const cardAvailability = card.getAttribute('data-availability');

                    const categoryMatch = !category || cardCategory === category;
                    const discountMatch = !discount || cardDiscount >= parseInt(discount);
                    const availabilityMatch = !availability || cardAvailability === availability;

                    card.style.display = (categoryMatch && discountMatch && availabilityMatch) ? 'block' : 'none';
                });
            }

            categoryFilter.addEventListener('change', filterDeals);
            discountFilter.addEventListener('change', filterDeals);
            availabilityFilter.addEventListener('change', filterDeals);
        });
    </script>

    <?php include 'includes/footer.php'; ?>

</body>
</html>

<?php
mysqli_close($conn);
?>
