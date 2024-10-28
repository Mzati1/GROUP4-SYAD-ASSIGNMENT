<?php
// Start session
session_start();

// Include database connection
include_once __DIR__ . '/../includes/database.php';

// Check if the user is already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {

    // Redirect user based on their role
    if ($_SESSION['role'] === 'admin') {
        header('Location: ./admin/dashboard.php');
    } else {
        header('Location: ./client/menu.php');
    }

    // Stop further execution of the script
    exit();
}


$error = ''; // Variable to hold error messages

// Check if form is submitted using POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Trim inputs( kuchosa ma whitespaces )
    $email = trim($_POST['email']);
    $login_password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($login_password)) {
        // Error message if fields are empty
        $error = 'Both fields are required.';
    } else {

        //using try catch kuti if any error it should show 
        try {

            // Check if the user exists in the database
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // If user is found and password is correct (password check should be added)


            if ($user  /* &&  password_verify($login_password, $user['password'])*/) {

                // Set session variables
                $_SESSION['user_logged_in'] = true;
                $_SESSION['name'] = $user['name'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['residence'] = $user['residence'];

                // Redirect user based on their role
                if ($user['role'] == 'admin') {
                    header('Location: ./admin/dashboard.php');
                } else {
                    header('Location: ./client/menu.php');
                }
                exit();
            } else {
                // Error message for invalid credentials
                $error = 'Invalid email or password⚠️';
            }

            //check if a user even exists
            if (empty($user)) {
                $error = "This user does not exist, please create a new one!";
            }
        } catch (PDOException $e) {
            // Catch and log database errors
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="../assets/css/login.css">

    <!-- FontAwesome for Icons -->
    <script src="https://kit.fontawesome.com/e99d0fa6e7.js" crossorigin="anonymous"></script>

    <title>Login</title>
</head>

<body>

    <body>
        <div class="container">

            <!-- Left section with background image -->
            <div class="left-section">
                <div class="overlay">
                    <h1>Welcome Back!</h1>
                    <p>Please Log in to discover your next meal!</p>
                </div>
            </div>

            <!-- Right section with login form -->
            <div class="right-section">

                <form method="post" class="login-form" id="login-form">
                    <!-- Display error message if any -->
                    <?php if (!empty($error)): ?>
                        <p class="error-message" id="error-message">
                            <?php echo $error; ?>
                        </p>
                    <?php endif; ?>

                    <h2>Login to Your Account</h2>

                    <!-- Inputs  -->
                    <div class="input-container">
                        <input type="email" id="login-email" name="email" placeholder="" required autocomplete="off">
                        <label for="login-email">Email:</label>
                        <div class="input-bg"></div>
                    </div>

                    <div class="input-container">
                        <input type="password" id="login-password" name="password" placeholder="" required autocomplete="off">
                        <label for="login-password">Password:</label>
                        <div class="input-bg"></div>
                    </div>

                    <button type="submit" id="login-button">Login</button>

                    <!-- Signup link if no account -->
                    <p class="signup-link">Don’t have an account? <a href="register.php">Sign up here</a></p>
                </form>
            </div>

        </div>

    </body>

</body>

<script>
    // Button click animation
    const loginButton = document.getElementById('login-button');
    loginButton.addEventListener('mousedown', function() {
        this.style.transform = 'scale(0.95)';
        this.style.backgroundColor = '#cc8400';
    });

    loginButton.addEventListener('mouseup', function() {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = '#e6a500';
    });
</script>

</html>