<?php
include_once __DIR__ . '/../../includes/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input
    $code = trim($_POST['code']);
    $discountValue = $_POST['discount_value'];
    $discountType = $_POST['discount_type'];
    $maxUses = $_POST['max_uses'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Prepare the SQL statement
    $stmt = $pdo->prepare("INSERT INTO Discount_Codes (code, discount_value, discount_type, usage_count, max_uses, start_date, end_date) VALUES (:code, :discount_value, :discount_type, 0, :max_uses, :start_date, :end_date)");

    // Bind parameters
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':discount_value', $discountValue);
    $stmt->bindParam(':discount_type', $discountType);
    $stmt->bindParam(':max_uses', $maxUses);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);

    try {
        // Execute the statement
        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<p style='color: red;'>Error: Unable to add discount code.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Discount Code</title>
    <style>
        :root {
            --bg-color: #17181d;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --text-color: #f8f8f2;
            --primary-color: #6272a4;
            --hover-color: #44475a;
            --border-radius: 8px;
            --padding: 20px;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--bg-color);
            font-family: Arial, sans-serif;
            color: var(--text-color);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 15px;
            width: 100%;
            max-width: 600px;
        }

        .glass-card {
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            padding: var(--padding);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            transition: var(--transition-duration);
            padding: 40px;
        }

        h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            /* Space between inputs */
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 90%;
            padding: 10px;
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            transition: border-color 0.3s;
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            border: none;
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: var(--hover-color);
        }

        .back-button {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid var(--primary-color);
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: var(--hover-color);
        }

        @media (max-width: 600px) {
            .grid-container {
                grid-template-columns: 1fr;
                /* Stack inputs on smaller screens */
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="glass-card">
            <h2>Add Discount Code</h2>
            <form method="POST" action="">
                <div class="grid-container">
                    <div>
                        <label for="code">Code:</label>
                        <input type="text" id="code" name="code" required>
                    </div>

                    <div>
                        <label for="discount_value">Discount Value:</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" required>
                    </div>

                    <div>
                        <label for="discount_type">Discount Type:</label>
                        <select id="discount_type" name="discount_type" required>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>

                    <div>
                        <label for="max_uses">Max Uses:</label>
                        <input type="number" id="max_uses" name="max_uses" required>
                    </div>

                    <div>
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>

                    <div>
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>

                <button type="submit">Add Discount Code</button>
            </form>
            <button class="back-button" onclick="history.back();">Back</button>
        </div>
    </div>
</body>

</html>