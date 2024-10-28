<?php
// Start session
require __DIR__ . '/../includes/database.php';

// Initialize variables
$cart_item_count = 0;
$user_profile_picture = null;

// Check if the user is logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

    try {
        // Prepare and execute the SQL query to count items in the cart
        $sql_cart_count = "
            SELECT COUNT(ci.id) AS item_count
            FROM Cart c
            JOIN Cart_Items ci ON c.id = ci.cart_id
            WHERE c.user_id = :user_id
        ";

        $stmt_cart_count = $pdo->prepare($sql_cart_count);
        $stmt_cart_count->execute(['user_id' => $user_id]);
        $cart_count_result = $stmt_cart_count->fetch(PDO::FETCH_ASSOC);

        // Store the count in a variable
        $cart_item_count = $cart_count_result['item_count'] ?? 0;

        // Fetch the user's profile picture
        $sql_profile_picture = "SELECT profile_picture FROM Users WHERE id = :user_id";
        $stmt_profile_picture = $pdo->prepare($sql_profile_picture);
        $stmt_profile_picture->execute(['user_id' => $user_id]);
        $user_profile_picture = $stmt_profile_picture->fetchColumn();
    } catch (PDOException $e) {
        // Handle any errors (optional)
        echo "Error fetching cart count or profile picture: " . $e->getMessage();
    }
}
?>

<!-- CSS for the navbar -->
<link rel="stylesheet" href="/../soReal/assets/css/navigation.css">

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo and Brand Name -->
        <div class="logo-container">
            <img src="/../soReal/assets/images/logo.jpeg" alt="Logo" class="logo">
            <span class="brand-name">soReal</span>
        </div>

        <!-- Navigation links (center-aligned) -->
        <ul class="nav-links">
            <li><a href="/../soReal/pages/index.php">Home</a></li>
            <li><a href="/../soReal/pages/client/menu.php">Menu</a></li>
            <li><a href="/../soReal/pages/about_us.php">About Us</a></li>
            <li><a href="/../soReal/pages/contact.php">Contact</a></li>
        </ul>

        <!-- Right section with profile, cart, and search -->
        <div class="navbar-right">
            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <i class="fas fa-search"></i>
            </div>

            <!-- Cart Icon -->
            <div class="cart-icon">
                <a href="../client/cart.php" id="cart-button">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">
                        <?php
                        // Display the cart item count only if greater than zero
                        if ($cart_item_count > 0) {
                            echo htmlspecialchars($cart_item_count);
                        }
                        ?>
                    </span>
                </a>
            </div>

            <!-- Profile Placeholder Image should link to profile page -->
            <div class="profile-icon">
                <a href="/../soReal/pages/client/profile.php">
                    <?php if (!empty($user_profile_picture)): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($user_profile_picture) ?>" alt="User" class="user-avatar">
                    <?php else: ?>
                        <img src="https://avatar.iran.liara.run/public/7" alt="User" class="user-avatar">
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</nav>