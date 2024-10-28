<?php
// Include header and database connection file
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/../../includes/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Function to verify payment using PayChangu API
function verifyPayment($tx_ref, $secret_key)
{
    $url = "https://api.paychangu.com/verify-payment/" . urlencode($tx_ref);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Authorization: Bearer $secret_key"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['error' => true, 'message' => $error_msg];
    }

    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    } else {
        return ['error' => true, 'message' => "Failed to verify payment. HTTP Code: $httpCode"];
    }
}

// Check if the URL has a 'tx_ref' parameter
if (isset($_GET['tx_ref'])) {
    $tx_ref = $_GET['tx_ref'];
    $secret_key = 'SEC-TEST-HbZLvaA5yyiXIaUsg0eYT2t5tfl56DsR';

    // Verify the payment
    $paymentVerification = verifyPayment($tx_ref, $secret_key);

    if ($paymentVerification && $paymentVerification['status'] == 'success') {
        $user_id = $_SESSION['user_id'];
        $order_id = $paymentVerification['data']['customization']['description'];
        $amount = $paymentVerification['data']['amount'];
        $payment_method = "mobile_money";
        $payment_status = "successful";

        $stmt = $pdo->prepare("INSERT INTO Payments (order_id, user_id, reference_id, payment_status, payment_method, amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $tx_ref, $payment_status, $payment_method, $amount]);

        $currentUrl = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $currentUrl");
        exit();
    } else {
        $user_id = $_SESSION['user_id'];
        $order_id = $paymentVerification['data']['customization']['order_id'];
        $amount = $paymentVerification['data']['amount'];
        $payment_method = "mobile_money";
        $payment_status = $paymentVerification['status'];

        $stmt = $pdo->prepare("INSERT INTO Payments (order_id, user_id, reference_id, payment_status, payment_method, amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $tx_ref, $payment_status, $payment_method, $amount]);

        $currentUrl = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $currentUrl");
        exit();
    }
}

// Initialize arrays for menus and advertisements
$menus = [];
$ads = [];

// Fetch User Data
if (isset($_SESSION['email'])) {
    $sql_get_user = "SELECT * FROM Users WHERE email = :email";
    $stmt_get_user = $pdo->prepare($sql_get_user);
    $stmt_get_user->execute(['email' => $_SESSION['email']]);
    $user = $stmt_get_user->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['residence'] = $user['residence'];
    } else {
        header('Location: ../login.php');
        exit;
    }
}

// Fetch Menus and their Items
$sql_menus = "SELECT * FROM Menus";
$result_menus = $pdo->query($sql_menus);

if ($result_menus->rowCount() > 0) {
    while ($row = $result_menus->fetch(PDO::FETCH_ASSOC)) {
        $menu_id = $row['id'];
        $menu_items = [];

        $sql_items = "SELECT * FROM Menu_Items WHERE menu_id = :menu_id";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute(['menu_id' => $menu_id]);

        while ($item = $stmt_items->fetch(PDO::FETCH_ASSOC)) {
            $menu_items[] = $item;
        }

        $row['items'] = $menu_items;
        $menus[] = $row;
    }
}

// Fetch Advertisement data
$sql_ads = "SELECT * FROM Ads";
$result_ads = $pdo->query($sql_ads);

if ($result_ads->rowCount() > 0) {
    while ($ad = $result_ads->fetch(PDO::FETCH_ASSOC)) {
        $ads[] = [
            'id' => $ad['id'],
            'title' => $ad['title'],
            'description' => $ad['description'],
            'image' => $ad['image'], // Store binary image data
        ];
    }
}

// Function to add an item to the cart and record it in the user's order
function addToCart($pdo, $userId, $menuItemId, $quantity)
{
    try {
        $pdo->beginTransaction();
        $sql_cart = "SELECT id FROM Cart WHERE user_id = :user_id";
        $stmt_cart = $pdo->prepare($sql_cart);
        $stmt_cart->execute(['user_id' => $userId]);
        $cart = $stmt_cart->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            $sql_create_cart = "INSERT INTO Cart (user_id) VALUES (:user_id)";
            $stmt_create_cart = $pdo->prepare($sql_create_cart);
            $stmt_create_cart->execute(['user_id' => $userId]);
            $cartId = $pdo->lastInsertId();
        } else {
            $cartId = $cart['id'];
        }

        $sql_check_item = "SELECT id, quantity FROM Cart_Items WHERE cart_id = :cart_id AND menu_item_id = :menu_item_id";
        $stmt_check_item = $pdo->prepare($sql_check_item);
        $stmt_check_item->execute(['cart_id' => $cartId, 'menu_item_id' => $menuItemId]);
        $existingItem = $stmt_check_item->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            $sql_update_item = "UPDATE Cart_Items SET quantity = :quantity WHERE id = :id";
            $stmt_update_item = $pdo->prepare($sql_update_item);
            $stmt_update_item->execute(['quantity' => $newQuantity, 'id' => $existingItem['id']]);
        } else {
            $sql_add_item = "INSERT INTO Cart_Items (cart_id, menu_item_id, quantity, price) 
                             SELECT :cart_id, id, :quantity, price FROM Menu_Items WHERE id = :menu_item_id";
            $stmt_add_item = $pdo->prepare($sql_add_item);
            $stmt_add_item->execute(['cart_id' => $cartId, 'quantity' => $quantity, 'menu_item_id' => $menuItemId]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error adding item to cart and order: " . $e->getMessage());
        return false;
    }
}

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $menuItemId = $_POST['menu_item_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    addToCart($pdo, $_SESSION['user_id'], $menuItemId, $quantity);
}
?>


<!-- Add assets special to menu -->
<link rel="stylesheet" href="/../soReal/assets/css/menu.css">
</head>

<body>
    <!-- Navigation component -->
    <?php require __DIR__ . '/../../includes/navigation.php'; ?>

    <!-- Check if there are any ads before displaying the carousel -->
    <?php if (!empty($ads)): ?>
        <!-- Advertisement Carousel -->
        <div class="carousel-container">
            <div class="carousel">
                <?php foreach ($ads as $ad): ?>
                    <div class="carousel-item">
                        <?php if (!empty($ad['image'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($ad['image']); ?>" alt="Ad" />
                        <?php else: ?>
                            <img src="https://via.placeholder.com/800x400" />
                        <?php endif; ?>
                        <div class="carousel-description"><?php echo htmlspecialchars($ad['description']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-button prev" onclick="moveSlide(-1)">&#10094;</button>
            <button class="carousel-button next" onclick="moveSlide(1)">&#10095;</button>
        </div>
    <?php endif; ?>



    <!-- Actual menu -->
    <div class="menu-section">
        <h2 class="menu-title">Our Menus</h2>

        <?php if (empty($menus)): ?>
            <div style="text-align: center; font-size: 3rem; color: #e74c3c; margin-top: 50px; margin-bottom: 190px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                <span id="message"></span>
            </div>
            <div id="fun-facts" style="text-align: center; font-size: 1.5rem; color: #555; margin-top: 20px;">
                <span id="fact"></span>
            </div>
        <?php else: ?>
            <?php foreach ($menus as $menu): ?>
                <?php if (!empty($menu['items'])): ?>
                    <div class="menu-category">
                        <h3 class="category-title"><?php echo htmlspecialchars($menu['name']); ?></h3>
                        <div class="menu-items-grid">
                            <?php foreach ($menu['items'] as $item): ?>
                                <div class="menu-item-card">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($item['image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image" />
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/200"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image" />
                                    <?php endif; ?>
                                    <div class="item-info">
                                        <h4 class="item-title"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <span class="item-price">Mwk <?php echo number_format($item['price'], 2); ?></span>
                                        <form method="POST" action="">
                                            <input type="hidden" name="menu_item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                            <input type="number" name="quantity" value="1" min="1" max="99"
                                                style="width: 50px; padding: 5px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px; text-align: center; font-size: 14px;">
                                            <button class="add-to-cart-btn" name="add_to_cart" type="submit">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Typing effect for the message
        const message = "NO MENUS CURRENTLY AVAILABLE, COME BACK LATER";
        const messageElement = document.getElementById('message');
        let index = 0;

        function typeMessage() {
            if (index < message.length) {
                messageElement.innerHTML += message.charAt(index);
                index++;
                setTimeout(typeMessage, 100); // Adjust typing speed here
            } else {
                showFunFacts();
            }
        }

        const funFacts = [
            "Did you know? The longest menu ever recorded was 1000 pages long!",
            "Food menus have existed for over 250 years!",
            "The word 'menu' comes from the French word 'mener' which means 'to lead'.",
            "The world's most expensive dish is 'FleurBurger 5000' at Fleur in Las Vegas, costing $5,000!",
            "Eating in a restaurant boosts your mood and reduces stress!",
            "The first printed menu was used in 1765 at the Careme restaurant in Paris.",
            "Menus are often color-coded to influence what you order.",
            "In Japan, it's common to slurp noodles as a sign of enjoyment.",
            "The concept of 'Tasting Menus' was popularized by chefs in France in the late 20th century.",
            "In many cultures, it's considered polite to finish everything on your plate.",
            "The word 'a la carte' means 'by the menu' in French, referring to ordering individual dishes.",
            "The average person spends 67 minutes a week looking at menus.",
            "The Michelin Guide, which rates restaurants, was first published in 1900!",
            "In Italy, you can find 'Menu del Giorno,' a fixed-price menu for lunch!",
            "In some countries, it’s common to see menus with pictures to attract customers."
        ];


        let factIndex = 0;

        function showFunFacts() {
            const factElement = document.getElementById('fact');

            if (factIndex < funFacts.length) {
                factElement.innerHTML = funFacts[factIndex];
                factIndex++;
                setTimeout(showFunFacts, 4000); // Display each fact for 4 seconds
            }
        }

        // Start the typing effect when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', () => {
            typeMessage();
        });
    </script>


    <!-- Footer -->
    <?php include __DIR__ . '/../../includes/footer.php'; ?>

</body>

<script>
    let currentIndex = 0;

    function moveSlide(direction) {
        const items = document.querySelectorAll('.carousel-item');
        const itemCount = items.length;

        currentIndex += direction;

        // Loop back to the first or last item
        if (currentIndex < 0) {
            currentIndex = itemCount - 1;
        } else if (currentIndex >= itemCount) {
            currentIndex = 0;
        }

        const carousel = document.querySelector('.carousel');
        carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
    }

    setInterval(() => {
        moveSlide(1);
    }, 5000); // Change slide every 5 seconds 
</script>

</html>