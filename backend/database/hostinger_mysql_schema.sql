-- =====================================================
-- ADIL CREATOR - COMPLETE DATABASE SCHEMA
-- MySQL/MariaDB Database - Production Ready
-- Includes: Core + Rocket Site Features + AI Integration
-- Version: 1.0.0
-- Date: 2025-10-16
-- =====================================================

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    avatar TEXT,
    role ENUM('user', 'admin', 'editor', 'viewer') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned', 'pending') NOT NULL DEFAULT 'active',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    type ENUM('text', 'number', 'boolean', 'json', 'email', 'url', 'textarea') NOT NULL DEFAULT 'text',
    category VARCHAR(100) NOT NULL DEFAULT 'general',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_settings_key ON settings(`key`);
CREATE INDEX idx_settings_category ON settings(category);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7),
    icon VARCHAR(100),
    parent_id INT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_categories_parent ON categories(parent_id);

-- Tags Table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    color VARCHAR(7),
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_tags_slug ON tags(slug);

-- =====================================================
-- CONTENT MANAGEMENT TABLES
-- =====================================================

-- Blogs Table
CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image TEXT,
    category_id INT,
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    published TINYINT(1) NOT NULL DEFAULT 0,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    read_time INT DEFAULT 5,
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_blogs_slug ON blogs(slug);
CREATE INDEX idx_blogs_status ON blogs(status);
CREATE INDEX idx_blogs_published ON blogs(published);
CREATE INDEX idx_blogs_category ON blogs(category_id);
CREATE INDEX idx_blogs_author ON blogs(author_id);
CREATE INDEX idx_blogs_published_at ON blogs(published_at);

-- Portfolio Table
CREATE TABLE IF NOT EXISTS portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    long_description LONGTEXT,
    featured_image TEXT NOT NULL,
    gallery_images TEXT,
    category_id INT,
    client_name VARCHAR(255),
    project_url VARCHAR(500),
    completion_date DATE,
    technologies TEXT,
    results TEXT,
    status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_portfolio_slug ON portfolio(slug);
CREATE INDEX idx_portfolio_status ON portfolio(status);
CREATE INDEX idx_portfolio_featured ON portfolio(featured);
CREATE INDEX idx_portfolio_category ON portfolio(category_id);

-- Services Table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    tagline VARCHAR(500),
    description TEXT NOT NULL,
    long_description LONGTEXT,
    icon VARCHAR(100),
    featured_image TEXT,
    category_id INT,
    features TEXT,
    pricing_tiers TEXT,
    delivery_time VARCHAR(100),
    popular TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_services_slug ON services(slug);
CREATE INDEX idx_services_active ON services(active);
CREATE INDEX idx_services_popular ON services(popular);

-- Testimonials Table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    company VARCHAR(255),
    position VARCHAR(255),
    avatar TEXT,
    content TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5 CHECK (rating >= 1 AND rating <= 5),
    service_id INT,
    project_id INT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES portfolio(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_testimonials_status ON testimonials(status);
CREATE INDEX idx_testimonials_featured ON testimonials(featured);

-- FAQs Table
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `order` INT DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT DEFAULT 0,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_faqs_category ON faqs(category);
CREATE INDEX idx_faqs_status ON faqs(status);
CREATE INDEX idx_faqs_featured ON faqs(featured);

-- =====================================================
-- COMMUNICATION TABLES
-- =====================================================

-- Contacts Table
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(500),
    message TEXT NOT NULL,
    service_interest VARCHAR(255),
    budget_range VARCHAR(100),
    timeline VARCHAR(100),
    source VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('new', 'read', 'replied', 'closed', 'spam') NOT NULL DEFAULT 'new',
    responded_at DATETIME,
    responded_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_contacts_status ON contacts(status);
CREATE INDEX idx_contacts_email ON contacts(email);
CREATE INDEX idx_contacts_created ON contacts(created_at);

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    status ENUM('active', 'unsubscribed', 'bounced', 'complained') NOT NULL DEFAULT 'active',
    source VARCHAR(100),
    interests TEXT,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_newsletter_email ON newsletter_subscribers(email);
CREATE INDEX idx_newsletter_status ON newsletter_subscribers(status);

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(100) NOT NULL,
    entity_id INT,
    description TEXT NOT NULL,
    changes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity, entity_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500),
    metadata TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created ON notifications(created_at);

-- Media Table
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    dimensions VARCHAR(20),
    alt_text VARCHAR(255),
    caption TEXT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_media_type ON media(file_type);
CREATE INDEX idx_media_uploaded_by ON media(uploaded_by);
CREATE INDEX idx_media_created ON media(created_at);

-- =====================================================
-- ROCKET SITE - API MANAGEMENT TABLES
-- =====================================================

-- API Keys Management Table
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    api_key TEXT NOT NULL,
    api_secret TEXT,
    webhook_url VARCHAR(500),
    additional_config TEXT,
    status ENUM('active', 'inactive', 'testing', 'error') NOT NULL DEFAULT 'testing',
    last_tested DATETIME,
    test_result TEXT,
    test_response_time_ms INT DEFAULT 0,
    usage_count INT DEFAULT 0,
    error_count INT DEFAULT 0,
    last_error TEXT,
    last_error_at DATETIME,
    monthly_cost DECIMAL(10,2) DEFAULT 0,
    rate_limit_per_hour INT DEFAULT 100,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_api_keys_service ON api_keys(service_name);
CREATE INDEX idx_api_keys_status ON api_keys(status);
CREATE INDEX idx_api_keys_enabled ON api_keys(is_enabled);

-- API Usage Logs Table
CREATE TABLE IF NOT EXISTS api_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT,
    service_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(500),
    operation VARCHAR(100),
    request_method VARCHAR(10),
    request_data TEXT,
    response_data TEXT,
    status_code INT,
    response_time_ms INT,
    success TINYINT(1) NOT NULL DEFAULT 1,
    error_message TEXT,
    tokens_used INT DEFAULT 0,
    cost DECIMAL(10,4) DEFAULT 0,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_api_usage_service ON api_usage_logs(service_name);
CREATE INDEX idx_api_usage_created ON api_usage_logs(created_at);
CREATE INDEX idx_api_usage_success ON api_usage_logs(success);

-- System Settings Table (Extended)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'email', 'url', 'textarea') NOT NULL DEFAULT 'string',
    category VARCHAR(100) NOT NULL DEFAULT 'general',
    description TEXT,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_encrypted TINYINT(1) NOT NULL DEFAULT 0,
    validation_rules TEXT,
    default_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_system_settings_key ON system_settings(setting_key);
CREATE INDEX idx_system_settings_category ON system_settings(category);

-- Feature Toggles Table
CREATE TABLE IF NOT EXISTS feature_toggles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(255) NOT NULL UNIQUE,
    feature_name VARCHAR(255) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    config TEXT,
    description TEXT,
    dependencies TEXT,
    requires_api_keys TEXT,
    min_user_role VARCHAR(50) DEFAULT 'user',
    beta_feature TINYINT(1) NOT NULL DEFAULT 0,
    usage_count INT DEFAULT 0,
    last_used_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_feature_toggles_key ON feature_toggles(feature_key);
CREATE INDEX idx_feature_toggles_enabled ON feature_toggles(is_enabled);

-- API Cost Tracking Table
CREATE TABLE IF NOT EXISTS api_cost_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    budget_limit DECIMAL(10,2) DEFAULT 100.00,
    current_spend DECIMAL(10,2) DEFAULT 0,
    total_requests INT DEFAULT 0,
    successful_requests INT DEFAULT 0,
    failed_requests INT DEFAULT 0,
    average_cost_per_request DECIMAL(10,4) DEFAULT 0,
    estimated_month_end_spend DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_service_period (service_name, year, month)
);

CREATE INDEX idx_api_cost_service ON api_cost_tracking(service_name);
CREATE INDEX idx_api_cost_period ON api_cost_tracking(year, month);

-- =====================================================
-- ROCKET SITE - AI INTEGRATION TABLES
-- =====================================================

-- AI Usage Log Table
CREATE TABLE IF NOT EXISTS ai_usage_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation VARCHAR(100) NOT NULL,
    cost DECIMAL(10,4) NOT NULL DEFAULT 0,
    input_tokens INT NOT NULL DEFAULT 0,
    output_tokens INT NOT NULL DEFAULT 0,
    total_tokens INT NOT NULL DEFAULT 0,
    user_id INT,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_ai_usage_operation ON ai_usage_log(operation);
CREATE INDEX idx_ai_usage_created ON ai_usage_log(created_at);
CREATE INDEX idx_ai_usage_user ON ai_usage_log(user_id);

-- AI Response Cache Table
CREATE TABLE IF NOT EXISTS ai_response_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    response_data LONGTEXT NOT NULL,
    operation VARCHAR(100) NOT NULL,
    hit_count INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_ai_cache_key ON ai_response_cache(cache_key);
CREATE INDEX idx_ai_cache_expires ON ai_response_cache(expires_at);
CREATE INDEX idx_ai_cache_operation ON ai_response_cache(operation);

-- AI Chat Sessions Table
CREATE TABLE IF NOT EXISTS ai_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INT,
    visitor_id VARCHAR(255),
    user_name VARCHAR(255),
    user_email VARCHAR(255),
    status ENUM('active', 'ended', 'transferred') DEFAULT 'active',
    total_messages INT NOT NULL DEFAULT 0,
    ai_responses INT NOT NULL DEFAULT 0,
    human_responses INT NOT NULL DEFAULT 0,
    satisfaction_score INT,
    lead_quality_score DECIMAL(3,2),
    converted_to_lead TINYINT(1) NOT NULL DEFAULT 0,
    total_cost DECIMAL(10,4) NOT NULL DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_ai_chat_session ON ai_chat_sessions(session_id);
CREATE INDEX idx_ai_chat_user ON ai_chat_sessions(user_id);
CREATE INDEX idx_ai_chat_status ON ai_chat_sessions(status);

-- AI Chat Messages Table
CREATE TABLE IF NOT EXISTS ai_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message_type ENUM('user', 'ai', 'human', 'system') NOT NULL,
    message_content TEXT NOT NULL,
    ai_confidence DECIMAL(3,2),
    tokens_used INT,
    cost DECIMAL(10,4),
    response_time_ms INT,
    context_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(session_id) ON DELETE CASCADE
);

CREATE INDEX idx_ai_messages_session ON ai_chat_messages(session_id);
CREATE INDEX idx_ai_messages_type ON ai_chat_messages(message_type);
CREATE INDEX idx_ai_messages_created ON ai_chat_messages(created_at);

-- =====================================================
-- ROCKET SITE - SOCIAL MEDIA TABLES
-- =====================================================

-- Social Media Post Queue Table
CREATE TABLE IF NOT EXISTS social_post_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('linkedin', 'twitter', 'facebook', 'instagram') NOT NULL,
    content TEXT NOT NULL,
    media_url VARCHAR(500),
    blog_id INT,
    status ENUM('pending', 'posted', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    scheduled_at DATETIME NOT NULL,
    posted_at DATETIME,
    external_post_id VARCHAR(255),
    error_message TEXT,
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogs(id) ON DELETE SET NULL
);

CREATE INDEX idx_social_queue_status ON social_post_queue(status);
CREATE INDEX idx_social_queue_scheduled ON social_post_queue(scheduled_at);
CREATE INDEX idx_social_queue_platform ON social_post_queue(platform);

-- Social Media Posts Log Table
CREATE TABLE IF NOT EXISTS social_media_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    external_post_id VARCHAR(255),
    success TINYINT(1) NOT NULL DEFAULT 1,
    error_message TEXT,
    engagement_count INT DEFAULT 0,
    reach INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_social_posts_platform ON social_media_posts(platform);
CREATE INDEX idx_social_posts_created ON social_media_posts(created_at);
CREATE INDEX idx_social_posts_success ON social_media_posts(success);

-- =====================================================
-- ROCKET SITE - LEAD PROSPECTING TABLES
-- =====================================================

-- Leads Table
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    company VARCHAR(255),
    role VARCHAR(255),
    industry VARCHAR(255),
    company_size VARCHAR(100),
    website VARCHAR(500),
    status ENUM('new', 'contacted', 'qualified', 'proposal', 'won', 'lost') NOT NULL DEFAULT 'new',
    source VARCHAR(100),
    lead_score DECIMAL(3,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_leads_email ON leads(email);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_score ON leads(lead_score);
CREATE INDEX idx_leads_created ON leads(created_at);

-- Lead Outreach Templates Table
CREATE TABLE IF NOT EXISTS lead_outreach_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    approach VARCHAR(100) NOT NULL,
    template_data TEXT NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    response_received TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
);

CREATE INDEX idx_outreach_lead ON lead_outreach_templates(lead_id);
CREATE INDEX idx_outreach_used ON lead_outreach_templates(used);

-- =====================================================
-- HOMEPAGE CONTENT TABLE
-- =====================================================

-- Homepage Content Table
CREATE TABLE IF NOT EXISTS homepage_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(100) NOT NULL,
    content_key VARCHAR(255) NOT NULL,
    content_value TEXT,
    content_type VARCHAR(50) NOT NULL DEFAULT 'text',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_key (section, content_key)
);

CREATE INDEX idx_homepage_section ON homepage_content(section);
CREATE INDEX idx_homepage_active ON homepage_content(is_active);

-- =====================================================
-- DEFAULT DATA INSERTION
-- =====================================================

-- Insert default admin user
INSERT IGNORE INTO users (email, password, name, role, status, email_verified) VALUES
('admin@adilgfx.com', '$2y$12$PXq0z7MbJKf/AQMEe6Fjsu6tZfjErrbYrGvtWyDnMa2my.xw46Xg2', 'Adil Creator Admin', 'admin', 'active', 1);

-- Insert default settings
INSERT IGNORE INTO settings (`key`, value, type, category, description) VALUES
('site_name', 'Adil Creator', 'text', 'general', 'Website name'),
('site_tagline', 'Professional Design & Creative Services', 'text', 'general', 'Website tagline'),
('contact_email', 'admin@adilgfx.com', 'email', 'general', 'Primary contact email'),
('site_url', 'https://adilgfx.com', 'url', 'general', 'Site URL');

-- Insert default categories
INSERT IGNORE INTO categories (name, slug, description, color, icon) VALUES
('Logo Design', 'logo-design', 'Professional logo design services', '#3B82F6', 'palette'),
('YouTube Thumbnails', 'youtube-thumbnails', 'Eye-catching YouTube thumbnail designs', '#FF0000', 'play-circle'),
('Video Editing', 'video-editing', 'Professional video editing and post-production', '#8B5CF6', 'film');

-- Insert feature toggles
INSERT IGNORE INTO feature_toggles (feature_key, feature_name, is_enabled, description, requires_api_keys) VALUES
('ai_content_generation', 'AI Content Generation', 1, 'Generate blog posts and content using AI', '["openai"]'),
('ai_chat_support', 'AI Chat Support', 1, '24/7 AI-powered customer support', '["openai"]'),
('social_media_automation', 'Social Media Automation', 0, 'Automated social media posting', '["linkedin","twitter","facebook"]'),
('lead_prospecting', 'Lead Prospecting', 0, 'Automated lead discovery and outreach', '["hunter","clearbit"]');

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- API Keys Overview View
CREATE OR REPLACE VIEW vw_api_keys_overview AS
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
    ROUND(CAST(ak.error_count AS DECIMAL(5,2)) * 100.0 / NULLIF(ak.usage_count, 0), 2) as error_rate_percent,
    act.current_spend as month_spend,
    act.budget_limit as month_budget,
    ROUND(CAST(act.current_spend AS DECIMAL(5,2)) * 100.0 / NULLIF(act.budget_limit, 0), 2) as budget_used_percent,
    ak.created_at,
    ak.updated_at
FROM api_keys ak
LEFT JOIN api_cost_tracking act ON 
    ak.service_name = act.service_name 
    AND act.year = YEAR(CURDATE())
    AND act.month = MONTH(CURDATE());

-- Recent API Usage View
CREATE OR REPLACE VIEW vw_recent_api_usage AS
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

-- Verify tables created
SELECT CONCAT('Schema created successfully. Total tables: ', COUNT(*)) as result 
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';