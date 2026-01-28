-- Sample Data for Testing
USE order_assignment_db;

-- Insert sample couriers
INSERT INTO couriers (name, serviceable_locations, daily_capacity, is_active) VALUES
('John Doe', '["New York", "Boston"]', 50, 1),
('Jane Smith', '["New York", "Philadelphia"]', 75, 1),
('Bob Johnson', '["Boston", "Cambridge"]', 60, 1),
('Alice Williams', '["New York", "Boston", "Philadelphia"]', 100, 1),
('Charlie Brown', '["New York"]', 80, 1);

-- Insert sample orders
INSERT INTO orders (order_date, delivery_location, order_value, status) VALUES
(NOW(), 'New York', 150.00, 'UNASSIGNED'),
(NOW(), 'Boston', 200.00, 'UNASSIGNED'),
(NOW(), 'New York', 175.00, 'UNASSIGNED'),
(NOW(), 'Philadelphia', 120.00, 'UNASSIGNED'),
(NOW(), 'New York', 300.00, 'UNASSIGNED'),
(NOW(), 'Boston', 250.00, 'UNASSIGNED'),
(NOW(), 'Cambridge', 180.00, 'UNASSIGNED'),
(NOW(), 'New York', 220.00, 'UNASSIGNED'),
(NOW(), 'Boston', 190.00, 'UNASSIGNED'),
(NOW(), 'Philadelphia', 160.00, 'UNASSIGNED');
