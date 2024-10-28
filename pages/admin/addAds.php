<?php
session_start();
include_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        $stmt = $pdo->prepare("INSERT INTO Ads (title, description, image) VALUES (:title, :description, :image)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image', $imageData, PDO::PARAM_LOB);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Error: Unable to create ad.";
        }
    } else {
        echo "Error uploading image: " . $_FILES['image']['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Ad</title>
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
            --padding: 15px;
            --border-radius: 10px;
            --font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: var(--bg-color);
            font-family: var(--font-family);
            color: var(--text-color);
        }

        .form-container {
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            padding: 40px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: background var(--transition-duration);
        }

        h2 {
            margin-bottom: 20px;
            font-size: 1.8em;
            color: var(--secondary-text-color);
        }

        input[type="text"],
        input[type="file"],
        textarea {
            width: calc(100% - 30px);
            padding: var(--padding);
            margin: 15px 0;
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            transition: border-color var(--transition-duration);
            font-size: 16px;
            outline: none;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: var(--secondary-text-color);
            box-shadow: 0 0 5px rgba(189, 147, 249, 0.5);
        }

        button {
            background-color: var(--primary-color);
            color: var(--text-color);
            padding: var(--padding);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 18px;
            transition: background-color var(--transition-duration);
            width: 100%;
            margin-top: 15px;
        }

        button:hover {
            background-color: var(--hover-color);
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 30px;
            }

            h2 {
                font-size: 1.5em;
            }

            button {
                padding: 10px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h2>Add New Ad</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Ad Title" required>
            <textarea name="description" placeholder="Ad Description" rows="4" required></textarea>
            <input type="file" name="image" accept="image/*" required>
            <button type="submit">Add Ad</button>
        </form>
    </div>

</body>

</html>