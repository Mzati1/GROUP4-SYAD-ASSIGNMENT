<?php
session_start();
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/database.php';

$userEmail = $userName = '';

if (isset($_SESSION['email'])) {
    $sql_get_user = "SELECT * FROM Users WHERE email = :email";
    $stmt_get_user = $pdo->prepare($sql_get_user);
    $stmt_get_user->execute(['email' => $_SESSION['email']]);
    $user = $stmt_get_user->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userEmail = htmlspecialchars($user['email']);
        $userName = htmlspecialchars($user['name']);
    }
}
?>

<link rel="stylesheet" href="./../assets/css/contact_us.css">
</head>

<body>
    <?php require __DIR__ . '/../includes/navigation.php'; ?>

    <div class="contact-container visible">
        <div class="contact-box fade-in">
            <h1 class="main-title">We’d Love to Hear From You!</h1>
            <h2 class="contact-heading">Contact Us</h2>
            <form action="contact_process.php" method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name"><i class="fa-solid fa-user"></i></label>
                    <input type="text" id="name" name="name" placeholder="Your Name" value="<?= $userName ?>">
                </div>
                <div class="form-group">
                    <label for="email"><i class="fa-solid fa-envelope"></i></label>
                    <input type="email" id="email" name="email" placeholder="Your Email" value="<?= $userEmail ?>">
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fa-solid fa-phone"></i></label>
                    <input type="tel" id="phone" name="phone" placeholder="Your Phone" value="">
                </div>
                <div class="form-group message-group">
                    <textarea id="message" name="message" placeholder="Your Message" rows="5"></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
        <div class="map-box">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3837.1352823208563!2d35.21427157545366!3d-15.901982025614107!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x18d9cad291cec51f%3A0xc98b4ac16beed81b!2sMalawi%20University%20of%20Science%20and%20Technology!5e0!3m2!1sen!2smw!4v1730125056179!5m2!1sen!2smw" width="800" height="700" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.querySelectorAll('.form-group input, .form-group textarea').forEach(input => {
            input.addEventListener('focus', () => input.parentElement.classList.add('active'));
            input.addEventListener('blur', () => input.parentElement.classList.remove('active'));
        });
    </script>

</body>

</html>