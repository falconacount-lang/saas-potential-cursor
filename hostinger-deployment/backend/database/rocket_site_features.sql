-- =====================================================
-- ROCKET SITE - ADVANCED FEATURES SCHEMA
-- =====================================================
-- Add these tables to your existing database
-- Run this after the main schema is installed

USE `u720615217_adil_db`;

-- =====================================================
-- API MANAGEMENT TABLES
-- =====================================================

-- API Keys Management
CREATE TABLE `api_keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(150) NOT NULL,
    `api_key` TEXT NOT NULL,
    `api_secret` TEXT,
    `webhook_url` TEXT,
    `status` ENUM('active', 'inactive', 'testing', 'error') DEFAULT 'testing',
    `last_tested` TIMESTAMP NULL,
    `test_result` TEXT,
    `usage_count` INT DEFAULT 0,
    `error_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_service` (`service_name`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Usage Tracking
CREATE TABLE `api_usage_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `api_key_id` INT,
    `endpoint` VARCHAR(255),
    `request_data` TEXT,
    `response_data` TEXT,
    `status_code` INT,
    `response_time_ms` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE,
    INDEX `idx_api_key` (`api_key_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AI INTEGRATION TABLES
-- =====================================================

-- AI Usage Tracking
CREATE TABLE `ai_usage_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `service` VARCHAR(100) NOT NULL,
    `prompt_tokens` INT DEFAULT 0,
    `completion_tokens` INT DEFAULT 0,
    `total_tokens` INT DEFAULT 0,
    `cost` DECIMAL(10, 4) DEFAULT 0.0000,
    `model` VARCHAR(100),
    `request_data` TEXT,
    `response_data` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_service` (`service`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generated Content
CREATE TABLE `ai_generated_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `content_type` ENUM('blog', 'social_post', 'email', 'proposal', 'description') NOT NULL,
    `title` VARCHAR(500),
    `content` LONGTEXT NOT NULL,
    `meta_data` JSON,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `ai_model` VARCHAR(100),
    `tokens_used` INT,
    `cost` DECIMAL(10, 4),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`content_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SOCIAL MEDIA AUTOMATION TABLES
-- =====================================================

-- Social Media Queue
CREATE TABLE `social_post_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `blog_id` INT,
    `platform` ENUM('linkedin', 'twitter', 'facebook', 'instagram', 'youtube') NOT NULL,
    `content` TEXT NOT NULL,
    `media_url` VARCHAR(500),
    `status` ENUM('pending', 'scheduled', 'posted', 'failed') DEFAULT 'pending',
    `scheduled_for` TIMESTAMP NULL,
    `posted_at` TIMESTAMP NULL,
    `post_id` VARCHAR(255),
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
    INDEX `idx_platform` (`platform`),
    INDEX `idx_status` (`status`),
    INDEX `idx_scheduled` (`scheduled_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social Media Analytics
CREATE TABLE `social_media_analytics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` VARCHAR(255) NOT NULL,
    `platform` ENUM('linkedin', 'twitter', 'facebook', 'instagram', 'youtube') NOT NULL,
    `likes` INT DEFAULT 0,
    `shares` INT DEFAULT 0,
    `comments` INT DEFAULT 0,
    `views` INT DEFAULT 0,
    `clicks` INT DEFAULT 0,
    `engagement_rate` DECIMAL(5, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_post` (`post_id`, `platform`),
    INDEX `idx_platform` (`platform`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LEAD PROSPECTING TABLES
-- =====================================================

-- Leads Table
CREATE TABLE `leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255),
    `phone` VARCHAR(20),
    `company` VARCHAR(255),
    `industry` VARCHAR(100),
    `role` VARCHAR(100),
    `domain` VARCHAR(255),
    `social_profiles` JSON,
    `company_info` JSON,
    `source` VARCHAR(100),
    `status` ENUM('new', 'contacted', 'qualified', 'proposal_sent', 'closed_won', 'closed_lost') DEFAULT 'new',
    `score` INT DEFAULT 0,
    `notes` TEXT,
    `assigned_to` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_company` (`company`),
    INDEX `idx_status` (`status`),
    INDEX `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Outreach Messages
CREATE TABLE `outreach_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT NOT NULL,
    `message_type` ENUM('direct', 'social_proof', 'problem_solution', 'follow_up') NOT NULL,
    `subject` VARCHAR(500),
    `content` TEXT NOT NULL,
    `status` ENUM('draft', 'sent', 'opened', 'replied', 'bounced') DEFAULT 'draft',
    `sent_at` TIMESTAMP NULL,
    `opened_at` TIMESTAMP NULL,
    `replied_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_type` (`message_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADVANCED ANALYTICS TABLES
-- =====================================================

-- Revenue Tracking
CREATE TABLE `revenue_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `revenue` DECIMAL(10, 2) DEFAULT 0.00,
    `profit` DECIMAL(10, 2) DEFAULT 0.00,
    `new_clients` INT DEFAULT 0,
    `recurring_revenue` DECIMAL(10, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_date` (`date`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Metrics
CREATE TABLE `performance_metrics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `metric_name` VARCHAR(100) NOT NULL,
    `metric_value` DECIMAL(10, 4) NOT NULL,
    `metric_unit` VARCHAR(20),
    `category` VARCHAR(50),
    `date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_metric` (`metric_name`),
    INDEX `idx_category` (`category`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM CONFIGURATION TABLES
-- =====================================================

-- Feature Toggles
CREATE TABLE `feature_toggles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `feature_name` VARCHAR(100) NOT NULL UNIQUE,
    `is_enabled` BOOLEAN DEFAULT FALSE,
    `config` JSON,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings (Enhanced)
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CACHE AND OPTIMIZATION TABLES
-- =====================================================

-- Cache Management
CREATE TABLE `cache_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cache_key` VARCHAR(255) NOT NULL UNIQUE,
    `cache_value` LONGTEXT,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_key` (`cache_key`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default feature toggles
INSERT INTO `feature_toggles` (`feature_name`, `is_enabled`, `description`) VALUES
('ai_content_generation', TRUE, 'Enable AI-powered content generation'),
('social_media_automation', TRUE, 'Enable social media posting automation'),
('lead_prospecting', TRUE, 'Enable lead prospecting features'),
('advanced_analytics', TRUE, 'Enable advanced analytics dashboard'),
('email_marketing', TRUE, 'Enable email marketing automation'),
('mobile_pwa', TRUE, 'Enable Progressive Web App features'),
('api_management', TRUE, 'Enable API key management'),
('real_time_notifications', TRUE, 'Enable real-time notifications');

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Rocket Site', 'string', 'Website name'),
('site_description', 'Advanced Business Automation Platform', 'string', 'Website description'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('max_file_upload_size', '10485760', 'number', 'Maximum file upload size in bytes'),
('session_timeout', '3600', 'number', 'Session timeout in seconds'),
('api_rate_limit', '100', 'number', 'API rate limit per hour'),
('ai_monthly_budget', '50.00', 'number', 'Monthly AI budget in USD'),
('social_media_auto_post', 'true', 'boolean', 'Enable automatic social media posting');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Add composite indexes for better performance
CREATE INDEX `idx_ai_usage_user_date` ON `ai_usage_logs` (`user_id`, `created_at`);
CREATE INDEX `idx_social_queue_status_scheduled` ON `social_post_queue` (`status`, `scheduled_for`);
CREATE INDEX `idx_leads_status_score` ON `leads` (`status`, `score`);
CREATE INDEX `idx_outreach_lead_status` ON `outreach_messages` (`lead_id`, `status`);
CREATE INDEX `idx_revenue_date` ON `revenue_tracking` (`date`);
CREATE INDEX `idx_performance_metric_date` ON `performance_metrics` (`metric_name`, `date`);

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
SELECT 'Rocket Site advanced features schema installed successfully!' as message;