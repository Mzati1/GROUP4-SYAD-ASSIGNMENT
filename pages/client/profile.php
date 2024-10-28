<?php
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/database.php';

session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // User is not logged in, redirect to login page
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize user data array
$user = [];

// Fetch user data from the database
try {
    $stmt = $pdo->prepare("SELECT name, email, residence, profile_picture FROM Users WHERE id = :id AND is_deleted = FALSE");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        throw new Exception('User not found or deleted.');
    }
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit; // Stop execution if user not found
}

// Fetch orders for the user
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT O.id, 
               O.status, 
               MI.name AS item, 
               OI.quantity 
        FROM Orders O
        JOIN Order_Items OI ON O.id = OI.order_id
        JOIN Menu_Items MI ON OI.menu_item_id = MI.id
        WHERE O.user_id = :user_id
        ORDER BY O.id");
    $stmt->execute(['user_id' => $user_id]);
    $ordersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize orders with their items
    foreach ($ordersData as $order) {
        $orderId = $order['id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'status' => $order['status'],
                'items' => []
            ];
        }
        // Append item with its quantity
        $orders[$orderId]['items'][] = [
            'name' => $order['item'],
            'quantity' => $order['quantity']
        ];
    }
} catch (Exception $e) {
    echo 'Error fetching orders: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Fetch payments for the user
$payments = [];
try {
    $stmt = $pdo->prepare("SELECT P.id, O.id AS order_id, MI.name AS item, P.amount, P.payment_method, P.reference_id, P.payment_status 
                            FROM Payments P
                            JOIN Orders O ON P.order_id = O.id
                            JOIN Order_Items OI ON O.id = OI.order_id
                            JOIN Menu_Items MI ON OI.menu_item_id = MI.id
                            WHERE O.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo 'Error fetching payments: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Fetch discount codes along with the order they were used for
$discounts = [];
try {
    $stmt = $pdo->prepare("SELECT DU.order_id, DC.code, DC.discount_value FROM Discount_Codes DC
                            JOIN Discount_Usage DU ON DC.id = DU.discount_code_id
                            WHERE DU.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo 'Error fetching discounts: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Check if form was submitted to update data:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $residence = trim($_POST['residence']);
    $profilePicture = $_FILES['profile_picture'];

    // Validate input
    if (empty($name) || empty($residence)) {
        echo 'Error: All fields are required.';
    } else {
        try {
            // Update user data in the database
            $stmt = $pdo->prepare("UPDATE Users SET name = :name, residence = :residence WHERE id = :id");
            $stmt->execute(['name' => $name, 'residence' => $residence, 'id' => $user_id]);

            // Handle profile picture upload
            if ($profilePicture['error'] === UPLOAD_ERR_OK) {
                // Validate the uploaded file
                $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($profilePicture['type'], $allowedImageTypes)) {
                    $imageData = file_get_contents($profilePicture['tmp_name']);
                    $stmt = $pdo->prepare("UPDATE Users SET profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute(['profile_picture' => $imageData, 'id' => $user_id]);
                } else {
                    echo 'Error: Invalid file type. Only JPG, PNG, and GIF are allowed.';
                }
            }

            // Refresh the user data to reflect the changes
            $user['name'] = $name;
            $user['residence'] = $residence;

            // Reload user profile picture if it was updated
            $stmt = $pdo->prepare("SELECT profile_picture FROM Users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user['profile_picture'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            echo 'Error updating profile: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!-- Add assets special to profile -->
<link rel="stylesheet" href="/../soReal/assets/css/profile.css">
</head>

<body>
    <!-- Navigation Component -->
    <?php
    require __DIR__ . '/../../includes/navigation.php';
    ?>

    <div class="profile-background">
        <div class="profile-container">
            <div class="user-info">
                <div class="user-image">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($user['profile_picture']) ?>"
                            alt="User Profile" />
                    <?php else: ?>
                        <img src="https://avatar.iran.liara.run/public/7" alt="User Profile" />
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h2 class="user-name">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </h2>
                    <p class="user-email">Email:
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p class="user-residence">Residence:
                        <?php if (!empty($user['residence'])) {
                            echo htmlspecialchars($user['residence']);
                        } else {
                            echo "Not assigned";
                        }; ?>
                    </p>
                    <button id="edit-profile-btn">Edit Profile</button>

                    <a href="/../soReal/pages/logout.php">
                        <button id="logout-profile-btn">Logout</button>
                    </a>
                </div>
            </div>

            <!-- Editable Profile Form -->
            <div class="edit-profile-form" id="edit-profile-form" style="display: none;">
                <h3>Edit Profile</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                        required>

                    <label for="residence">Residence:</label>
                    <input type="text" id="residence" name="residence" value="<?php if (!empty($user['residence'])) {
                                                                                    echo htmlspecialchars($user['residence']);
                                                                                } else {
                                                                                    echo "";
                                                                                }; ?>" required>

                    <label for="profile_picture">Profile Picture:</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

                    <button type="submit">Update</button>
                    <button type="button" id="cancel-edit-btn">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabs for Orders, Payments, and Discount Codes -->
    <div class="tabs">
        <button class="tab" id="orders-tab" onclick="openTab(event, 'orders')">Orders</button>
        <button class="tab" id="payments-tab" onclick="openTab(event, 'payments')">Payments</button>
        <button class="tab" id="discounts-tab" onclick="openTab(event, 'discounts')">Discount Codes</button>
    </div>

    <div class="tab-content">
        <div id="orders" class="tab-pane active" style="display: block;">
            <h3>Your Orders</h3>
            <div class="orders-list">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $orderId => $order): ?>
                        <div class="order">
                            <div class="order-header">
                                <strong>Order #<?= htmlspecialchars($orderId) ?></strong>
                                <span class="order-status"><?= htmlspecialchars($order['status']) ?></span>
                            </div>
                            <ul class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <span class="item-quantity"><?= htmlspecialchars($item['quantity']) ?> x </span>
                                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="payments" class="tab-pane" style="display: none;">
            <h3>Your Payments</h3>
            <div class="payments-list">
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <div class="payment">
                            <strong>Order #<?= htmlspecialchars($payment['order_id']) ?></strong><br>
                            <div class="payment-details">
                                <span>Payment #<?= htmlspecialchars($payment['id']) ?>:</span>
                                <span class="price"><?= htmlspecialchars($payment['amount']) ?></span>
                                <span>(Paid via <strong><?= htmlspecialchars($payment['payment_method']) ?></strong>, Reference: <?= htmlspecialchars($payment['reference_id']) ?>, Status: <strong><?= htmlspecialchars($payment['payment_status']) ?></strong>)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-payments">No payments made.</div>
                <?php endif; ?>
            </div>
        </div>

        <div id="discounts" class="tab-pane" style="display: none;">
            <h3>Your Discount Codes</h3>
            <div class="discounts-list">
                <?php if (!empty($discounts)): ?>
                    <?php foreach ($discounts as $discount): ?>
                        <div class="discount">
                            <strong>Order #<?= htmlspecialchars($discount['order_id']) ?></strong><br>
                            <div class="discount-details">
                                Code: <strong><?= htmlspecialchars($discount['code']) ?></strong> - Discount: <strong><?= htmlspecialchars($discount['discount_value']) ?>%</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-discounts">No discount codes found.</div>
                <?php endif; ?>
            </div>
        </div>


    </div>

    <script>
        function openTab(evt, tabName) {
            // Hide all tab panes
            var tabPanes = document.getElementsByClassName('tab-pane');
            for (var i = 0; i < tabPanes.length; i++) {
                tabPanes[i].style.display = 'none';
            }
            // Remove active class from all tabs
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            // Show the current tab and add active class to the button that opened it
            document.getElementById(tabName).style.display = 'block';
            evt.currentTarget.classList.add('active');
        }

        // Toggle edit profile form
        document.getElementById('edit-profile-btn').onclick = function() {
            document.getElementById('edit-profile-form').style.display = 'block';
        };
        document.getElementById('cancel-edit-btn').onclick = function() {
            document.getElementById('edit-profile-form').style.display = 'none';
        };
    </script>
</body>

</html>