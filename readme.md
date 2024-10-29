# soReal

## Overview

soReal is a web application designed for a restaurant, allowing users to browse, order, and purchase various food items. The application features user registration, discount codes, native advertisements, payment integration, and an admin panel for managing the restaurant's menus, items, discount codes, orders, and user accounts. An analytics dashboard offers insights into restaurant performance.

## Features

- **User Registration**: Allows users to create accounts for personalized access.
- **Menu Management**: Admins can manage restaurant menus and respective items.
- **Order Placement**: Users can easily place orders for food items.
- **Discount Codes**: Promotional codes provide users with savings.
- **Advertisements**: Integrated ads promote special offers.
- **Payment Integration**: Secure payments processed via PayChangu.
- **Order Tracking**: Admins can track and manage pending orders efficiently.
- **User Management**: Admins manage user accounts and activity.
- **Analytics Dashboard**: Provides projected revenue, growth, and daily order statistics.

## Installation and Setup

### Requirements

- You will need MAMP or XAMPP to run this application locally.

### Steps

1. **Clone the repository**:

    ```bash
    git clone https://github.com/Mzati1/GROUP4-SYAD-ASSIGNMENT.git
    cd soreal
    ```

2. **Start the PHP Server**

   Launch the MAMP or XAMPP servers as required by your setup.

3. **Create the Database**

   Open phpMyAdmin in your web browser (typically at `http://localhost/phpmyadmin`). Create a new database using the database schema, and populate it with the sample data provided:

   - **Database Schema:** `assets/database/database.sql`
   - **Sample Data:** `assets/database/sample.sql`

4. **Configure the Database Connection**

   Update the database credentials in `includes/database.php` to match your setup.

5. **Access the Application**

   Go to `http://localhost:8888/soReal/pages` in your browser to access the landing page and begin exploring the application.

6. **Login Credentials**

   Use the following credentials for initial access:

   - **Admin Login**
      - **Email:** `admin@test.com`
      - **Password:** `password123`
      
   - **Customer Login**
      - **Email:** `customer@test.com`
      - **Password:** `password123`
      
   Alternatively, create a new user account by signing up with your own credentials.

## Live Website

You can access the live website at: [http://soreal.rf.gd](http://soreal.rf.gd)

## Third-Party Services Used

- **Font Awesome**: Provides icons via [Font Awesome](https://fontawesome.com/).
- **PayChangu Payment Gateway**: Manages payment processing through [PayChangu](https://paychangu.com/).
