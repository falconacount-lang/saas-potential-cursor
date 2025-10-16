-- =====================================================
-- ADIL CREATOR - COMPLETE DATABASE SCHEMA
-- MySQL Database - Production Ready for Hostinger
-- Includes: Core + Rocket Site Features + AI Integration
-- Version: 1.0.0
-- Date: 2025-10-16
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `avatar` varchar(500) DEFAULT NULL,
    `role` enum('user','admin','editor','viewer') NOT NULL DEFAULT 'user',
    `status` enum('active','inactive','banned','pending') NOT NULL DEFAULT 'active',
    `email_verified` tinyint(1) NOT NULL DEFAULT 0,
    `verification_token` varchar(255) DEFAULT NULL,
    `reset_token` varchar(255) DEFAULT NULL,
    `reset_expires` datetime DEFAULT NULL,
    `login_attempts` int(11) DEFAULT 0,
    `locked_until` datetime DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` text,
    `type` enum('text','number','boolean','json','email','url','textarea') NOT NULL DEFAULT 'text',
    `category` varchar(100) NOT NULL DEFAULT 'general',
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`),
    KEY `idx_settings_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `color` varchar(7) DEFAULT NULL,
    `icon` varchar(100) DEFAULT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_categories_parent` (`parent_id`),
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags Table
CREATE TABLE IF NOT EXISTS `tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `color` varchar(7) DEFAULT NULL,
    `usage_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONTENT MANAGEMENT TABLES
-- =====================================================

-- Blogs Table
CREATE TABLE IF NOT EXISTS `blogs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `excerpt` text,
    `content` longtext NOT NULL,
    `featured_image` varchar(500) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `author_id` int(11) NOT NULL,
    `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `published` tinyint(1) NOT NULL DEFAULT 0,
    `views` int(11) DEFAULT 0,
    `likes` int(11) DEFAULT 0,
    `read_time` int(11) DEFAULT 5,
    `published_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_blogs_status` (`status`),
    KEY `idx_blogs_published` (`published`),
    KEY `idx_blogs_category` (`category_id`),
    KEY `idx_blogs_author` (`author_id`),
    KEY `idx_blogs_published_at` (`published_at`),
    CONSTRAINT `fk_blogs_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_blogs_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Portfolio Table
CREATE TABLE IF NOT EXISTS `portfolio` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `long_description` longtext,
    `featured_image` varchar(500) NOT NULL,
    `gallery_images` text,
    `category_id` int(11) DEFAULT NULL,
    `client_name` varchar(255) DEFAULT NULL,
    `project_url` varchar(500) DEFAULT NULL,
    `completion_date` date DEFAULT NULL,
    `technologies` text,
    `results` text,
    `status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `views` int(11) DEFAULT 0,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_portfolio_status` (`status`),
    KEY `idx_portfolio_featured` (`featured`),
    KEY `idx_portfolio_category` (`category_id`),
    CONSTRAINT `fk_portfolio_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services Table
CREATE TABLE IF NOT EXISTS `services` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `tagline` varchar(500) DEFAULT NULL,
    `description` text NOT NULL,
    `long_description` longtext,
    `icon` varchar(100) DEFAULT NULL,
    `featured_image` varchar(500) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `features` text,
    `pricing_tiers` text,
    `delivery_time` varchar(100) DEFAULT NULL,
    `popular` tinyint(1) NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_services_active` (`active`),
    KEY `idx_services_popular` (`popular`),
    CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonials Table
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `position` varchar(255) DEFAULT NULL,
    `avatar` varchar(500) DEFAULT NULL,
    `content` text NOT NULL,
    `rating` int(11) NOT NULL DEFAULT 5,
    `service_id` int(11) DEFAULT NULL,
    `project_id` int(11) DEFAULT NULL,
    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_testimonials_status` (`status`),
    KEY `idx_testimonials_featured` (`featured`),
    CONSTRAINT `fk_testimonials_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_testimonials_project` FOREIGN KEY (`project_id`) REFERENCES `portfolio` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_testimonials_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_testimonials_rating` CHECK (`rating` >= 1 AND `rating` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FAQs Table
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `question` text NOT NULL,
    `answer` longtext NOT NULL,
    `category` varchar(100) NOT NULL,
    `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
    `order` int(11) DEFAULT 0,
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `views` int(11) DEFAULT 0,
    `helpful_votes` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_faqs_category` (`category`),
    KEY `idx_faqs_status` (`status`),
    KEY `idx_faqs_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COMMUNICATION TABLES
-- =====================================================

-- Contacts Table
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `subject` varchar(255) DEFAULT NULL,
    `message` text NOT NULL,
    `service_interest` varchar(255) DEFAULT NULL,
    `budget_range` varchar(100) DEFAULT NULL,
    `timeline` varchar(100) DEFAULT NULL,
    `source` varchar(100) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `status` enum('new','read','replied','closed','spam') NOT NULL DEFAULT 'new',
    `responded_at` datetime DEFAULT NULL,
    `responded_by` int(11) DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contacts_status` (`status`),
    KEY `idx_contacts_email` (`email`),
    KEY `idx_contacts_created` (`created_at`),
    CONSTRAINT `fk_contacts_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `status` enum('active','unsubscribed','bounced','complained') NOT NULL DEFAULT 'active',
    `source` varchar(100) DEFAULT NULL,
    `interests` text,
    `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_newsletter_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `entity` varchar(100) NOT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `description` text NOT NULL,
    `changes` text,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user` (`user_id`),
    KEY `idx_activity_logs_entity` (`entity`, `entity_id`),
    KEY `idx_activity_logs_created` (`created_at`),
    CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `action_url` varchar(500) DEFAULT NULL,
    `metadata` text,
    `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `read_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_type` (`type`),
    KEY `idx_notifications_is_read` (`is_read`),
    KEY `idx_notifications_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media Table
CREATE TABLE IF NOT EXISTS `media` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `original_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` int(11) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `file_type` varchar(50) NOT NULL,
    `dimensions` varchar(20) DEFAULT NULL,
    `alt_text` varchar(255) DEFAULT NULL,
    `caption` text,
    `uploaded_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_media_type` (`file_type`),
    KEY `idx_media_uploaded_by` (`uploaded_by`),
    KEY `idx_media_created` (`created_at`),
    CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ROCKET SITE - API MANAGEMENT TABLES
-- =====================================================

-- API Keys Management Table
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `service_name` varchar(100) NOT NULL,
    `display_name` varchar(255) NOT NULL,
    `api_key` text NOT NULL,
    `api_secret` text,
    `webhook_url` varchar(500) DEFAULT NULL,
    `additional_config` text,
    `status` enum('active','inactive','testing','error') NOT NULL DEFAULT 'testing',
    `last_tested` datetime DEFAULT NULL,
    `test_result` text,
    `test_response_time_ms` int(11) DEFAULT 0,
    `usage_count` int(11) DEFAULT 0,
    `error_count` int(11) DEFAULT 0,
    `last_error` text,
    `last_error_at` datetime DEFAULT NULL,
    `monthly_cost` decimal(10,2) DEFAULT 0.00,
    `rate_limit_per_hour` int(11) DEFAULT 100,
    `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `service_name` (`service_name`),
    KEY `idx_api_keys_status` (`status`),
    KEY `idx_api_keys_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Usage Logs Table
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_key_id` int(11) DEFAULT NULL,
    `service_name` varchar(100) NOT NULL,
    `endpoint` varchar(255) DEFAULT NULL,
    `operation` varchar(100) DEFAULT NULL,
    `request_method` varchar(10) DEFAULT NULL,
    `request_data` text,
    `response_data` text,
    `status_code` int(11) DEFAULT NULL,
    `response_time_ms` int(11) DEFAULT 0,
    `success` tinyint(1) NOT NULL DEFAULT 1,
    `error_message` text,
    `tokens_used` int(11) DEFAULT 0,
    `cost` decimal(10,4) DEFAULT 0.0000,
    `user_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_api_usage_service` (`service_name`),
    KEY `idx_api_usage_created` (`created_at`),
    KEY `idx_api_usage_success` (`success`),
    CONSTRAINT `fk_api_usage_logs_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_api_usage_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table (Extended)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(255) NOT NULL,
    `setting_value` text,
    `setting_type` enum('string','number','boolean','json','email','url','textarea') NOT NULL DEFAULT 'string',
    `category` varchar(100) NOT NULL DEFAULT 'general',
    `description` text,
    `is_public` tinyint(1) NOT NULL DEFAULT 0,
    `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
    `validation_rules` text,
    `default_value` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`),
    KEY `idx_system_settings_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature Toggles Table
CREATE TABLE IF NOT EXISTS `feature_toggles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `feature_key` varchar(100) NOT NULL,
    `feature_name` varchar(255) NOT NULL,
    `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `config` text,
    `description` text,
    `dependencies` text,
    `requires_api_keys` text,
    `min_user_role` varchar(20) DEFAULT 'user',
    `beta_feature` tinyint(1) NOT NULL DEFAULT 0,
    `usage_count` int(11) DEFAULT 0,
    `last_used_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `feature_key` (`feature_key`),
    KEY `idx_feature_toggles_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Cost Tracking Table
CREATE TABLE IF NOT EXISTS `api_cost_tracking` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `service_name` varchar(100) NOT NULL,
    `year` int(11) NOT NULL,
    `month` int(11) NOT NULL,
    `budget_limit` decimal(10,2) DEFAULT 100.00,
    `current_spend` decimal(10,2) DEFAULT 0.00,
    `total_requests` int(11) DEFAULT 0,
    `successful_requests` int(11) DEFAULT 0,
    `failed_requests` int(11) DEFAULT 0,
    `average_cost_per_request` decimal(10,4) DEFAULT 0.0000,
    `estimated_month_end_spend` decimal(10,2) DEFAULT 0.00,
    `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `service_year_month` (`service_name`, `year`, `month`),
    KEY `idx_api_cost_service` (`service_name`),
    KEY `idx_api_cost_period` (`year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ROCKET SITE - AI INTEGRATION TABLES
-- =====================================================

-- AI Usage Log Table
CREATE TABLE IF NOT EXISTS `ai_usage_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `operation` varchar(100) NOT NULL,
    `cost` decimal(10,4) NOT NULL DEFAULT 0.0000,
    `input_tokens` int(11) NOT NULL DEFAULT 0,
    `output_tokens` int(11) NOT NULL DEFAULT 0,
    `total_tokens` int(11) NOT NULL DEFAULT 0,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ai_usage_operation` (`operation`),
    KEY `idx_ai_usage_created` (`created_at`),
    KEY `idx_ai_usage_user` (`user_id`),
    CONSTRAINT `fk_ai_usage_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Response Cache Table
CREATE TABLE IF NOT EXISTS `ai_response_cache` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cache_key` varchar(255) NOT NULL,
    `response_data` longtext NOT NULL,
    `operation` varchar(100) NOT NULL,
    `hit_count` int(11) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` datetime NOT NULL,
    `last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cache_key` (`cache_key`),
    KEY `idx_ai_cache_expires` (`expires_at`),
    KEY `idx_ai_cache_operation` (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Chat Sessions Table
CREATE TABLE IF NOT EXISTS `ai_chat_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(255) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `visitor_id` varchar(255) DEFAULT NULL,
    `user_name` varchar(255) DEFAULT NULL,
    `user_email` varchar(255) DEFAULT NULL,
    `status` enum('active','ended','transferred') DEFAULT 'active',
    `total_messages` int(11) NOT NULL DEFAULT 0,
    `ai_responses` int(11) NOT NULL DEFAULT 0,
    `human_responses` int(11) NOT NULL DEFAULT 0,
    `satisfaction_score` int(11) DEFAULT NULL,
    `lead_quality_score` decimal(3,2) DEFAULT NULL,
    `converted_to_lead` tinyint(1) NOT NULL DEFAULT 0,
    `total_cost` decimal(10,4) NOT NULL DEFAULT 0.0000,
    `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at` datetime DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `session_id` (`session_id`),
    KEY `idx_ai_chat_user` (`user_id`),
    KEY `idx_ai_chat_status` (`status`),
    CONSTRAINT `fk_ai_chat_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Chat Messages Table
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(255) NOT NULL,
    `message_type` enum('user','ai','human','system') NOT NULL,
    `message_content` longtext NOT NULL,
    `ai_confidence` decimal(3,2) DEFAULT NULL,
    `tokens_used` int(11) DEFAULT NULL,
    `cost` decimal(10,4) DEFAULT NULL,
    `response_time_ms` int(11) DEFAULT NULL,
    `context_data` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ai_messages_session` (`session_id`),
    KEY `idx_ai_messages_type` (`message_type`),
    KEY `idx_ai_messages_created` (`created_at`),
    CONSTRAINT `fk_ai_chat_messages_session` FOREIGN KEY (`session_id`) REFERENCES `ai_chat_sessions` (`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ROCKET SITE - SOCIAL MEDIA TABLES
-- =====================================================

-- Social Media Post Queue Table
CREATE TABLE IF NOT EXISTS `social_post_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `platform` enum('linkedin','twitter','facebook','instagram') NOT NULL,
    `content` text NOT NULL,
    `media_url` varchar(500) DEFAULT NULL,
    `blog_id` int(11) DEFAULT NULL,
    `status` enum('pending','posted','failed','cancelled') NOT NULL DEFAULT 'pending',
    `scheduled_at` datetime NOT NULL,
    `posted_at` datetime DEFAULT NULL,
    `external_post_id` varchar(255) DEFAULT NULL,
    `error_message` text,
    `attempts` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_social_queue_status` (`status`),
    KEY `idx_social_queue_scheduled` (`scheduled_at`),
    KEY `idx_social_queue_platform` (`platform`),
    CONSTRAINT `fk_social_queue_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social Media Posts Log Table
CREATE TABLE IF NOT EXISTS `social_media_posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `platform` varchar(50) NOT NULL,
    `content` text NOT NULL,
    `external_post_id` varchar(255) DEFAULT NULL,
    `success` tinyint(1) NOT NULL DEFAULT 1,
    `error_message` text,
    `engagement_count` int(11) DEFAULT 0,
    `reach` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_social_posts_platform` (`platform`),
    KEY `idx_social_posts_created` (`created_at`),
    KEY `idx_social_posts_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ROCKET SITE - LEAD PROSPECTING TABLES
-- =====================================================

-- Leads Table
CREATE TABLE IF NOT EXISTS `leads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `role` varchar(100) DEFAULT NULL,
    `industry` varchar(100) DEFAULT NULL,
    `company_size` varchar(50) DEFAULT NULL,
    `website` varchar(500) DEFAULT NULL,
    `status` enum('new','contacted','qualified','proposal','won','lost') NOT NULL DEFAULT 'new',
    `source` varchar(100) DEFAULT NULL,
    `lead_score` decimal(3,2) DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_leads_status` (`status`),
    KEY `idx_leads_score` (`lead_score`),
    KEY `idx_leads_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lead Outreach Templates Table
CREATE TABLE IF NOT EXISTS `lead_outreach_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lead_id` int(11) NOT NULL,
    `approach` varchar(100) NOT NULL,
    `template_data` text NOT NULL,
    `used` tinyint(1) NOT NULL DEFAULT 0,
    `response_received` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_outreach_lead` (`lead_id`),
    KEY `idx_outreach_used` (`used`),
    CONSTRAINT `fk_outreach_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- HOMEPAGE CONTENT TABLE
-- =====================================================

-- Homepage Content Table
CREATE TABLE IF NOT EXISTS `homepage_content` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section` varchar(100) NOT NULL,
    `content_key` varchar(100) NOT NULL,
    `content_value` text,
    `content_type` varchar(50) NOT NULL DEFAULT 'text',
    `display_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_content_key` (`section`, `content_key`),
    KEY `idx_homepage_section` (`section`),
    KEY `idx_homepage_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA INSERTION
-- =====================================================

-- Insert default admin user
INSERT IGNORE INTO `users` (`email`, `password`, `name`, `role`, `status`, `email_verified`) VALUES
('admin@adilcreator.com', '$2y$12$PXq0z7MbJKf/AQMEe6Fjsu6tZfjErrbYrGvtWyDnMa2my.xw46Xg2', 'Adil Creator Admin', 'admin', 'active', 1);

-- Insert default settings
INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `category`, `description`) VALUES
('site_name', 'Adil Creator', 'text', 'general', 'Website name'),
('site_tagline', 'Professional Design & Creative Services', 'text', 'general', 'Website tagline'),
('contact_email', 'admin@adilcreator.com', 'email', 'general', 'Primary contact email'),
('site_url', 'https://adilcreator.com', 'url', 'general', 'Site URL');

-- Insert default categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`, `color`, `icon`) VALUES
('Logo Design', 'logo-design', 'Professional logo design services', '#3B82F6', 'palette'),
('YouTube Thumbnails', 'youtube-thumbnails', 'Eye-catching YouTube thumbnail designs', '#FF0000', 'play-circle'),
('Video Editing', 'video-editing', 'Professional video editing and post-production', '#8B5CF6', 'film');

-- Insert feature toggles
INSERT IGNORE INTO `feature_toggles` (`feature_key`, `feature_name`, `is_enabled`, `description`, `requires_api_keys`) VALUES
('ai_content_generation', 'AI Content Generation', 1, 'Generate blog posts and content using AI', '["openai"]'),
('ai_chat_support', 'AI Chat Support', 1, '24/7 AI-powered customer support', '["openai"]'),
('social_media_automation', 'Social Media Automation', 0, 'Automated social media posting', '["linkedin","twitter","facebook"]'),
('lead_prospecting', 'Lead Prospecting', 0, 'Automated lead discovery and outreach', '["hunter","clearbit"]');

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- API Keys Overview View
CREATE OR REPLACE VIEW `vw_api_keys_overview` AS
SELECT 
    ak.id,
    ak.service_name,
    ak.display_name,
    ak.status,
    ak.is_enabled,
    ak.usage_count,
    ak.error_count,
    ak.last_tested,
    ak.test_result,
    ak.test_response_time_ms,
    ROUND(CAST(ak.error_count AS DECIMAL(10,2)) * 100.0 / NULLIF(ak.usage_count, 0), 2) as error_rate_percent,
    act.current_spend as month_spend,
    act.budget_limit as month_budget,
    ROUND(CAST(act.current_spend AS DECIMAL(10,2)) * 100.0 / NULLIF(act.budget_limit, 0), 2) as budget_used_percent,
    ak.created_at,
    ak.updated_at
FROM api_keys ak
LEFT JOIN api_cost_tracking act ON 
    ak.service_name = act.service_name 
    AND act.year = YEAR(NOW())
    AND act.month = MONTH(NOW());

-- Recent API Usage View
CREATE OR REPLACE VIEW `vw_recent_api_usage` AS
SELECT 
    aul.id,
    aul.service_name,
    aul.operation,
    aul.status_code,
    aul.success,
    aul.response_time_ms,
    aul.tokens_used,
    aul.cost,
    aul.error_message,
    aul.created_at,
    ak.display_name as service_display_name,
    u.name as user_name
FROM api_usage_logs aul
LEFT JOIN api_keys ak ON aul.service_name = ak.service_name
LEFT JOIN users u ON aul.user_id = u.id
ORDER BY aul.created_at DESC
LIMIT 100;

-- =====================================================
-- END OF SCHEMA
-- =====================================================

COMMIT;
