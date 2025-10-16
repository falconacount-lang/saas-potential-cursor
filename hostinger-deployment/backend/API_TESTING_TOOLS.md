# 🧪 API Testing Tools

This directory contains several tools to help you verify that your API endpoints are working correctly in both development and production environments.

## 📋 Available Tools

### 1. Web-Based Visual Tester (Recommended for Production)
**File:** `test-api-live.php`

Beautiful web interface that shows real-time API status with color-coded results.

**Usage:**
```
https://adilcreator.com/backend/test-api-live.php
```

**Features:**
- ✅ Auto-detects server URL
- 🎨 Color-coded status (Green/Yellow/Red)
- 📊 Response preview
- 📈 Visual summary with statistics
- 🔄 Easy refresh button

**Best For:**
- Production server testing
- Quick visual verification
- Sharing test results with team
- Non-technical users

---

### 2. Command-Line Tester (For SSH/Terminal Access)
**File:** `test-api-cli.php`

Terminal-based tester with colored output and detailed diagnostics.

**Usage:**
```bash
# Test localhost (default)
php backend/test-api-cli.php

# Test specific URL
php backend/test-api-cli.php https://adilcreator.com/backend

# Using from backend directory
cd backend
./test-api-cli.php

# With .env BACKEND_URL
php backend/test-api-cli.php
```

**Features:**
- ✅ Colored terminal output
- ⏱️ Response time measurements
- 📊 Detailed data preview
- 🔍 Connection diagnostics
- 📈 Exit codes for automation

**Exit Codes:**
- `0` = All tests passed
- `1` = Some tests failed
- `2` = All tests failed

**Best For:**
- SSH access scenarios
- CI/CD pipelines
- Automated testing
- Developers who prefer terminal

---

### 3. Installation Script Tests
**File:** `install.php`

Updated installation script with intelligent URL detection.

**Usage:**
```
https://adilcreator.com/backend/install.php
```

**Features:**
- ✅ Auto-detects production URL
- ✅ Falls back to localhost for development
- ✅ Reads BACKEND_URL from .env
- ✅ Tests APIs after installation

**Best For:**
- Initial deployment
- Database setup
- First-time installation verification

---

## 🎯 Quick Start Guide

### For Production (Hostinger, etc.)

**Step 1:** Open the visual tester in your browser
```
https://your-domain.com/backend/test-api-live.php
```

**Step 2:** Check the results
- All green = Perfect! ✅
- Some yellow = Needs attention ⚠️
- Any red = Troubleshooting needed ❌

**Step 3:** If needed, review detailed logs
```bash
ssh your-server
php ~/public_html/backend/test-api-cli.php
```

### For Local Development

**Step 1:** Start your backend server
```bash
php -S localhost:8000 -t backend
```

**Step 2:** Run the CLI tester
```bash
php backend/test-api-cli.php
```

**Step 3:** Or open in browser
```
http://localhost:8000/test-api-live.php
```

---

## 📊 What Gets Tested

All tools test these core API endpoints:

1. **Test API** (`/api/test.php`)
   - Database connectivity
   - Basic API functionality
   - Configuration status

2. **Settings API** (`/api/settings.php`)
   - Site configuration
   - Theme settings

3. **Blogs API** (`/api/blogs.php`)
   - Blog post retrieval
   - Category filtering

4. **Portfolio API** (`/api/portfolio.php`)
   - Portfolio items
   - Project categories

5. **Services API** (`/api/services.php`)
   - Service listings
   - Pricing information

6. **Testimonials API** (`/api/testimonials.php`)
   - Client testimonials
   - Rating data

---

## 🔧 Troubleshooting

### Issue: "No response" errors

**Possible Causes:**
1. Server not running
2. Incorrect URL
3. Firewall blocking
4. .htaccess rules blocking

**Solutions:**
```bash
# Check if server is accessible
curl https://your-domain.com/backend/api/test.php

# Verify .htaccess isn't blocking
mv backend/.htaccess backend/.htaccess.backup
# Test again, then restore if it worked

# Check file permissions
chmod 644 backend/api/*.php
chmod 755 backend/api
```

### Issue: "Response not JSON"

**Possible Causes:**
1. PHP errors being output
2. Warnings/notices enabled
3. PHP short tags issue

**Solutions:**
```bash
# Check PHP error logs
tail -f ~/logs/error.log

# Test individual endpoint
curl -i https://your-domain.com/backend/api/test.php

# Look for PHP errors before the JSON
```

### Issue: CORS errors

**Solution:**
Update your `.env` file:
```env
FRONTEND_URL=https://your-domain.com
CORS_ALLOWED_ORIGINS=https://your-domain.com,http://localhost:5173
```

---

## 🔐 Security Notes

### For Production:

After confirming APIs work, secure the test tools:

**Option 1: Delete them**
```bash
rm backend/test-api-live.php
rm backend/test-api-cli.php
rm backend/install.php
```

**Option 2: Restrict access via .htaccess**
```apache
<FilesMatch "^(test-api-live|test-api-cli|install)\.php$">
    Order Deny,Allow
    Deny from all
    Allow from YOUR.IP.ADDRESS
</FilesMatch>
```

**Option 3: Rename them**
```bash
mv backend/test-api-live.php backend/test-api-live-secret-name-here.php
```

### For Development:

These tools are safe to use freely in local development environments.

---

## 💡 Integration Examples

### CI/CD Pipeline (GitHub Actions)

```yaml
- name: Test API Endpoints
  run: |
    php backend/test-api-cli.php https://staging.your-domain.com/backend
  continue-on-error: false
```

### Monitoring Script

```bash
#!/bin/bash
# api-monitor.sh - Run every 5 minutes via cron

php /path/to/backend/test-api-cli.php https://your-domain.com/backend

if [ $? -ne 0 ]; then
    echo "API endpoints failing!" | mail -s "API Alert" admin@your-domain.com
fi
```

### Health Check Endpoint

Create a simple health check that uses these tools:

```php
<?php
// health-check.php
require_once 'test-api-cli.php';
// Returns HTTP 200 if all pass, 500 if any fail
```

---

## 📚 Additional Resources

- **Main Documentation:** `../DEPLOYMENT_API_VERIFICATION.md`
- **Fix Summary:** `../DEPLOYMENT_FIX_SUMMARY.md`
- **Environment Config:** `../.env.example`

---

## 🎉 Success Indicators

Your APIs are working perfectly when:

- ✅ All tests show green/success status
- ✅ Response times are under 1000ms
- ✅ JSON responses are valid
- ✅ Database connection confirmed
- ✅ No CORS errors in browser
- ✅ Frontend can fetch data successfully

---

**Last Updated:** 2025-10-16  
**Version:** 1.0.0  
**Compatibility:** PHP 7.4+
