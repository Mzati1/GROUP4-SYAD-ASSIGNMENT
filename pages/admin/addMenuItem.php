<?php
include_once __DIR__ . '/../../includes/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Check if menu_id is set in the URL
if (!isset($_GET['menu_id'])) {
    header('Location: dashboard.php');
    exit;
}

$menuId = $_GET['menu_id']; // Get the menu ID from the URL

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = null;

    // Handle file upload if an image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > 16 * 1024 * 1024) {
            echo "<p style='color: red;'>Error: File size exceeds the maximum limit of 16 MB.</p>";
            exit;
        }
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }
    $stmt = $pdo->prepare("INSERT INTO Menu_Items (menu_id, name, description, price, image) VALUES (:menu_id, :name, :description, :price, :image)");

    $stmt->bindParam(':menu_id', $menuId);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':image', $image, PDO::PARAM_LOB);

    try {
        if ($stmt->execute()) {
            // Redirect to dashboard.php after successfully adding the item
            header("Location: dashboard.php");
            exit; // Ensure no further processing occurs after the redirect
        } else {
            echo "<p style='color: red;'>Error: Unable to add menu item.</p>";
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
    <title>Add Menu Item</title>
    <style>
        :root {
            --bg-color: #17181d;
            --sidebar-bg-color: #282a36;
            --primary-color: #6272a4;
            --text-color: #f8f8f2;
            --secondary-text-color: #bd93f9;
            --hover-color: #44475a;
            --transition-duration: 0.3s;
            --glass-bg: rgba(169, 168, 166, 0.192);
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
            max-width: 500px;
        }

        .glass-card {
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            padding: var(--padding);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            transition: var(--transition-duration);
        }

        h2 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            transition: var(--transition-duration);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            border-color: var(--secondary-text-color);
            outline: none;
        }

        textarea {
            resize: vertical;
            height: 80px;
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
            transition: background-color var(--transition-duration);
        }

        button:hover {
            background-color: var(--hover-color);
        }

        @media (max-width: 600px) {
            .glass-card {
                width: 90%;
                padding: 15px;
            }

            h2 {
                font-size: 1.5rem;
            }

            input[type="text"],
            input[type="number"],
            textarea {
                font-size: 1rem;
            }

            button {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="glass-card">
            <h2>Add Menu Item</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>

                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" required>

                <label for="image">Image:</label>
                <input type="file" id="image" name="image" accept="image/*">

                <button type="submit">Add Item</button>
                <button type="button" onclick="window.history.back()" style="background-color: var(--hover-color); border: none; border-radius: var(--border-radius); color: var(--text-color); padding: 10px; margin-top: 10px; font-weight: bold; cursor: pointer;">Back</button>
            </form>
        </div>

    </div>
</body>

</html>