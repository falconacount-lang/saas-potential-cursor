# 🚀 Hostinger Deployment Guide
## Adil Creator - Complete Deployment Instructions

**Last Updated:** 2025-10-16  
**Domain:** adilcreator.com  
**Database:** u720615217_adil_db  
**Hosting:** Hostinger Premium Shared Hosting

---

## 📋 Pre-Deployment Checklist

### ✅ Before You Start
- [ ] Hostinger account active with Premium/Business plan
- [ ] Domain adilcreator.com pointed to Hostinger nameservers
- [ ] SSL certificate enabled (free with Hostinger)
- [ ] FTP/File Manager access ready
- [ ] phpMyAdmin access available
- [ ] Backup current site (if exists)

---

## 🔧 Step 1: Upload Files to Hostinger

### Option A: Via File Manager (Recommended for Beginners)

1. **Login to Hostinger Control Panel**
   - Go to https://hpanel.hostinger.com
   - Select your hosting plan for adilcreator.com

2. **Access File Manager**
   - Go to **Files** → **File Manager**
   - Navigate to `public_html/`

3. **Upload Backend Files**
   - Compress `/workspace/backend/` into `backend.zip`
   - Upload `backend.zip` to `public_html/`
   - Extract the zip file
   - Delete `backend.zip` after extraction

4. **Upload Frontend Files**
   - Build React app: `npm run build`
   - Upload everything from `/workspace/dist/` to `public_html/`
   - Or upload to `public_html/app/` if you want backend at root

5. **Upload .env File**
   - Upload `/workspace/.env` to `public_html/`
   - **IMPORTANT:** Never upload to git, only via FTP/File Manager

### Option B: Via FTP (For Advanced Users)

```bash
# Using FileZilla or similar FTP client
Host: ftp.adilcreator.com (or IP from Hostinger)
Username: u720615217
Password: Your Hostinger FTP password
Port: 21

# Upload structure:
/public_html/
  ├── backend/          (PHP backend)
  ├── .env             (Environment variables)
  ├── .htaccess        (Root htaccess)
  ├── index.html       (React app entry)
  ├── assets/          (React build assets)
  └── ...              (Other React files)
```

---

## 🗄️ Step 2: Set Up MySQL Database

### Via phpMyAdmin:

1. **Access phpMyAdmin**
   - Hostinger Control Panel → **Databases** → **phpMyAdmin**
   - Select database: `u720615217_adil_db`

2. **Import Database Schema**
   - Click **Import** tab
   - Choose file: `/workspace/backend/database/hostinger_mysql_schema.sql`
   - Click **Go**
   - Wait for import to complete (should take 10-30 seconds)

3. **Import Additional Tables**
   - Import: `/workspace/backend/database/migrations/social_media_tables.sql`
   - This adds social media queue and AI tables

4. **Verify Tables Created**
   - Check that 30+ tables exist
   - Look for: `users`, `blogs`, `services`, `portfolio`, `social_post_queue`, etc.

5. **Create Admin User (if not exists)**
   ```sql
   INSERT INTO users (email, password, name, role, status, email_verified, created_at) 
   VALUES (
     'admin@adilcreator.com', 
     '$2y$12$PXq0z7MbJKf/AQMEe6Fjsu6tZfjErrbYrGvtWyDnMa2my.xw46Xg2',
     'Adil Creator Admin', 
     'admin', 
     'active', 
     1, 
     NOW()
   );
   ```
   **Default Password:** `Muhadilmmad#11213` (Change after first login!)

---

## 🔐 Step 3: Configure Environment Variables

Your `.env` file is already created with correct credentials:

```env
# Database (Already configured)
DB_HOST=localhost
DB_NAME=u720615217_adil_db
DB_USER=u720615217_adil
DB_PASS=Muhadilmmad#11213

# Email (Already configured)
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USERNAME=admin@adilcreator.com
SMTP_PASSWORD=Muhadilmmad#11213
```

### What You Need to Add:

1. **Generate Secure JWT Secret**
   ```bash
   # Run on your local machine
   openssl rand -base64 32
   # Copy output and update JWT_SECRET in .env
   ```

2. **Add OpenAI API Key** (for AI features)
   - Get key from: https://platform.openai.com/api-keys
   - Update `OPENAI_API_KEY=sk-your-key-here`

3. **Add Social Media Credentials** (optional, for auto-posting)
   - Follow setup guides for each platform
   - Update META_*, TWITTER_*, LINKEDIN_* variables

---

## ⚙️ Step 4: Set File Permissions

Via File Manager or FTP:

```bash
# Directories: 755
chmod 755 public_html/
chmod 755 public_html/backend/
chmod 755 public_html/backend/api/

# Writable directories: 777
chmod 777 public_html/backend/uploads/
chmod 777 public_html/backend/cache/

# PHP files: 644
chmod 644 public_html/backend/*.php
chmod 644 public_html/backend/api/*.php

# .env file: 600 (most secure)
chmod 600 public_html/.env

# .htaccess files: 644
chmod 644 public_html/.htaccess
chmod 644 public_html/backend/.htaccess
```

**In Hostinger File Manager:**
- Right-click folder/file → **Permissions**
- Set numbers as above

---

## 🌐 Step 5: Configure Domain & SSL

### DNS Configuration (If Not Done):
1. Go to Hostinger **Domains** section
2. Select adilcreator.com
3. Verify nameservers point to Hostinger:
   - ns1.dns-parking.com
   - ns2.dns-parking.com

### Enable SSL Certificate:
1. Go to **SSL** section in Hostinger panel
2. Click **Install SSL** for adilcreator.com
3. Choose **Free SSL** (Hostinger provides Let's Encrypt)
4. Wait 10-15 minutes for activation
5. Verify: https://adilcreator.com should work with 🔒

---

## ⏰ Step 6: Set Up Cron Jobs

1. **Go to Advanced → Cron Jobs**

2. **Add Social Media Queue Processor**
   ```
   Frequency: Every 1 hour
   Command: /usr/bin/php /home/u720615217/public_html/backend/cron/process_social_queue.php
   
   Schedule:
   Minute: 0
   Hour: * (every hour)
   Day: *
   Month: *
   Weekday: *
   ```

3. **Add Auto Blog Generator**
   ```
   Frequency: Daily at 9 AM
   Command: /usr/bin/php /home/u720615217/public_html/backend/cron/auto_blog_generator.php
   
   Schedule:
   Minute: 0
   Hour: 9
   Day: *
   Month: *
   Weekday: *
   ```

4. **Add Cache Cleanup**
   ```
   Frequency: Daily at 3 AM
   Command: /usr/bin/php /home/u720615217/public_html/backend/cron/cleanup_cache.php
   
   Schedule:
   Minute: 0
   Hour: 3
   Day: *
   Month: *
   Weekday: *
   ```

**Note:** Enable auto-blog by setting `CRON_AUTO_BLOG_ENABLED=true` in `.env`

---

## 🧪 Step 7: Test Your Deployment

### Test Backend API:
```
✅ https://adilcreator.com/backend/api/test.php
   Should return: {"success": true, "message": "API is working"}

✅ https://adilcreator.com/backend/api/blogs.php
   Should return list of blog posts

✅ https://adilcreator.com/backend/api/services.php
   Should return list of services
```

### Test Frontend:
```
✅ https://adilcreator.com
   Should load React app

✅ https://adilcreator.com/admin
   Should load admin login page
```

### Test Admin Login:
```
Email: admin@adilcreator.com
Password: Muhadilmmad#11213

IMPORTANT: Change password immediately after first login!
```

### Test Database Connection:
Create test file: `public_html/backend/test_db.php`
```php
<?php
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful!";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
```
Access: https://adilcreator.com/backend/test_db.php
**Delete this file after testing!**

---

## 🚨 Troubleshooting Common Issues

### Issue 1: "500 Internal Server Error"
**Causes:**
- PHP syntax error
- .htaccess misconfiguration
- File permissions wrong

**Solutions:**
```bash
# Check PHP error logs
Hostinger Panel → Files → File Manager → /home/u720615217/logs/error_log

# Verify PHP version (should be 8.0+)
Hostinger Panel → Advanced → PHP Configuration

# Temporarily disable .htaccess
Rename .htaccess to .htaccess.bak and test
```

### Issue 2: "Database Connection Failed"
**Solutions:**
```bash
# Verify .env file exists
ls -la /home/u720615217/public_html/.env

# Check database credentials in phpMyAdmin
# Database name: u720615217_adil_db
# Username: u720615217_adil
# Host: localhost (not 127.0.0.1)
```

### Issue 3: "API Returns 404"
**Solutions:**
```bash
# Check .htaccess exists in backend/
ls -la /home/u720615217/public_html/backend/.htaccess

# Verify mod_rewrite is enabled
# (Should be enabled by default on Hostinger)

# Test direct PHP file access
https://adilcreator.com/backend/api/test.php (should work)
```

### Issue 4: "Cron Job Not Running"
**Solutions:**
```bash
# Verify path is correct
/home/u720615217/public_html/backend/cron/...

# Check if script has errors
php -l /home/u720615217/public_html/backend/cron/process_social_queue.php

# View cron execution logs
/home/u720615217/logs/cron_log.txt
```

### Issue 5: "React App Shows Blank Page"
**Solutions:**
```bash
# Check if assets loaded
Browser DevTools → Network tab
# All .js and .css files should return 200 OK

# Verify index.html exists
ls -la /home/u720615217/public_html/index.html

# Check browser console for errors
F12 → Console tab
```

---

## 🔒 Post-Deployment Security

### 1. Change Default Credentials
```sql
-- Change admin password
UPDATE users 
SET password = PASSWORD_HASH('YourNewSecurePassword', PASSWORD_BCRYPT, ['cost' => 12])
WHERE email = 'admin@adilcreator.com';
```

### 2. Update JWT Secret
```env
# In .env file
JWT_SECRET=generate-new-random-string-here-32-chars-minimum
```

### 3. Disable Debug Mode
```env
APP_ENV=production
APP_DEBUG=false
DISPLAY_ERRORS=false
```

### 4. Configure Firewall (Hostinger provides)
- Hostinger Panel → Security → IP Blocker
- Block suspicious IPs
- Enable DDoS protection

### 5. Enable Backups
```bash
Hostinger Panel → Backups → Enable Automatic Backups
Schedule: Daily backups
Retention: 7 days minimum
```

---

## 📊 Monitoring & Maintenance

### Daily Checks:
- [ ] Site is accessible (https://adilcreator.com)
- [ ] Admin panel works
- [ ] No errors in PHP error log

### Weekly Checks:
- [ ] Cron jobs are running (check logs)
- [ ] Database size (should be < 100MB for optimal performance)
- [ ] Disk space usage (< 80% of quota)
- [ ] AI budget usage (check OpenAI dashboard)

### Monthly Checks:
- [ ] Update composer dependencies: `composer update`
- [ ] Review security logs
- [ ] Clean up old cache files
- [ ] Optimize database tables (phpMyAdmin → Operations → Optimize)

---

## 🎯 Performance Optimization

### Enable OPcache (Built-in on Hostinger)
Hostinger Panel → PHP Configuration → Enable OPcache

### Enable Gzip Compression
Already configured in `.htaccess`

### Use Cloudflare (Free CDN)
1. Sign up at cloudflare.com
2. Add domain: adilcreator.com
3. Update nameservers to Cloudflare
4. Benefits: Faster loading, DDoS protection, free SSL

---

## 📞 Support Resources

### Hostinger Support:
- 24/7 Live Chat: Available in Hostinger panel
- Email: support@hostinger.com
- Knowledge Base: https://support.hostinger.com

### Documentation:
- Backend API: `/workspace/SAAS_BACKEND_AUDIT_REPORT.md`
- Cron Jobs: `/workspace/backend/cron/README_CRON_SETUP.md`
- Social Media: Check integration class files

---

## ✅ Deployment Complete Checklist

After following all steps, verify:

- [ ] ✅ Files uploaded to Hostinger
- [ ] ✅ Database created and populated
- [ ] ✅ .env file configured
- [ ] ✅ File permissions set correctly
- [ ] ✅ SSL certificate active
- [ ] ✅ Domain resolves to https://adilcreator.com
- [ ] ✅ Backend API responding
- [ ] ✅ Frontend React app loading
- [ ] ✅ Admin login working
- [ ] ✅ Cron jobs scheduled
- [ ] ✅ Email sending works
- [ ] ✅ Security measures implemented
- [ ] ✅ Backups enabled

---

## 🎉 Next Steps

1. **Change default passwords immediately**
2. **Add social media API credentials** (for auto-posting)
3. **Add OpenAI API key** (for AI features)
4. **Test email sending** (contact form, newsletter)
5. **Create sample content** (blogs, portfolio, services)
6. **Test mobile responsiveness**
7. **Submit sitemap to Google** (via Search Console)
8. **Start monitoring analytics**

---

**Congratulations! Your site is now live! 🚀**

For questions or issues, refer to the audit report or contact support.

**Last Updated:** 2025-10-16  
**Version:** 1.0  
**Status:** Production Ready ✅
