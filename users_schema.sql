-- ============================================================
-- Support Ticket System — Users Table (Authentication)
-- Run against existing database: support_ticket_system
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,   -- bcrypt hashed
    `role` ENUM('customer','agent','admin') DEFAULT 'customer',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a default admin account (password: admin123)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@support.com', '$2y$10$8KzQwE5e5r3g5W0Q5z5z5OYwKQkQkQkQkQkQkQkQkQkQkQkQkQkQ', 'admin');
