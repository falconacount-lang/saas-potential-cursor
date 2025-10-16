-- =====================================================
-- ROCKET SITE PLAN - PHASE 1: API MANAGEMENT SYSTEM
-- SQLite Compatible Schema
-- =====================================================

-- API Keys Management Table
CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    api_key TEXT NOT NULL,
    api_secret TEXT,
    webhook_url TEXT,
    additional_config TEXT, -- JSON field for extra configuration
    status TEXT NOT NULL DEFAULT 'testing' CHECK(status IN ('active', 'inactive', 'testing', 'error')),
    last_tested TEXT,
    test_result TEXT,
    test_response_time_ms INTEGER DEFAULT 0,
    usage_count INTEGER DEFAULT 0,
    error_count INTEGER DEFAULT 0,
    last_error TEXT,
    last_error_at TEXT,
    monthly_cost REAL DEFAULT 0,
    rate_limit_per_hour INTEGER DEFAULT 100,
    is_enabled INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_api_keys_service ON api_keys(service_name);
CREATE INDEX IF NOT EXISTS idx_api_keys_status ON api_keys(status);
CREATE INDEX IF NOT EXISTS idx_api_keys_enabled ON api_keys(is_enabled);

-- API Usage Logs Table
CREATE TABLE IF NOT EXISTS api_usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key_id INTEGER,
    service_name TEXT NOT NULL,
    endpoint TEXT,
    operation TEXT,
    request_method TEXT,
    request_data TEXT, -- JSON
    response_data TEXT, -- JSON
    status_code INTEGER,
    response_time_ms INTEGER,
    success INTEGER NOT NULL DEFAULT 1,
    error_message TEXT,
    tokens_used INTEGER DEFAULT 0,
    cost REAL DEFAULT 0,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_api_usage_service ON api_usage_logs(service_name);
CREATE INDEX IF NOT EXISTS idx_api_usage_created ON api_usage_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_api_usage_success ON api_usage_logs(success);
CREATE INDEX IF NOT EXISTS idx_api_usage_api_key ON api_usage_logs(api_key_id);

-- System Settings Table (extended version)
CREATE TABLE IF NOT EXISTS system_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type TEXT NOT NULL DEFAULT 'string' CHECK(setting_type IN ('string', 'number', 'boolean', 'json', 'email', 'url', 'textarea')),
    category TEXT NOT NULL DEFAULT 'general',
    description TEXT,
    is_public INTEGER NOT NULL DEFAULT 0,
    is_encrypted INTEGER NOT NULL DEFAULT 0,
    validation_rules TEXT, -- JSON
    default_value TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_system_settings_category ON system_settings(category);
CREATE INDEX IF NOT EXISTS idx_system_settings_public ON system_settings(is_public);

-- Feature Toggles Table
CREATE TABLE IF NOT EXISTS feature_toggles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feature_key TEXT NOT NULL UNIQUE,
    feature_name TEXT NOT NULL,
    is_enabled INTEGER NOT NULL DEFAULT 0,
    config TEXT, -- JSON configuration
    description TEXT,
    dependencies TEXT, -- JSON array of required features
    requires_api_keys TEXT, -- JSON array of required API services
    min_user_role TEXT DEFAULT 'user',
    beta_feature INTEGER NOT NULL DEFAULT 0,
    usage_count INTEGER DEFAULT 0,
    last_used_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_feature_toggles_key ON feature_toggles(feature_key);
CREATE INDEX IF NOT EXISTS idx_feature_toggles_enabled ON feature_toggles(is_enabled);
CREATE INDEX IF NOT EXISTS idx_feature_toggles_beta ON feature_toggles(beta_feature);

-- API Rate Limiting Table
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    identifier TEXT NOT NULL, -- IP address or user ID
    identifier_type TEXT NOT NULL CHECK(identifier_type IN ('ip', 'user', 'api_key')),
    request_count INTEGER DEFAULT 1,
    window_start TEXT DEFAULT (datetime('now')),
    window_end TEXT,
    is_blocked INTEGER NOT NULL DEFAULT 0,
    block_reason TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_rate_limits_unique ON api_rate_limits(service_name, identifier, identifier_type, window_start);
CREATE INDEX IF NOT EXISTS idx_rate_limits_service ON api_rate_limits(service_name);
CREATE INDEX IF NOT EXISTS idx_rate_limits_blocked ON api_rate_limits(is_blocked);

-- API Webhooks Table
CREATE TABLE IF NOT EXISTS api_webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    webhook_url TEXT NOT NULL,
    webhook_secret TEXT,
    event_types TEXT, -- JSON array
    is_active INTEGER NOT NULL DEFAULT 1,
    last_triggered TEXT,
    success_count INTEGER DEFAULT 0,
    failure_count INTEGER DEFAULT 0,
    last_error TEXT,
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_api_webhooks_service ON api_webhooks(service_name);
CREATE INDEX IF NOT EXISTS idx_api_webhooks_active ON api_webhooks(is_active);

-- API Cost Tracking Table
CREATE TABLE IF NOT EXISTS api_cost_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    year INTEGER NOT NULL,
    month INTEGER NOT NULL,
    budget_limit REAL DEFAULT 100.00,
    current_spend REAL DEFAULT 0,
    total_requests INTEGER DEFAULT 0,
    successful_requests INTEGER DEFAULT 0,
    failed_requests INTEGER DEFAULT 0,
    average_cost_per_request REAL DEFAULT 0,
    estimated_month_end_spend REAL DEFAULT 0,
    last_updated TEXT DEFAULT (datetime('now')),
    UNIQUE(service_name, year, month)
);

CREATE INDEX IF NOT EXISTS idx_api_cost_service ON api_cost_tracking(service_name);
CREATE INDEX IF NOT EXISTS idx_api_cost_period ON api_cost_tracking(year, month);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- System Settings for API Management
INSERT OR IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description, is_encrypted) VALUES
('api_encryption_key', '', 'string', 'security', 'Encryption key for API credentials (auto-generated)', 1),
('api_global_rate_limit', '1000', 'number', 'api', 'Global API rate limit per hour', 0),
('api_enable_logging', 'true', 'boolean', 'api', 'Enable detailed API usage logging', 0),
('api_log_retention_days', '90', 'number', 'api', 'Days to retain API logs', 0),
('api_enable_caching', 'true', 'boolean', 'api', 'Enable API response caching', 0),
('api_cache_ttl_seconds', '3600', 'number', 'api', 'Default cache TTL in seconds', 0),
('api_alert_threshold_percent', '80', 'number', 'api', 'Alert when budget reaches this percentage', 0),
('api_auto_disable_on_error', 'false', 'boolean', 'api', 'Auto-disable API on repeated errors', 0);

-- Feature Toggles for Rocket Site Features
INSERT OR IGNORE INTO feature_toggles (feature_key, feature_name, is_enabled, description, requires_api_keys, beta_feature) VALUES
('ai_content_generation', 'AI Content Generation', 1, 'Generate blog posts, social media content, and marketing copy using AI', '["openai"]', 0),
('ai_chat_support', 'AI Chat Support', 1, '24/7 AI-powered customer support chat', '["openai"]', 0),
('ai_seo_optimization', 'AI SEO Optimization', 1, 'Automatic SEO optimization for content', '["openai"]', 0),
('social_media_automation', 'Social Media Automation', 0, 'Automated social media posting and scheduling', '["linkedin", "twitter", "facebook"]', 0),
('lead_prospecting', 'Lead Prospecting', 0, 'Automated lead discovery and outreach', '["hunter", "clearbit", "apollo"]', 1),
('email_automation', 'Email Automation', 0, 'Automated email campaigns and sequences', '["sendgrid", "mailchimp"]', 0),
('analytics_tracking', 'Advanced Analytics', 1, 'Comprehensive analytics and reporting', '["google_analytics"]', 0),
('payment_processing', 'Payment Processing', 0, 'Accept payments via Stripe/PayPal', '["stripe", "paypal"]', 0),
('wordpress_integration', 'WordPress Integration', 0, 'Sync content with WordPress sites', '["wordpress"]', 1),
('translation_system', 'Multi-language Support', 1, 'Automatic content translation', '[]', 0);

-- Default API Keys (placeholders - to be configured via admin panel)
INSERT OR IGNORE INTO api_keys (service_name, display_name, api_key, status, description, rate_limit_per_hour) VALUES
('openai', 'OpenAI (GPT-4)', 'CONFIGURE_IN_ADMIN', 'inactive', 'AI content generation and chat support', 60),
('linkedin', 'LinkedIn API', 'CONFIGURE_IN_ADMIN', 'inactive', 'LinkedIn profile and posting automation', 100),
('twitter', 'Twitter/X API', 'CONFIGURE_IN_ADMIN', 'inactive', 'Twitter posting and engagement', 180),
('facebook', 'Meta/Facebook API', 'CONFIGURE_IN_ADMIN', 'inactive', 'Facebook and Instagram posting', 200),
('sendgrid', 'SendGrid Email', 'CONFIGURE_IN_ADMIN', 'inactive', 'Email delivery service', 1000),
('stripe', 'Stripe Payments', 'CONFIGURE_IN_ADMIN', 'inactive', 'Payment processing', 100),
('google_analytics', 'Google Analytics', 'CONFIGURE_IN_ADMIN', 'inactive', 'Website analytics tracking', 10000),
('hunter', 'Hunter.io', 'CONFIGURE_IN_ADMIN', 'inactive', 'Email finding and verification', 50),
('clearbit', 'Clearbit', 'CONFIGURE_IN_ADMIN', 'inactive', 'Lead enrichment data', 100);

-- Initialize cost tracking for current month
INSERT OR IGNORE INTO api_cost_tracking (service_name, year, month, budget_limit, current_spend)
SELECT 
    service_name,
    CAST(strftime('%Y', 'now') AS INTEGER) as year,
    CAST(strftime('%m', 'now') AS INTEGER) as month,
    50.00 as budget_limit,
    0 as current_spend
FROM api_keys
WHERE service_name IN ('openai', 'linkedin', 'twitter', 'facebook', 'hunter', 'clearbit');

-- =====================================================
-- TRIGGERS FOR AUTO-UPDATE
-- =====================================================

-- Update api_keys updated_at on change
CREATE TRIGGER IF NOT EXISTS api_keys_updated_at 
AFTER UPDATE ON api_keys
BEGIN
    UPDATE api_keys SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Update system_settings updated_at on change
CREATE TRIGGER IF NOT EXISTS system_settings_updated_at 
AFTER UPDATE ON system_settings
BEGIN
    UPDATE system_settings SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Update feature_toggles updated_at on change
CREATE TRIGGER IF NOT EXISTS feature_toggles_updated_at 
AFTER UPDATE ON feature_toggles
BEGIN
    UPDATE feature_toggles SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Update feature usage count when enabled
CREATE TRIGGER IF NOT EXISTS feature_toggles_usage_count 
AFTER UPDATE OF is_enabled ON feature_toggles
WHEN NEW.is_enabled = 1 AND OLD.is_enabled = 0
BEGIN
    UPDATE feature_toggles 
    SET usage_count = usage_count + 1,
        last_used_at = datetime('now')
    WHERE id = NEW.id;
END;

-- Update API usage count on new log entry
CREATE TRIGGER IF NOT EXISTS api_usage_count_increment 
AFTER INSERT ON api_usage_logs
BEGIN
    UPDATE api_keys 
    SET usage_count = usage_count + 1,
        error_count = CASE WHEN NEW.success = 0 THEN error_count + 1 ELSE error_count END,
        last_error = CASE WHEN NEW.success = 0 THEN NEW.error_message ELSE last_error END,
        last_error_at = CASE WHEN NEW.success = 0 THEN NEW.created_at ELSE last_error_at END,
        updated_at = datetime('now')
    WHERE service_name = NEW.service_name;
    
    -- Update cost tracking
    UPDATE api_cost_tracking
    SET current_spend = current_spend + NEW.cost,
        total_requests = total_requests + 1,
        successful_requests = CASE WHEN NEW.success = 1 THEN successful_requests + 1 ELSE successful_requests END,
        failed_requests = CASE WHEN NEW.success = 0 THEN failed_requests + 1 ELSE failed_requests END,
        last_updated = datetime('now')
    WHERE service_name = NEW.service_name
        AND year = CAST(strftime('%Y', NEW.created_at) AS INTEGER)
        AND month = CAST(strftime('%m', NEW.created_at) AS INTEGER);
END;

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- API Keys Overview with Stats
CREATE VIEW IF NOT EXISTS vw_api_keys_overview AS
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
    ROUND(ak.error_count * 100.0 / NULLIF(ak.usage_count, 0), 2) as error_rate_percent,
    act.current_spend as month_spend,
    act.budget_limit as month_budget,
    ROUND(act.current_spend * 100.0 / NULLIF(act.budget_limit, 0), 2) as budget_used_percent,
    ak.created_at,
    ak.updated_at
FROM api_keys ak
LEFT JOIN api_cost_tracking act ON 
    ak.service_name = act.service_name 
    AND act.year = CAST(strftime('%Y', 'now') AS INTEGER)
    AND act.month = CAST(strftime('%m', 'now') AS INTEGER);

-- Recent API Usage
CREATE VIEW IF NOT EXISTS vw_recent_api_usage AS
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

-- Feature Toggles with Dependencies
CREATE VIEW IF NOT EXISTS vw_features_status AS
SELECT 
    ft.id,
    ft.feature_key,
    ft.feature_name,
    ft.is_enabled,
    ft.beta_feature,
    ft.usage_count,
    ft.last_used_at,
    ft.requires_api_keys,
    ft.description,
    CASE 
        WHEN ft.requires_api_keys IS NULL OR ft.requires_api_keys = '[]' THEN 1
        ELSE (
            SELECT COUNT(*)
            FROM api_keys ak
            WHERE ak.is_enabled = 1 
            AND ak.status = 'active'
            AND ak.service_name IN (
                SELECT value FROM json_each(ft.requires_api_keys)
            )
        ) >= (
            SELECT COUNT(*)
            FROM json_each(ft.requires_api_keys)
        )
    END as dependencies_met
FROM feature_toggles ft;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Run these to verify the installation:
-- SELECT COUNT(*) as total_tables FROM sqlite_master WHERE type='table' AND name LIKE 'api_%';
-- SELECT * FROM vw_api_keys_overview;
-- SELECT * FROM vw_features_status;
-- SELECT * FROM system_settings WHERE category = 'api';

COMMIT;
