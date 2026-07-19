-- ============================================================
-- Support Ticket System — Database Schema
-- Run this after creating the database:
--   CREATE DATABASE support_ticket_system;
--   USE support_ticket_system;
-- ============================================================

CREATE TABLE IF NOT EXISTS `agents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `priority` ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
    `status` ENUM('New','Open','In Progress','On Hold','Resolved','Closed') DEFAULT 'New',
    `sentiment` ENUM('Positive','Neutral','Negative','Frustrated') DEFAULT NULL,
    `ai_suggested_reply` TEXT DEFAULT NULL,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `agent_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_tickets_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attachments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `author_role` ENUM('customer','agent') NOT NULL,
    `author_name` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for common queries
CREATE INDEX `idx_tickets_status` ON `tickets`(`status`);
CREATE INDEX `idx_tickets_category` ON `tickets`(`category`);
CREATE INDEX `idx_tickets_priority` ON `tickets`(`priority`);
CREATE INDEX `idx_tickets_agent_id` ON `tickets`(`agent_id`);
CREATE INDEX `idx_tickets_customer_email` ON `tickets`(`customer_email`);
CREATE INDEX `idx_tickets_created_at` ON `tickets`(`created_at`);
CREATE INDEX `idx_replies_ticket_id` ON `ticket_replies`(`ticket_id`);
CREATE INDEX `idx_attachments_ticket_id` ON `ticket_attachments`(`ticket_id`);
