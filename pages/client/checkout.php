<?php
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/database.php';

//start session
session_start();

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$total_price = 0;
$discount_amount = 0;
$discount_message = '';
$discount_applied = false;

// Fetch pending order and its items for the specific user
$orderQuery = "SELECT id FROM Orders WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1";
$orderStmt = $pdo->prepare($orderQuery);
$orderStmt->execute([$user_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

$order_items = [];
if ($order) {
    $order_id = $order['id'];

    // Fetch order items for the pending order
    $query = "SELECT oi.menu_item_id, mi.name AS item_name, oi.price AS item_price, oi.quantity 
              FROM Order_Items oi 
              JOIN Menu_Items mi ON oi.menu_item_id = mi.id 
              WHERE oi.order_id = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total price
    foreach ($order_items as $item) {
        $total_price += $item['item_price'] * $item['quantity'];
    }
}

// Handle discount code submission
// Handle discount code submission
// Handle discount code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['discount_code'])) {
    $discount_code = trim($_POST['discount_code']);

    if (!empty($discount_code)) {
        $discount_query = "SELECT id, discount_type, discount_value 
                           FROM Discount_Codes 
                           WHERE code = ? AND 
                                 (start_date IS NULL OR start_date <= NOW()) AND 
                                 (end_date IS NULL OR end_date >= NOW())";

        try {
            $discount_stmt = $pdo->prepare($discount_query);
            $discount_stmt->execute([$discount_code]);
            $discount = $discount_stmt->fetch(PDO::FETCH_ASSOC);

            if ($discount) {
                if ($discount['discount_type'] === 'percentage') {
                    $discount_amount = min($total_price * $discount['discount_value'] / 100, $total_price);
                    $discount_message = "Discount applied: {$discount['discount_value']}% off.";
                } else if ($discount['discount_type'] === 'fixed') {
                    $discount_amount = min($discount['discount_value'], $total_price);
                    $discount_message = "Discount applied: MWK {$discount['discount_value']} off.";
                }

                // Update total price after discount
                $total_price -= $discount_amount;
                $_SESSION['discount_applied'] = true;
                $discount_applied = true;

                // Assuming the user ID is stored in the session after login
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];

                    // Check if order ID is available
                    if ($order) {
                        $order_id = $order['id']; // Get the current order ID

                        // Insert into Discount_Usage table
                        $usage_query = "INSERT INTO Discount_Usage (discount_code_id, user_id, order_id) 
                                        VALUES (?, ?, ?)";
                        $usage_stmt = $pdo->prepare($usage_query);

                        // Execute and catch any exceptions
                        try {
                            $usage_stmt->execute([$discount['id'], $user_id, $order_id]);
                        } catch (PDOException $e) {
                            echo "Error recording discount usage: " . $e->getMessage();
                        }
                    } else {
                        echo "No pending order to associate with discount usage.";
                    }
                }
            } else {
                $discount_message = "Invalid discount code.";
            }
        } catch (PDOException $e) {
            echo "Database error while applying discount: " . $e->getMessage();
        }
    } else {
        $discount_message = "No discount code entered.";
    }
}


// Function to process the payment and mark order as completed
function processPayment($total_price, $order_id, $user_id)
{
    $api_url = "https://api.paychangu.com/payment";
    $api_key = "SEC-TEST-HbZLvaA5yyiXIaUsg0eYT2t5tfl56DsR";
    $title = "RECEIPT FOR " . $_SESSION['name'];
    $description = "$order_id";
    $amount = $total_price;
    $callback_url = "http://localhost:8888/soReal/pages/client/menu.php";
    $return_url = "http://localhost:8888/soReal/pages/client/menu.php";
    $user_fullname = $_SESSION['name'];
    $user_email = $_SESSION['email'];

    $tx_ref = "soReal_" . uniqid();

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'currency' => 'MWK',
            'customization' => [
                'title' => $title,
                'description' => $description,
            ],
            'amount' => $amount,
            'tx_ref' => $tx_ref,
            'first_name' => $user_fullname,
            'callback_url' => $callback_url,
            'return_url' => $return_url,
            'email' => $user_email,
        ]),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json",
            "Authorization: Bearer $api_key"
        ],

        CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification

    ]);

    try {
        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }

        $response_data = json_decode($response, true);

        if (
            isset($response_data['status']) && $response_data['status'] === 'success' &&
            isset($response_data['data']['checkout_url'])
        ) {
            $checkout_url = $response_data['data']['checkout_url'];

            // Update order status to completed
            global $pdo;
            $updateOrderQuery = "UPDATE Orders SET status = 'completed' WHERE id = ?";
            $updateOrderStmt = $pdo->prepare($updateOrderQuery);
            $updateOrderStmt->execute([$order_id]);

            // Clear cart items for the user
            $deleteCartItemsQuery = "DELETE FROM Cart_Items WHERE cart_id = (SELECT id FROM Cart WHERE user_id = ?)";
            $deleteCartItemsStmt = $pdo->prepare($deleteCartItemsQuery);
            $deleteCartItemsStmt->execute([$user_id]);

            header("Location: $checkout_url");
            exit();
        } else {
            echo "Payment initiation failed";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    } finally {
        curl_close($curl);
    }
}

// Handle order creation and payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay'])) {
    if ($order) {
        processPayment($total_price, $order['id'], $user_id);
        $_SESSION['discount_applied'] = false;
        $discount_applied = $_SESSION['discount_applied'];
    } else {
        echo "No pending order to process.";
        $_SESSION['discount_applied'] = false;
        $discount_applied = $_SESSION['discount_applied'];
    }
}
?>

<!-- Add assets specific to checkout -->
<link rel="stylesheet" href="/../soReal/assets/css/checkout.css">
</head>

<body>
    <div class="checkout-wrapper">
        <div class="checkout-container">
            <div class="header-section">
                <a class="go-back-btn" href="cart.php">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
                <h1 class="checkout-title">Checkout</h1>
            </div>

            <div class="order-summary">
                <h2 class="summary-title">Order Summary</h2>

                <?php if (empty($order_items)): ?>
                    <div class="no-orders-message">
                        <h3>You have no pending orders!</h3>
                    </div>
                <?php else: ?>
                    <div class="order-list">
                        <div class="order-item header">
                            <div class="item-label">Item</div>
                            <div class="price-label">Price</div>
                            <div class="quantity-label">Quantity</div>
                        </div>

                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                <div class="item-price">MWK<?php echo number_format($item['item_price'], 2); ?></div>
                                <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="total">
                        <span>Total:</span>
                        <span class="total-price">MWK<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <?php if ($discount_message): ?>
                        <div class="discount-message">
                            <span><?php echo $discount_message; ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Discount Code Section -->
            <div class="discount-container">
                <h2 class="discount-title">Apply Discount Code</h2>
                <form method="POST">
                    <div class="discount-input-container">
                        <input type="text" name="discount_code" class="discount-input" placeholder="Enter discount code"
                            <?php echo $discount_applied ? 'disabled' : ''; ?>> <!-- Disable if discount already applied -->
                        <button type="submit" class="apply-discount-btn"
                            <?php echo $discount_applied ? 'disabled' : ''; ?>>Apply</button> <!-- Disable button if discount already applied -->
                    </div>
                </form>
            </div>

            <form method="POST">
                <div class="checkout-button-container">
                    <button type="submit" name="pay" class="checkout-btn">Proceed to Payment</button>
                </div>
            </form>
        </div>

        <div class="footer">
            <p>Thank you for your order! Have a great day!</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const orderList = document.querySelector('.order-list');
            const orderItems = document.querySelectorAll('.order-item');

            if (orderItems.length >= 5) {
                orderList.classList.add('scroll-active');
            }
        });
    </script>
</body>

</html>