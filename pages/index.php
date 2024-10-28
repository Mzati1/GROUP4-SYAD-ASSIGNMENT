<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Restaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./../assets/css/index.css">

    <style>
        header {
            background: white;
            padding: 20px 2rem;
            position: sticky;
            top: 0;
            box-shadow: var(--box-shadow);
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-left .logo {
            color: var(--primary-color);
            font-size: 2rem;
            text-decoration: none;
        }

        .nav-right ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .nav-right ul li a {
            color: var(--text-color);
            text-decoration: none;
            font-size: 1rem;
            position: relative;
            transition: color 0.3s;
            padding: 5px 0;
        }

        .nav-right ul li a:hover {
            color: var(--primary-color);
        }
    </style>
</head>

<body>

    <header>
        <nav class="navbar">
            <div class="nav-left">
                <a href="#" class="logo">soReal</a>
            </div>
            <div class="nav-right">
                <ul>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#" id="loginBtn">Login</a></li>
                    <li><a href="#" id="registerBtn">Register</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main style="margin-top: 30px;">
        <section style="margin-top: 20px;" class="hero">
            <div class="hero-content">
                <h1>Welcome to Our Restaurant</h1>
                <p>Experience exquisite flavors and exceptional dining.</p>
                <button class="order-now" id="orderNowBtn">Order Now</button>
                <div class="social-links">
                    <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </section>

        <section id="carousel" class="carousel">
            <div class="carousel-images">
                <img loading="lazy" src="/../soReal/assets/images/food.jpg" alt="Dish 1" class="active">
                <img src="/../soReal/assets/images/food2.jpg" alt="Dish 2">
                <img src="/../soReal/assets/images/food3.jpg" alt="Dish 3">
            </div>
        </section>

        <section id="about" class="about-section">
            <h2>About Us</h2>
            <div class="about-content">
                <img src="https://avatar.iran.liara.run/public" alt="Owner Image" class="owner-image">
                <div class="about-text">
                    <h3>Meet Our Owner</h3>
                    <p>Mrs. Patricia Siliya has a passion for culinary arts and a vision to create a dining experience
                        that brings people together. With years of experience in the industry, her dedication is
                        reflected in every dish served. Her commitment to using fresh, high-quality ingredients ensures
                        that every meal is not only delicious but also memorable.</p>
                </div>
            </div>
        </section>

        <section id="testimonials" class="testimonials-section">
            <h2>What Our Customers Say</h2>
            <div class="testimonials-container">
                <div class="testimonial-card">
                    <img src="https://avatar.iran.liara.run/public/boy" alt="Customer 1">
                    <h4>John Doe</h4>
                    <p>"Delicious meals in a cozy atmosphere. Highly recommend!"</p>
                </div>
                <div class="testimonial-card">
                    <img src="https://avatar.iran.liara.run/public/girl" alt="Customer 2">
                    <h4>Jane Smith</h4>
                    <p>"An unforgettable dining experience! Will come back for sure."</p>
                </div>
                <div class="testimonial-card">
                    <img src="https://avatar.iran.liara.run/public/boy" alt="Customer 3">
                    <h4>Michael Johnson</h4>
                    <p>"Fantastic service and amazing food. A must-visit!"</p>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // JavaScript to handle button clicks
        document.getElementById('orderNowBtn').addEventListener('click', function() {
            window.location.href = "client/menu.php"; // Update the link as needed
        });

        document.getElementById('loginBtn').addEventListener('click', function() {
            window.location.href = "login.php"; // Update the link as needed
        });

        document.getElementById('registerBtn').addEventListener('click', function() {
            window.location.href = "register.php"; // Update the link as needed
        });

        // Carousel functionality
        const images = document.querySelectorAll('.carousel-images img');
        let currentIndex = 0;

        function showImage(index) {
            images.forEach((img, i) => {
                img.classList.remove('active');
                if (i === index) {
                    img.classList.add('active');
                }
            });
        }

        function nextImage() {
            currentIndex = (currentIndex + 1) % images.length;
            showImage(currentIndex);
        }

        setInterval(nextImage, 3000); // Change image every 3 seconds
    </script>
</body>

</html>