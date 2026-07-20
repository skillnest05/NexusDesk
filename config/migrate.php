<?php
/**
 * Database Auto-Migration
 * 
 * Automatically creates tables and seeds the admin account
 * on first run. Safe to run multiple times (uses IF NOT EXISTS
 * and checks before inserting).
 * 
 * Called from index.php on every request (fast — skips if tables exist).
 */

require_once __DIR__ . '/db.php';

function runMigrations(): void {
    try {
        $pdo = getDbConnection();

        // ---- Create Tables ----

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(30) DEFAULT 'customer',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `agents` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `expertise` VARCHAR(100) DEFAULT 'General',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `ticket_attachments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `ticket_id` INT NOT NULL,
                `filename` VARCHAR(255) NOT NULL,
                `url` VARCHAR(500) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_attachments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `ticket_replies` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `ticket_id` INT NOT NULL,
                `author_role` ENUM('customer','agent') NOT NULL,
                `author_name` VARCHAR(150) NOT NULL,
                `message` TEXT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---- Seed Admin Account ----
        // Read admin credentials from environment (Railway) or .env (local)
        $envFile = __DIR__ . '/../.env';
        $envVars = [];
        if (file_exists($envFile)) {
            $envVars = parse_ini_file($envFile);
        }

        $adminEmail    = getenv('ADMIN_EMAIL') ?: ($envVars['ADMIN_EMAIL'] ?? '');
        $adminPassword = getenv('ADMIN_PASSWORD') ?: ($envVars['ADMIN_PASSWORD'] ?? '');
        $adminName     = getenv('ADMIN_NAME') ?: ($envVars['ADMIN_NAME'] ?? 'Admin');

        // Only seed if credentials are provided
        if (!empty($adminEmail) && !empty($adminPassword)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$adminEmail]);

            if (!$stmt->fetch()) {
                // Admin does not exist yet — create it
                $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)')
                    ->execute([$adminName, $adminEmail, $hash, 'admin']);
                error_log('[NexusDesk] Admin account seeded: ' . $adminEmail);
            }
        }

    } catch (PDOException $e) {
        error_log('[NexusDesk] Migration error: ' . $e->getMessage());
        // Don't crash the app — just log and continue
    }
}

// Run migrations
runMigrations();
