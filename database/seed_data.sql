-- =======================================================
-- AWT Intern Management System (AWT-IMS)
-- Initial Seed Data
-- =======================================================

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('org_name', 'Alamgir Welfare Trust Int''l'),
('org_tagline', 'Serving Humanity with Honor and Integrity'),
('org_address', 'Alamgir Road, Bahadurabad, Karachi, Pakistan'),
('org_phone', '+92-21-111-153-153'),
('org_email', 'internship@alamgirwelfaretrust.com.pk'),
('org_website', 'www.alamgirwelfaretrust.com.pk'),
('hr_coordinator_name', 'Nisar Ahmed'),
('hr_coordinator_title', 'Coordinator'),
('active_year', '2026'),
('certificate_signatory', 'Nisar Ahmed'),
('certificate_signatory_title', 'HR & Internship Coordinator');

-- Default Admin & Supervisor Users
-- Default passwords:
-- admin@awt.org -> admin123 (sha256 fallback: 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9)
-- supervisor@awt.org -> supervisor123 (sha256 fallback: 5d1b790d7c3d2e1b12b3a164b3df3d537f8f0f089608447d6d338f0d80c3d9a9)
-- intern@awt.org -> intern123 (sha256 fallback: 4e9e51e9e7b2ff9c4b7261a868a2bf61b9ad9c60e4eb0d738f654df8c1719c28)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@awt.org', 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 'active'),
(2, 'Muhammad Wali Saleem', 'supervisor@awt.org', 'wali.saleem', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 'active'),
(3, 'Abdul Latif', 'latif@awt.org', 'abdul.latif', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 'active');

-- Supervisors Profile Records
INSERT INTO `supervisors` (`id`, `user_id`, `name`, `email`, `phone`, `department`, `designation`, `is_active`) VALUES
(1, 2, 'Muhammad Wali Saleem', 'supervisor@awt.org', '+92-300-1234567', 'Coordination & Media', 'Officer - Coordination', 1),
(2, 3, 'Abdul Latif', 'latif@awt.org', '+92-300-7654321', 'Operations & Social Work', 'Senior Supervisor', 1),
(3, NULL, 'Muhammad Amir', 'amir@awt.org', '+92-300-9988776', 'Health & OPD Unit', 'Department Supervisor', 1),
(4, NULL, 'Sohail Ahmed Khan', 'sohail@awt.org', '+92-300-5544332', 'Administration', 'Assistant Coordinator', 1);

-- Link supervisor_id back to users
UPDATE `users` SET `supervisor_id` = 1 WHERE `id` = 2;
UPDATE `users` SET `supervisor_id` = 2 WHERE `id` = 3;
