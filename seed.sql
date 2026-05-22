USE recurlog;

-- Admin user (password: demo123)
INSERT INTO users (email, password, name) VALUES
('admin@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Staff
INSERT INTO staff (id, name, phone, avatar, active_tasks) VALUES
(1, 'Ramesh Yadav', '+977-9812345001', 'https://ui-avatars.com/api/?name=Ramesh+Yadav&background=1DB954&color=fff&size=200', 0),
(2, 'Suresh Thakur', '+977-9812345002', 'https://ui-avatars.com/api/?name=Suresh+Thakur&background=0EA5E9&color=fff&size=200', 0),
(3, 'Bikash Sah', '+977-9812345003', 'https://ui-avatars.com/api/?name=Bikash+Sah&background=F59E0B&color=fff&size=200', 0),
(4, 'Anita Devi', '+977-9812345004', 'https://ui-avatars.com/api/?name=Anita+Devi&background=8B5CF6&color=fff&size=200', 0),
(5, 'Manoj Kumar', '+977-9812345005', 'https://ui-avatars.com/api/?name=Manoj+Kumar&background=6366F1&color=fff&size=200', 0);

-- Categories
INSERT INTO categories (id, name, color) VALUES
(1, 'Annual Maintenance', '#1DB954'),
(2, 'Filter Change', '#0EA5E9'),
(3, 'Repair', '#F59E0B'),
(4, 'Deep Cleaning', '#8B5CF6'),
(5, 'Installation', '#EC4899'),
(6, 'Inspection', '#6366F1');

-- Customers
INSERT INTO customers (id, name, address, phone, services_for, location_lat, location_lng) VALUES
(1, 'Sharma Family', 'Adarsh Nagar, Birgunj', '+977-9801234001', '["RO","Refrigerator"]', 27.00, 84.87),
(2, 'Gupta Electronics', 'Ghantaghar, Birgunj', '+977-9801234002', '["AC","TV"]', 27.01, 84.87),
(3, 'Hotel Makalu', 'Station Road, Birgunj', '+977-9801234003', '["AC","RO","Refrigerator"]', 27.02, 84.87),
(4, 'Patel Residence', 'Ramanand Path, Birgunj', '+977-9801234004', '["RO","TV"]', 27.00, 84.87),
(5, 'Singh Niwas', 'Triveni, Birgunj', '+977-9801234005', '["AC","Refrigerator","Washing Machine"]', 27.01, 84.87),
(6, 'Modern Pharmacy', 'Gol Bazar, Birgunj', '+977-9801234006', '["AC","Refrigerator","RO"]', 27.00, 84.87),
(7, 'Khanal House', 'Rani Talab, Birgunj', '+977-9801234007', '["TV","RO"]', 27.02, 84.87),
(8, 'Birgunj Sweets', 'Chauraha, Birgunj', '+977-9801234008', '["Refrigerator","Washing Machine","AC"]', 27.01, 84.87);

-- Services
INSERT INTO services (id, customer_id, category_id, service_for, title, is_recurring, first_scheduled_date, assigned_to, notes, recurrence_value, recurrence_unit, recurrence_repeat_from) VALUES
(1, 1, 2, 'RO', 'RO Filter Check', 1, '2026-05-15', 1, 'Regular RO filter check-up', 30, 'days', 'last_service'),
(2, 1, 1, 'Refrigerator', 'Refrigerator Annual Maintenance', 1, '2026-04-10', 2, 'Yearly maintenance of refrigerator', 90, 'days', 'last_service'),
(3, 2, 5, 'AC', 'New AC Installation', 0, '2026-05-20', 3, 'Install new split AC unit', NULL, NULL, NULL),
(4, 2, 3, 'TV', 'TV Repair', 0, '2026-05-18', 1, 'Screen flickering issue', NULL, NULL, NULL),
(5, 3, 1, 'AC', 'AC Annual Maintenance', 1, '2026-03-01', 2, 'All AC units in hotel need maintenance', 45, 'days', 'last_service'),
(6, 3, 2, 'RO', 'RO Filter Change', 1, '2026-03-15', 1, 'Replace RO filters for kitchen', 30, 'days', 'last_service');

-- Tasks
INSERT INTO tasks (id, service_id, customer_id, title, status, scheduled_date, completed_date, assigned_to, notes, category_id) VALUES
(1, 1, 1, 'Filter Change - Sharma Family', 'pending', '2026-05-15', NULL, 1, '', 2),
(2, 2, 1, 'Annual Maintenance - Sharma Family', 'pending', '2026-06-10', NULL, 2, '', 1),
(3, 3, 2, 'Installation - Gupta Electronics', 'pending', '2026-05-20', NULL, 3, '', 5),
(4, 4, 2, 'Repair - Gupta Electronics', 'completed', '2026-05-18', '2026-05-18', 1, 'TV screen repaired successfully', 3),
(5, 5, 3, 'Annual Maintenance - Hotel Makalu', 'completed', '2026-03-01', '2026-03-01', 2, 'All 5 AC units serviced', 1),
(6, 6, 3, 'Filter Change - Hotel Makalu', 'completed', '2026-03-15', '2026-03-15', 1, 'RO filters replaced for kitchen and dining', 2),
(7, 5, 3, 'Annual Maintenance - Hotel Makalu', 'completed', '2026-04-15', '2026-04-16', 2, 'AC filter cleaning done', 1),
(8, 6, 3, 'Filter Change - Hotel Makalu', 'pending', '2026-04-14', NULL, 1, '', 2),
(9, 1, 1, 'Filter Change - Sharma Family', 'completed', '2026-04-15', '2026-04-15', 1, 'RO filter replaced', 2),
(10, 5, 3, 'Annual Maintenance - Hotel Makalu', 'pending', '2026-05-30', NULL, 2, '', 1);

-- Notifications
INSERT INTO notifications (id, text, type, related_id, is_read, created_at) VALUES
(1, 'Ramesh Yadav completed TV Repair for Gupta Electronics', 'task_completed', 4, 0, '2026-05-18 10:30:00'),
(2, 'Suresh Thakur completed Annual Maintenance for Hotel Makalu. Next service: Apr 15, 2026', 'task_completed', 5, 0, '2026-03-01 14:00:00'),
(3, 'Ramesh Yadav completed Filter Change for Hotel Makalu. Next service: Apr 14, 2026', 'task_completed', 6, 0, '2026-03-15 11:00:00'),
(4, 'New customer Gupta Electronics registered', 'customer_added', 2, 1, '2026-05-10 09:00:00'),
(5, 'Suresh Thakur completed Annual Maintenance for Hotel Makalu. Next service: May 30, 2026', 'task_completed', 7, 0, '2026-04-16 15:00:00'),
(6, 'Ramesh Yadav completed Filter Change for Sharma Family. Next service: May 15, 2026', 'task_completed', 9, 0, '2026-04-15 16:00:00');
