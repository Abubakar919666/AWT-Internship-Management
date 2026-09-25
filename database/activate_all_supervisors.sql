-- =======================================================
-- AWT Intern Management System (AWT-IMS)
-- Activate All Supervisors and Provision Login Accounts
-- Organization: Alamgir Welfare Trust Int'l (AWT)
-- =======================================================

-- 1. Ensure all supervisors exist in `supervisors` table and are active
INSERT INTO `supervisors` (`id`, `user_id`, `name`, `email`, `phone`, `department`, `designation`, `is_active`) VALUES
(1, 2, 'Muhammad Wali Saleem', 'supervisor@awt.org', '+92-300-1234567', 'Coordination & Media', 'Officer - Coordination', 1),
(2, 3, 'Abdul Latif', 'latif@awt.org', '+92-300-7654321', 'Operations & Social Work', 'Senior Supervisor', 1),
(3, 4, 'Muhammad Amir', 'amir@awt.org', '+92-300-9988776', 'Health & OPD Unit', 'Department Supervisor', 1),
(4, 5, 'Sohail Ahmed Khan', 'sohail@awt.org', '+92-300-5544332', 'Administration', 'Assistant Coordinator', 1),
(5, 6, 'Umer Qureshi', 'umer@awt.org', '+92-300-4433221', 'Marketing & Public Relations', 'Officer - Coordination', 1),
(6, 7, 'Niaz Khan', 'niaz@awt.org', '+92-300-3322110', 'Field Operations & Logistics', 'Field Supervisor', 1),
(7, 8, 'Nisar Ahmed', 'nisar@awt.org', '+92-300-1122334', 'HR & Internship Coordination', 'HR Coordinator', 1)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `email` = VALUES(`email`),
    `phone` = VALUES(`phone`),
    `department` = VALUES(`department`),
    `designation` = VALUES(`designation`),
    `is_active` = 1;

-- 2. Ensure each supervisor has an active user account (default password: supervisor123)
-- Hash $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm matches supervisor123
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role`, `supervisor_id`, `status`) VALUES
(2, 'Muhammad Wali Saleem', 'supervisor@awt.org', 'wali.saleem', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 1, 'active'),
(3, 'Abdul Latif', 'latif@awt.org', 'abdul.latif', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 2, 'active'),
(4, 'Muhammad Amir', 'amir@awt.org', 'muhammad.amir', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 3, 'active'),
(5, 'Sohail Ahmed Khan', 'sohail@awt.org', 'sohail.khan', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 4, 'active'),
(6, 'Umer Qureshi', 'umer@awt.org', 'umer.qureshi', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 5, 'active'),
(7, 'Niaz Khan', 'niaz@awt.org', 'niaz.khan', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 6, 'active'),
(8, 'Nisar Ahmed', 'nisar@awt.org', 'nisar.ahmed', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'supervisor', 7, 'active')
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `username` = VALUES(`username`),
    `role` = 'supervisor',
    `supervisor_id` = VALUES(`supervisor_id`),
    `status` = 'active';

-- 3. Link user_id in supervisors table
UPDATE `supervisors` SET `user_id` = 2 WHERE `id` = 1;
UPDATE `supervisors` SET `user_id` = 3 WHERE `id` = 2;
UPDATE `supervisors` SET `user_id` = 4 WHERE `id` = 3;
UPDATE `supervisors` SET `user_id` = 5 WHERE `id` = 4;
UPDATE `supervisors` SET `user_id` = 6 WHERE `id` = 5;
UPDATE `supervisors` SET `user_id` = 7 WHERE `id` = 6;
UPDATE `supervisors` SET `user_id` = 8 WHERE `id` = 7;

-- 4. Map legacy interns to supervisor_id by Mentor name
UPDATE `interns` SET `supervisor_id` = 1 WHERE `Mentor` LIKE '%Wali%' OR `Mentor` LIKE '%Saleem%';
UPDATE `interns` SET `supervisor_id` = 2 WHERE `Mentor` LIKE '%Latif%';
UPDATE `interns` SET `supervisor_id` = 3 WHERE `Mentor` LIKE '%Amir%';
UPDATE `interns` SET `supervisor_id` = 4 WHERE `Mentor` LIKE '%Sohail%';
UPDATE `interns` SET `supervisor_id` = 5 WHERE `Mentor` LIKE '%Umer%' OR `Mentor` LIKE '%Qureshi%';
UPDATE `interns` SET `supervisor_id` = 6 WHERE `Mentor` LIKE '%Niaz%';
UPDATE `interns` SET `supervisor_id` = 7 WHERE `Mentor` LIKE '%Nisar%';
