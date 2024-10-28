<?php
// Start the session to store error messages
session_start();

// Include database connection
include_once __DIR__ . '/../../includes/database.php';

// Check if the right session is set
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // User is not logged in, redirect to login page
    header('Location: ../login.php');
    exit;;
}

// Function to execute a query and handle errors
function executeQuery($pdo, $query, $params = [])
{
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Handle the exception (log it, show a message, etc.)
        // For now, we'll just echo the message for debugging
        echo 'Database error: ' . htmlspecialchars($e->getMessage());
        return null;
    }
}

// Get the logged-in admin's details
$userId = $_SESSION['user_id']; // Assuming user_id is stored in session after login
$userQuery = "SELECT name, email FROM Users WHERE id = :user_id";
$userStmt = executeQuery($pdo, $userQuery, [':user_id' => $userId]);
$user = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : null;

// Queries to retrieve metrics for the dashboard
$totalRevenueQuery = "SELECT SUM(amount) AS total_revenue FROM Payments WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$totalRevenueStmt = executeQuery($pdo, $totalRevenueQuery);
$totalRevenue = $totalRevenueStmt ? $totalRevenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0 : 0;

$totalUsersQuery = "SELECT COUNT(*) AS total_users FROM Users WHERE role != 'admin'";
$totalUsersStmt = executeQuery($pdo, $totalUsersQuery);
$totalUsers = $totalUsersStmt ? $totalUsersStmt->fetch(PDO::FETCH_ASSOC)['total_users'] : 0;

$ordersTodayQuery = "SELECT COUNT(*) AS orders_today FROM Orders WHERE DATE(created_at) = CURRENT_DATE()";
$ordersTodayStmt = executeQuery($pdo, $ordersTodayQuery);
$ordersToday = $ordersTodayStmt ? $ordersTodayStmt->fetch(PDO::FETCH_ASSOC)['orders_today'] : 0;

// Query to get current month's revenue
$monthlyRevenueQuery = "SELECT SUM(amount) AS monthly_revenue FROM Payments WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$monthlyRevenueStmt = executeQuery($pdo, $monthlyRevenueQuery);
$monthlyRevenue = $monthlyRevenueStmt ? $monthlyRevenueStmt->fetch(PDO::FETCH_ASSOC)['monthly_revenue'] : 0;

// Query to get previous month's revenue
$previousMonthRevenueQuery = "SELECT SUM(amount) AS previous_month_revenue FROM Payments WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
$previousMonthRevenueStmt = executeQuery($pdo, $previousMonthRevenueQuery);
$previousMonthRevenue = $previousMonthRevenueStmt ? $previousMonthRevenueStmt->fetch(PDO::FETCH_ASSOC)['previous_month_revenue'] : 0;

// Calculate dynamic projected growth percentage
$projectedGrowth = 0;
if ($previousMonthRevenue > 0) {
    $projectedGrowth = (($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
} else if ($monthlyRevenue > 0) {
    $projectedGrowth = 100;
}

$totalAdsQuery = "SELECT COUNT(*) AS active_ads FROM Ads";
$totalAdsStmt = executeQuery($pdo, $totalAdsQuery);
$activeAds = $totalAdsStmt ? $totalAdsStmt->fetch(PDO::FETCH_ASSOC)['active_ads'] : 0;

$discountCodesQuery = "SELECT COUNT(*) AS discount_codes FROM Discount_Codes WHERE end_date >= CURRENT_DATE()";
$discountCodesStmt = executeQuery($pdo, $discountCodesQuery);
$discountCodesAvailable = $discountCodesStmt ? $discountCodesStmt->fetch(PDO::FETCH_ASSOC)['discount_codes'] : 0;

// Complex Queries (Getting the table data)

// ORDERS
$ordersQuery = "SELECT o.id, u.name AS customer_name, o.created_at, o.status 
                FROM Orders o 
                JOIN Users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
$ordersStmt = executeQuery($pdo, $ordersQuery);
$orders = $ordersStmt ? $ordersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// USERS (CUSTOMERS ONLY)
$usersQuery = "SELECT id, name, email, role, residence, created_at FROM Users WHERE role = 'customer' ORDER BY created_at DESC";
$usersStmt = executeQuery($pdo, $usersQuery);
$users = $usersStmt ? $usersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// ADS
$adsQuery = "SELECT id, title, description, image, created_at, updated_at FROM Ads ORDER BY created_at DESC";
$adsStmt = executeQuery($pdo, $adsQuery);
$ads = $adsStmt ? $adsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// PAYMENTS
$paymentsQuery = "SELECT u.name AS user_name, p.reference_id, p.payment_status, p.payment_method, p.amount, p.created_at, p.updated_at 
                  FROM Payments p 
                  JOIN Users u ON p.user_id = u.id 
                  ORDER BY p.created_at DESC";
$paymentsStmt = executeQuery($pdo, $paymentsQuery);
$payments = $paymentsStmt ? $paymentsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// DISCOUNTS
$discountsQuery = "SELECT id, code, discount_value, discount_type, max_uses - usage_count AS usages_left, start_date, end_date, created_at, updated_at 
                   FROM Discount_Codes 
                   WHERE end_date >= CURRENT_DATE() 
                   ORDER BY usages_left DESC, created_at DESC";
$discountsStmt = executeQuery($pdo, $discountsQuery);
$discounts = $discountsStmt ? $discountsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

//DELIVERIES
// Prepare and execute the query to fetch all pending orders, ordered by oldest first
$deliveriesQuery = $pdo->prepare("
    SELECT o.id AS id, 
           u.name AS customer_name, 
           u.residence AS customer_residence,
           o.created_at, 
           o.status 
    FROM Orders o 
    JOIN Users u ON o.user_id = u.id 
    WHERE o.status = 'pending' 
    ORDER BY o.created_at ASC
");

$deliveriesQuery->execute();
$deliveries = $deliveriesQuery->fetchAll(PDO::FETCH_ASSOC);

//MENUS
// SQL query to fetch menus and their items
$menusQuery = "
    SELECT m.id AS menu_id, m.name AS menu_name, 
           mi.id AS item_id, mi.name AS item_name, 
           mi.price AS item_cost, mi.created_at AS date_added
    FROM Menus m
    LEFT JOIN Menu_Items mi ON m.id = mi.menu_id
    ORDER BY m.created_at DESC
";

// Execute the query
$menusStmt = $pdo->prepare($menusQuery);
$menusStmt->execute();
$menuData = $menusStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize the menus and their items with IDs
$menus = [];
foreach ($menuData as $row) {
    $menuId = $row['menu_id'];
    if (!isset($menus[$menuId])) {
        $menus[$menuId] = [
            'id' => $menuId,
            'name' => $row['menu_name'],
            'items' => []
        ];
    }

    // Add menu item details, including item ID
    if ($row['item_name']) {
        $menus[$menuId]['items'][] = [
            'id' => $row['item_id'],
            'name' => $row['item_name'],
            'cost' => $row['item_cost'],
            'date_added' => $row['date_added']
        ];
    }
}

// Now conditionally handling data depending on what edit/delete button has been clicked
// (Implementation of button handling logic goes here)

//DELETING USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUser'])) {
    $userId = $_POST['userId'];

    if (!empty($userId) && is_numeric($userId)) {
        $success = deleteRecord($pdo, 'Users', $userId);
        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;;
        } else {
            echo "Failed to delete the user.";
        }
    } else {
        echo "Invalid user ID.";
    }
}

//delete Orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteOrder'])) {
    $orderId = $_POST['orderId'];

    if (!empty($orderId) && is_numeric($orderId)) {
        $success = deleteRecord($pdo, 'Orders', $orderId);
        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to delete the order.";
        }
    } else {
        echo "Invalid order ID.";
    }
}

//delete ADS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteAd'])) {
    $adId = $_POST['adId'];

    if (!empty($adId) && is_numeric($adId)) {
        $success = deleteRecord($pdo, 'Ads', $adId);
        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to delete the ad.";
        }
    } else {
        echo "Invalid ad ID.";
    }
}


//menu deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteMenu'])) {
    $menuId = $_POST['menuId'];

    if (!empty($menuId) && is_numeric($menuId)) {
        $success = deleteRecord($pdo, 'Menus', $menuId);
        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to delete the menu.";
        }
    } else {
        echo "Invalid menu ID.";
    }
}

// menu item deleteion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteMenuItem'])) {
    $menuItemId = $_POST['menuItemId'];

    if (!empty($menuItemId) && is_numeric($menuItemId)) {
        $success = deleteMenuItem($pdo, $menuItemId);
        if ($success) {
            // Reload the page to reflect the deleted item
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to delete the menu item.";
        }
    } else {
        echo "Invalid menu item ID.";
    }
}

//discount deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteDiscountItem'])) {
    $discountItem = $_POST['discountId'];

    if (!empty($discountItem) && is_numeric($discountItem)) {
        $success = deleteRecord($pdo, 'Discount_Codes', $discountItem);
        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to delete the menu item.";
        }
    } else {
        echo "Invalid menu item ID.";
    }
}

// Completing a order

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completeDelivery'])) {
    // Retrieve the order ID from the POST request
    $deliveryId = $_POST['deliveryId'];

    // Validate the order ID
    if (!empty($deliveryId) && is_numeric($deliveryId)) {
        // Prepare the SQL statement to update the order status
        $stmt = $pdo->prepare("UPDATE Orders SET status = 'completed' WHERE id = :deliveryId");
        $stmt->bindParam(':deliveryId', $deliveryId, PDO::PARAM_INT);

        // Execute the query
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to update the order status.";
        }
    } else {
        echo "Invalid order ID.";
    }
}



// Functions 

// Check if the deleteUser form was submitted
function deleteRecord($pdo, $tableName, $id)
{
    if (empty($tableName) || !is_string($tableName) || !is_numeric($id)) {
        throw new InvalidArgumentException("Invalid parameters provided for deletion.");
    }
    $sql = "DELETE FROM " . htmlspecialchars($tableName) . " WHERE id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    } catch (PDOException $e) {
        echo "Error deleting record: " . $e->getMessage();
        return false;
    }
}

function deleteMenuItem(PDO $pdo, $menuItemId)
{
    $sql = "DELETE FROM Menu_Items WHERE id = :menuItemId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':menuItemId', $menuItemId, PDO::PARAM_INT);
    return $stmt->execute();
}

//going to places 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addMenuItem'])) {
    $menuId = $_POST['menuId'];

    // Build the URL without any fragments
    $redirectUrl = parse_url('addMenuItem.php?menu_id=' . urlencode($menuId));

    // Redirect to the specified URL
    header("Location: " . $redirectUrl['path'] . "?" . http_build_query(['menu_id' => $menuId]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addMenuMain'])) {
    $newMenuName = trim($_POST['newMenu']); // Use $newMenuName instead of $newMenu

    if (!empty($newMenuName)) { // Check against the correct variable
        // Use the executeQuery function to insert the new menu
        $query = "INSERT INTO Menus (name, created_at, updated_at) VALUES (:name, NOW(), NOW())";
        $params = ['name' => $newMenuName]; // Bind the correct variable

        $result = executeQuery($pdo, $query, $params);

        // Check if the insertion was successful
        if ($result) {
            // Optionally, redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Failed to add the new menu. Please try again.";
        }
    } else {
        echo "Menu name cannot be empty."; // Handling empty menu name
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/../soReal/assets/css/admin.css">
</head>

<body>
    <!-- Loader -->
    <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>

    <div id="dashboard" class="dashboard" style="display: none;">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav class="nav-menu">
                <a href="#home" class="nav-item" onclick="showTab('home')"><i class="fas fa-home"></i> Home</a>
                <a href="#orders" class="nav-item" onclick="showTab('orders')"><i class="fas fa-box"></i> Orders</a>
                <a href="#deliveries" class="nav-item" onclick="showTab('deliveries')"><i class="fas fa-box"></i>
                    Deliveries</a>
                <a href="#users" class="nav-item" onclick="showTab('users')"><i class="fas fa-users"></i> Users</a>
                <a href="#payments" class="nav-item" onclick="showTab('payments')"><i class="fas fa-credit-card"></i>
                    Payments</a>
                <a href="#ads" class="nav-item" onclick="showTab('ads')"><i class="fas fa-ad"></i> Ads</a>
                <a href="#discounts" class="nav-item" onclick="showTab('discounts')"><i class="fas fa-tags"></i>
                    Discounts</a>
                <a href="#ads" class="nav-item" onclick="showTab('menus')"><i class="fas fa-ad"></i> Menus</a>
            </nav>
            <!-- Profile Section -->
            <div class="profile">
                <img src="<?= htmlspecialchars($user['profile_image'] ?? 'https://avatar.iran.liara.run/public/job/police/male'); ?>"
                    alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h3>
                        <?= htmlspecialchars($user['name'] ?? 'Admin Name'); ?>
                    </h3>
                    <p>
                        <?= htmlspecialchars($user['email'] ?? 'admin@example.com'); ?>
                    </p>
                </div>
                <a href="/../soReal/pages/logout.php" class="logout-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <main class="content">
            <!-- Home Tab Content -->
            <div id="home" class="tab-content">
                <h1 class="home-title">Welcome to the Dashboard</h1>

                <div class="card-container">
                    <!-- Total Revenue Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <br>
                            <h3>Total Revenue</h3>
                        </div>
                        <br>
                        <p class="card-value">Mwk
                            <?= number_format($totalRevenue, 2); ?>
                        </p>
                        <p class="card-subtext">Total earnings this month</p>
                    </div>

                    <!-- Total Users Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <br>
                            <h3>Total Users</h3>
                        </div>
                        <br>
                        <p class="card-value">
                            <?= $totalUsers; ?>
                        </p>
                        <p class="card-subtext">User registrations since launch</p>
                    </div>

                    <!-- Orders Today Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <br>
                            <h3>Orders Today</h3>
                        </div>
                        <br>
                        <p class="card-value">
                            <?= $ordersToday; ?>
                        </p>
                        <p class="card-subtext">Orders placed today</p>
                    </div>

                    <!-- Monthly Revenue Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <br>
                            <h3>Monthly Revenue</h3>
                        </div>
                        <br>
                        <p class="card-value">$
                            <?= number_format($monthlyRevenue, 2); ?>
                        </p>
                        <p class="card-subtext">Projected growth: +
                            <?= $projectedGrowth; ?>%
                        </p>
                    </div>

                    <!-- Total Ads Active Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-ad"></i>
                            </div>
                            <br>
                            <h3>Total Ads Active</h3>
                        </div>
                        <br>
                        <p class="card-value">
                            <?= $activeAds; ?>
                        </p>
                        <p class="card-subtext">Active advertisements</p>
                    </div>

                    <!-- Discount Codes Available Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <br>
                            <h3>Discount Codes Available</h3>
                        </div>
                        <br>
                        <p class="card-value">
                            <?= $discountCodesAvailable; ?>
                        </p>
                        <p class="card-subtext">Total discount codes</p>
                    </div>
                </div>
            </div>
            <!-- Orders Tab Content -->
            <div id="orders" class="tab-content" style="display: none;">
                <h1 class="orders-title">Orders</h1>
                <p>Manage orders here.</p>

                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($order['id']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($order['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order['status']); ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="orderId"
                                                value="<?= htmlspecialchars($order['id']); ?>">
                                            <button type="submit" name="deleteOrder" class="btn delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>

            <div id="deliveries" class="tab-content" style="display: none;">
                <h1 class="orders-title">Deliveries</h1>
                <p>Manage Deliveries here.</p>

                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Delivery ID</th>
                                <th>Customer Name</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $delivery): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($delivery['id']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($delivery['customer_name']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($delivery['customer_residence']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($delivery['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($delivery['status']); ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="deliveryId"
                                                value="<?= htmlspecialchars($delivery['id']); ?>">
                                            <button type="submit" name="completeDelivery"
                                                class="btn deliveryComplete-btn">Complete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>

            <!-- Users Tab Content -->
            <div id="users" class="tab-content" style="display: none;">
                <h1 class="users-title">Users</h1>
                <p>Manage users here.</p>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Residence</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($user['id']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['role']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['residence']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($user['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="userId"
                                                value="<?= htmlspecialchars($user['id']); ?>">
                                            <button type="submit" name="deleteUser" class="btn delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>

            <!-- Payments Tab Content -->
            <div id="payments" class="tab-content" style="display: none;">
                <h1>Payments</h1>
                <p>Manage payments here.</p>

                <div class="table-container">
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Reference ID</th>
                                <th>Payment Status</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($payment['user_name']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($payment['reference_id']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($payment['payment_status']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($payment['payment_method']); ?>
                                    </td>
                                    <td>Mwk
                                        <?= htmlspecialchars(number_format($payment['amount'], 2)); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($payment['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($payment['updated_at']))->format('d M Y')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>

            <!-- Ads Tab Content -->
            <div id="ads" class="tab-content" style="display: none;">
                <h1>Ads</h1>
                <p>Manage ads here.</p>

                <a href="addAds.php">
                    <button class="btn btn-primary">Add New Ad</button>
                </a>

                <div class="table-container">
                    <table class="ads-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($ad['title']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($ad['description']); ?>
                                    </td>
                                    <td><img src="<?= htmlspecialchars($ad['image']); ?>"
                                            alt="<?= htmlspecialchars($ad['title']); ?>"
                                            style="width: 100px; height: auto;"></td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($ad['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($ad['updated_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">

                                            <input type="hidden" name="adId" value="<?= htmlspecialchars($ad['id']); ?>">
                                            <button type="submit" name="deleteAd" class="btn delete-btn">Delete</button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>

            <!-- Discounts Tab Content -->
            <div id="discounts" class="tab-content" style="display: none;">
                <h1>Discounts</h1>
                <p>Manage discount codes here.</p>

                <!-- Button to Add New Discount -->
                <a href="addDiscount.php">
                    <button onclick="openAddDiscountModal()" class="btn btn-primary">Add New Discount</button>
                </a>

                <div class="table-container">
                    <table class="discounts-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount Value</th>
                                <th>Discount Type</th>
                                <th>Usages Left</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discounts as $discount): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($discount['code']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($discount['discount_value']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($discount['discount_type']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($discount['usages_left']); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($discount['start_date']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($discount['end_date']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($discount['created_at']))->format('d M Y')); ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((new DateTime($discount['updated_at']))->format('d M Y')); ?>
                                    </td>

                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="discountId"
                                                value="<?= htmlspecialchars($discount['id']); ?>">

                                            <button type="submit" name="deleteDiscountItem"
                                                class="btn delete-btn">Delete</button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn pagination-btn previous">Previous</button>
                    <button class="btn pagination-btn next">Next</button>
                </div>
            </div>
            <!--menus-->
            <div id="menus" class="tab-content" style="display: none;">
                <h1 class="orders-title">Menus</h1>
                <p>Manage Menus here.</p>

                <!-- Form to Add New Menu -->
                <form method="post" style="display: inline-flex; align-items: center;">
                    <input type="text" name="newMenu" placeholder="Enter menu name" required
                        style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-right: 10px; width: 200px;">
                    <button type="submit" name="addMenuMain" class="btn btn-primary">Add New Menu</button>
                </form>

                <div class="menus-container">
                    <?php foreach ($menus as $menu): ?>
                        <div class="menu-card">
                            <div class="menu-header">
                                <div class="menu-title-container">
                                    <h2>
                                        <?= htmlspecialchars($menu['name']); ?>
                                    </h2>
                                    <form method="post" style="display: inline-flex;">
                                        <input type="hidden" name="menuId" value="<?= htmlspecialchars($menu['id']); ?>">
                                        <button type="submit" name="addMenuItem" class="btn edit-btn">Add item</button>
                                        <button type="submit" name="deleteMenu" class="btn delete-btn">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Cost</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menu['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($item['name']); ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($item['cost']); ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars((new DateTime($item['date_added']))->format('d M Y')); ?>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="menuId" value="<?= htmlspecialchars($menu['id']); ?>">
                                                    <input type="hidden" name="menuItemId" value="<?= htmlspecialchars($item['id']); ?>">
                                                    <button type="submit" name="deleteMenuItem" class="btn delete-btn">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>
</body>
<script src="https://kit.fontawesome.com/e99d0fa6e7.js" crossorigin="anonymous"></script>

<script>
    // Show the Home tab content by default and hide loader
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("loader").style.display = "none";
        document.getElementById("dashboard").style.display = "flex";
        showTab('home');
    });

    // Function to display the selected tab content
    function showTab(tabId) {
        // Hide all content sections
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
            content.classList.remove('fade-in');
        });

        // Display the selected section
        const selectedTab = document.getElementById(tabId);
        selectedTab.style.display = 'block';
        selectedTab.classList.add('fade-in');

        // Highlight the active tab
        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
        document.querySelector(`[href="#${tabId}"]`).classList.add('active');
    }
</script>

</html>