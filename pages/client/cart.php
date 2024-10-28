<?php
// Includes
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/../../includes/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Get user's ID from session
$user_id = $_SESSION['user_id'];

// Handle removal of items from the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item_id'])) {
        $remove_item_id = $_POST['remove_item_id'];
        $sql = "DELETE FROM Cart_Items WHERE id = :item_id AND cart_id = (SELECT id FROM Cart WHERE user_id = :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['item_id' => $remove_item_id, 'user_id' => $user_id]);
    } elseif (isset($_POST['increment_item_id'])) {
        $increment_item_id = $_POST['increment_item_id'];
        $sql = "UPDATE Cart_Items SET quantity = quantity + 1 WHERE id = :item_id AND cart_id = (SELECT id FROM Cart WHERE user_id = :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['item_id' => $increment_item_id, 'user_id' => $user_id]);
    } elseif (isset($_POST['decrement_item_id'])) {
        $decrement_item_id = $_POST['decrement_item_id'];
        $sql = "UPDATE Cart_Items SET quantity = GREATEST(quantity - 1, 1) WHERE id = :item_id AND cart_id = (SELECT id FROM Cart WHERE user_id = :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['item_id' => $decrement_item_id, 'user_id' => $user_id]);
    } elseif (isset($_POST['checkout'])) {
        try {
            $pdo->beginTransaction();
            $sql = "SELECT id FROM Orders WHERE user_id = :user_id AND status = 'pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $existing_order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing_order) {
                $sql = "INSERT INTO Orders (user_id, status) VALUES (:user_id, 'pending')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $user_id]);
                $order_id = $pdo->lastInsertId();
            } else {
                $order_id = $existing_order['id'];
            }

            $sql = "
                SELECT ci.menu_item_id, ci.quantity, mi.price
                FROM Cart_Items ci
                JOIN Cart c ON ci.cart_id = c.id
                JOIN Menu_Items mi ON ci.menu_item_id = mi.id
                WHERE c.user_id = :user_id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cart_items as $cart_item) {
                $sql = "SELECT id FROM Order_Items WHERE order_id = :order_id AND menu_item_id = :menu_item_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['order_id' => $order_id, 'menu_item_id' => $cart_item['menu_item_id']]);
                $existing_order_item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_order_item) {
                    $sql = "UPDATE Order_Items SET quantity = :quantity WHERE id = :item_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'quantity' => $cart_item['quantity'],
                        'item_id' => $existing_order_item['id']
                    ]);
                } else {
                    $sql = "INSERT INTO Order_Items (order_id, menu_item_id, quantity, price) VALUES (:order_id, :menu_item_id, :quantity, :price)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'order_id' => $order_id,
                        'menu_item_id' => $cart_item['menu_item_id'],
                        'quantity' => $cart_item['quantity'],
                        'price' => $cart_item['price']
                    ]);
                }
            }
            $pdo->commit();
            header('Location: checkout.php?order_id=' . $order_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Error during checkout: " . $e->getMessage();
        }
    }
}

// Fetch user's cart items, including the image
$sql = "
    SELECT ci.id AS cart_item_id, mi.name AS item_name, mi.price, ci.quantity, mi.image AS item_image
    FROM Cart_Items ci
    JOIN Cart c ON ci.cart_id = c.id
    JOIN Menu_Items mi ON ci.menu_item_id = mi.id
    WHERE c.user_id = :user_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price = 0;
?>

<!-- Add assets specific to cart -->
<link rel="stylesheet" href="/../soReal/assets/css/cart.css">
</head>

<body>
    <!-- Navigation -->
    <?php require __DIR__ . '/../../includes/navigation.php'; ?>

    <!-- Cart Container -->
    <div class="cart-container">
        <h2>Your Shopping Cart</h2>

        <!-- Cart Items Wrapper (vertical scroll for overflow) -->
        <div class="cart-items-wrapper">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart-message">
                    <h3>Your cart is empty.</h3>
                    <p>Looks like you haven't added any items yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $item_total = $item['price'] * $item['quantity'];
                    $total_price += $item_total;
                    ?>
                    <div class="cart-item" data-cart-id="<?= $item['cart_item_id'] ?>">
                        <img src="data:image/jpeg;base64,<?= base64_encode($item['item_image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                        <div class="item-details">
                            <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                            <p>Price: MWK<?= number_format($item['price'], 2) ?></p>
                            <form method="POST" action="">
                                <div class="item-quantity">
                                    <button class="btn-decrease" type="submit" name="decrement_item_id" value="<?= $item['cart_item_id'] ?>" aria-label="Decrease quantity" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>-</button>
                                    <input type="number" name="new_quantity" value="<?= $item['quantity'] ?>" min="1" aria-label="Item quantity" class="quantity-input" readonly>
                                    <button class="btn-increase" type="submit" name="increment_item_id" value="<?= $item['cart_item_id'] ?>" aria-label="Increase quantity">+</button>
                                </div>
                            </form>
                        </div>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="remove_item_id" value="<?= $item['cart_item_id'] ?>">
                            <button class="btn-remove" type="submit" aria-label="Remove item">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($cart_items)): ?>
            <div class="cart-summary">
                <h3>Total Price: <span class="total-price">MWK<?= number_format($total_price, 2) ?></span></h3>
            </div>
        <?php endif; ?>

        <div class="checkout-button-wrapper">
            <?php if (empty($cart_items)): ?>
                <a href="menu.php" class="btn-continue-shopping" aria-label="Continue Shopping">Continue Shopping</a>
            <?php else: ?>
                <form method="POST" action="">
                    <button type="submit" class="btn-checkout" name="checkout" aria-label="Proceed to checkout">Proceed to Checkout</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>