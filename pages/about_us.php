<?php
// Start session and include header
session_start();
include_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/../soReal/assets/css/about_us.css">
</head>

<body>
    <?php require __DIR__ . '/../includes/navigation.php'; ?>

    <!-- Restaurant Introduction -->
    <section id="about" class="about-section">
        <h2>About So Real</h2>
        <p class="intro-text">At So Real, we believe that dining is more than just a meal – it's an experience. Our focus is on creating unforgettable memories through exquisite flavors, cozy ambiance, and exceptional service.</p>
        <div class="about-content">
            <img src="https://avatar.iran.liara.run/public" alt="Owner Image" class="owner-image">
            <div class="about-text">
                <h3>Meet Our Owner</h3>
                <p>Mrs. Patricia Siliya, a culinary visionary, has crafted a restaurant that celebrates flavor, quality, and community. Her passion for high-quality ingredients ensures that every dish is memorable.</p>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section id="story" class="story-section">
        <h2>Our Story</h2>
        <p class="story-text">Founded in the heart of MUST, So Real started as a humble dream to bring people together over delightful food. From our first day, we've focused on quality, creativity, and dedication to our customers.</p>
    </section>


    <!-- Customer Testimonials -->
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

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>