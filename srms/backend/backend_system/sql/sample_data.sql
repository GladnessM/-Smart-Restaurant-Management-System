USE restaurant_db;


INSERT INTO menu_items (name, description, price, category, image) VALUES
('Margherita Pizza', 'Classic tomato, mozzarella, basil', 8.50, 'Pizza', ''),
('Grilled Chicken', 'Served with vegetables and fries', 10.00, 'Mains', ''),
('Caesar Salad', 'Romaine lettuce, parmesan, croutons', 6.50, 'Salads', '');


-- sample admin user (password: changeit)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$replace_with_bcrypt_hash', 'admin');