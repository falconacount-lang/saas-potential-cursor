-- =====================================================
-- Social Media & Auto-Blog Tables
-- Add these to your existing database
-- =====================================================

-- Social Media Post Queue
CREATE TABLE IF NOT EXISTS `social_post_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform` ENUM('facebook', 'instagram', 'twitter', 'linkedin') NOT NULL,
    `content` TEXT NOT NULL,
    `image_url` VARCHAR(500),
    `link_url` VARCHAR(500),
    `scheduled_at` TIMESTAMP NOT NULL,
    `status` ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    `posted_at` TIMESTAMP NULL,
    `external_post_id` VARCHAR(255),
    `error_message` TEXT,
    `attempts` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_scheduled` (`scheduled_at`),
    INDEX `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Usage Log (if not exists)
CREATE TABLE IF NOT EXISTS `ai_usage_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `operation` VARCHAR(100) NOT NULL,
    `user_id` INT NULL,
    `cost` DECIMAL(10, 6) DEFAULT 0,
    `input_tokens` INT DEFAULT 0,
    `output_tokens` INT DEFAULT 0,
    `total_tokens` INT DEFAULT 0,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_operation` (`operation`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Response Cache
CREATE TABLE IF NOT EXISTS `ai_response_cache` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cache_key` VARCHAR(64) UNIQUE NOT NULL,
    `response_data` LONGTEXT NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `cache_hits` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cache_key` (`cache_key`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generated Content
CREATE TABLE IF NOT EXISTS `ai_generated_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `content_type` VARCHAR(50) NOT NULL,
    `content_id` INT NULL,
    `ai_operation` VARCHAR(100) NOT NULL,
    `original_prompt` TEXT,
    `generated_content` LONGTEXT NOT NULL,
    `tokens_used` INT DEFAULT 0,
    `cost` DECIMAL(10, 6) DEFAULT 0,
    `quality_score` DECIMAL(3, 2),
    `approved` BOOLEAN DEFAULT FALSE,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_content_type` (`content_type`),
    INDEX `idx_operation` (`ai_operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Chat Sessions
CREATE TABLE IF NOT EXISTS `ai_chat_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(100) UNIQUE NOT NULL,
    `user_id` INT NULL,
    `visitor_id` VARCHAR(100),
    `status` ENUM('active', 'closed') DEFAULT 'active',
    `total_messages` INT DEFAULT 0,
    `ai_responses` INT DEFAULT 0,
    `total_cost` DECIMAL(10, 6) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session` (`session_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Chat Messages
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(100) NOT NULL,
    `message_type` ENUM('user', 'ai') NOT NULL,
    `message_content` TEXT NOT NULL,
    `ai_confidence` DECIMAL(3, 2),
    `tokens_used` INT DEFAULT 0,
    `cost` DECIMAL(10, 6) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session` (`session_id`),
    INDEX `idx_type` (`message_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limits (File-based alternative available, but keeping DB option)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `endpoint` VARCHAR(255) NOT NULL,
    `requests` INT DEFAULT 1,
    `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_ip_endpoint` (`ip_address`, `endpoint`),
    INDEX `idx_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
