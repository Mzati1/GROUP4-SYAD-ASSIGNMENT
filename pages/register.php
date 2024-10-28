<?php
// Start the session to store error messages
session_start();
$error = "";

//include database
require '../includes/database.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and trim form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $residence = trim($_POST['residence']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
        } else {
            // Hash the password for security 
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO users (name, email,residence, password) VALUES (:name, :email, :residence, :password)");
            if ($stmt->execute(['name' => $name, 'email' => $email, 'residence' => $residence, 'password' => $hashed_password])) {

                // Redirect to login page
                header("Location: login.php");
                exit(); //end script
            } else {
                $error = "There was an error registering your account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>

<body>
    <div class="container">
        <div class="left-section">
            <div class="overlay">
                <h1>Welcome to <br> soReal</h1>
                <p>Don't miss out, join us today!</p>
            </div>
        </div>
        <div class="right-section">
            <form method="post" class="register-form" id="register-form">
                <!-- Display error message if any -->
                <?php if (!empty($error)): ?>
                    <p class="error-message" id="error-message">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                <h2>Create Your Account</h2>
                <div class="input-container">
                    <input type="text" id="name" name="name" placeholder="" required autocomplete="off">
                    <label for="name">Name</label>
                    <div class="input-bg"></div>
                </div>
                <div class="input-container">
                    <input type="email" id="email" name="email" placeholder="" required autocomplete="off">
                    <label for="email">Email</label>
                    <div class="input-bg"></div>
                </div>
                <div class="input-container">
                    <input type="text" id="residence" name="residence" placeholder="" required autocomplete="off">
                    <label for="residence">Residence</label>
                    <div class="input-bg"></div>
                </div>
                <div class="input-container">
                    <input type="password" id="password" name="password" placeholder="" required autocomplete="off">
                    <label for="password">Password</label>
                    <div class="input-bg"></div>
                </div>
                <div class="input-container">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="" required
                        autocomplete="off">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-bg"></div>
                </div>
                <button type="submit" id="register-button">Register</button>
                <p class="signup-link">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>
</body>

</html>