-- Insert predefined users
INSERT INTO Users (name, email, password, role, residence, created_at) VALUES 
('Customer1', 'customer@test.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Mzuzu', NOW()),
('Admin1', 'admin@test.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'admin', 'Lilongwe', NOW());

-- Insert additional users
INSERT INTO Users (name, email, password, role, residence, created_at) VALUES 
('User3', 'user3@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Blantyre', NOW()),
('User4', 'user4@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Lilongwe', NOW()),
('User5', 'user5@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Zomba', NOW()),
('User6', 'user6@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Mzuzu', NOW()),
('User7', 'user7@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Blantyre', NOW()),
('User8', 'user8@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Lilongwe', NOW()),
('User9', 'user9@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Zomba', NOW()),
('User10', 'user10@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Mzuzu', NOW()),
('User11', 'user11@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Blantyre', NOW()),
('User12', 'user12@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Lilongwe', NOW()),
('User13', 'user13@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Zomba', NOW()),
('User14', 'user14@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Mzuzu', NOW()),
('User15', 'user15@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Blantyre', NOW()),
('User16', 'user16@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Lilongwe', NOW()),
('User17', 'user17@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Zomba', NOW()),
('User18', 'user18@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Mzuzu', NOW()),
('User19', 'user19@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Blantyre', NOW()),
('User20', 'user20@example.com', '$2y$10$VgN2XJ3XG6pUt3C9wMjaEOe7uWiA3L9GR6/XaD65HjGZPj8wD/LOK', 'customer', 'Lilongwe', NOW());

-- Insert menu items (Malawian dishes)
INSERT INTO Menus (name, created_at) VALUES 
('Malawian Dishes', NOW());

INSERT INTO Menu_Items (menu_id, name, description, price, created_at) VALUES 
(1, 'Nsima', 'A staple food made from maize flour.', 200.00, NOW()),
(1, 'Chambo', 'Grilled fish from Lake Malawi.', 600.00, NOW()),
(1, 'Kachumbari', 'A fresh vegetable salad.', 150.00, NOW()),
(1, 'Sambusa', 'Deep-fried pastries filled with meat or vegetables.', 250.00, NOW()),
(1, 'Fried Chicken', 'Crispy fried chicken served with sides.', 700.00, NOW()),
(1, 'Mbatata', 'Sweet potato mash, a common side dish.', 180.00, NOW()),
(1, 'Bitter Leaf Soup', 'A traditional soup made from bitter leaves.', 300.00, NOW()),
(1, 'Rice and Beans', 'A hearty meal of rice and beans.', 350.00, NOW()),
(1, 'Grilled T bone Steak', 'Juicy steak grilled to perfection.', 900.00, NOW()),
(1, 'Zitumbuwa', 'Malawian pumpkin fritters.', 220.00, NOW()),
(1, 'Matemba', 'Dried fish, typically served with nsima.', 500.00, NOW()),
(1, 'Chicken Curry', 'Spicy chicken curry with rice.', 750.00, NOW()),
(1, 'Mchicha', 'A nutritious local vegetable stew.', 300.00, NOW()),
(1, 'Nkhuku Yosenda', 'Roasted chicken, a common festive dish.', 800.00, NOW()),
(1, 'Sikhomela', 'Sweet, spiced corn pudding.', 200.00, NOW());

-- Insert orders and payments for users
INSERT INTO Orders (user_id, status, created_at) VALUES 
(3, 'completed', NOW()),
(4, 'completed', NOW()),
(5, 'pending', NOW()),
(6, 'completed', NOW()),
(7, 'canceled', NOW()),
(8, 'completed', NOW()),
(9, 'pending', NOW()),
(10, 'completed', NOW()),
(11, 'completed', NOW()),
(12, 'completed', NOW()),
(13, 'pending', NOW()),
(14, 'completed', NOW()),
(15, 'completed', NOW()),
(16, 'completed', NOW()),
(17, 'completed', NOW()),
(18, 'completed', NOW()),
(19, 'pending', NOW()),
(20, 'completed', NOW());

INSERT INTO Payments (order_id, user_id, reference_id, payment_status, payment_method, amount, created_at) VALUES 
(1, 3, 'REF123456', 'successful', 'credit_card', 1000.00, NOW()),
(2, 4, 'REF123457', 'successful', 'paypal', 1200.00, NOW()),
(3, 5, 'REF123458', 'pending', 'mobile_money', 850.00, NOW()),
(4, 6, 'REF123459', 'successful', 'credit_card', 1100.00, NOW()),
(5, 7, 'REF123460', 'failed', 'paypal', 0.00, NOW()),
(6, 8, 'REF123461', 'successful', 'mobile_money', 1300.00, NOW()),
(7, 9, 'REF123462', 'pending', 'credit_card', 900.00, NOW()),
(8, 10, 'REF123463', 'successful', 'paypal', 950.00, NOW()),
(9, 11, 'REF123464', 'successful', 'credit_card', 2000.00, NOW()),
(10, 12, 'REF123465', 'successful', 'mobile_money', 1500.00, NOW()),
(11, 13, 'REF123466', 'pending', 'paypal', 1800.00, NOW()),
(12, 14, 'REF123467', 'successful', 'credit_card', 2500.00, NOW()),
(13, 15, 'REF123468', 'successful', 'mobile_money', 2200.00, NOW()),
(14, 16, 'REF123469', 'successful', 'credit_card', 1000.00, NOW()),
(15, 17, 'REF123470', 'successful', 'mobile_money', 1700.00, NOW()),
(16, 18, 'REF123471', 'successful', 'paypal', 3000.00, NOW()),
(17, 19, 'REF123472', 'pending', 'credit_card', 2500.00, NOW()),
(18, 20, 'REF123473', 'successful', 'paypal', 3100.00, NOW());
