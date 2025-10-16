# Hostinger Cron Jobs Setup Guide

## How to Set Up Cron Jobs on Hostinger

### Step 1: Access Hostinger Control Panel
1. Log in to your Hostinger account
2. Select your hosting plan for **adilcreator.com**
3. Go to **Advanced** → **Cron Jobs**

### Step 2: Add Cron Jobs

#### 1. Social Media Queue Processor
**Frequency:** Every 1 hour  
**Command:**
```bash
/usr/bin/php /home/u720615217/public_html/backend/cron/process_social_queue.php
```

**Settings in Hostinger:**
- Minute: `0`
- Hour: `*` (every hour)
- Day: `*`
- Month: `*`
- Weekday: `*`

**What it does:** Processes queued social media posts and publishes them to Facebook, Instagram, Twitter, LinkedIn

---

#### 2. Auto Blog Generator  
**Frequency:** Daily at 9:00 AM  
**Command:**
```bash
/usr/bin/php /home/u720615217/public_html/backend/cron/auto_blog_generator.php
```

**Settings in Hostinger:**
- Minute: `0`
- Hour: `9`
- Day: `*`
- Month: `*`
- Weekday: `*`

**What it does:** Automatically generates and publishes a blog post using AI, then queues social media promotions

**Note:** Set `CRON_AUTO_BLOG_ENABLED=true` in `.env` to enable this feature

---

#### 3. Cache Cleanup
**Frequency:** Daily at 3:00 AM  
**Command:**
```bash
/usr/bin/php /home/u720615217/public_html/backend/cron/cleanup_cache.php
```

**Settings in Hostinger:**
- Minute: `0`
- Hour: `3`
- Day: `*`
- Month: `*`
- Weekday: `*`

**What it does:** Removes expired cache files to free up disk space

---

### Step 3: Test Cron Jobs

You can test cron jobs manually by running them via SSH or adding a test URL:

#### Via Web Browser (for testing only):
```
https://adilcreator.com/backend/cron/process_social_queue.php?cron_key=test123
```

**IMPORTANT:** Remove the `?cron_key` check after testing for security!

#### Via SSH (if available):
```bash
php /home/u720615217/public_html/backend/cron/process_social_queue.php
```

---

### Step 4: Monitor Cron Job Logs

Logs are written to PHP error log. To view them:

1. In Hostinger Control Panel, go to **Files** → **File Manager**
2. Navigate to `/home/u720615217/logs/`
3. View `php_errors.log`

Or add this to your cron command to save output:
```bash
/usr/bin/php /home/u720615217/public_html/backend/cron/process_social_queue.php >> /home/u720615217/logs/cron_social.log 2>&1
```

---

## Cron Job Status & Troubleshooting

### Check if Cron is Running:
Look for these log entries in `php_errors.log`:
```
[CRON] Social queue processing started at 2025-10-16 14:00:01
[CRON] Processed: 5, Succeeded: 5, Failed: 0
[CRON] Social queue processing completed at 2025-10-16 14:00:23
```

### Common Issues:

#### 1. "Access denied"
- Make sure script has execute permissions: `chmod +x cron/*.php`
- Or ensure cron runs via `/usr/bin/php` (not direct execution)

#### 2. "Database connection failed"
- Check `.env` file exists in `/home/u720615217/public_html/`
- Verify database credentials are correct

#### 3. "OpenAI API key not configured"
- Add `OPENAI_API_KEY` to `.env` file
- Enable AI features: `AI_CONTENT_GENERATION_ENABLED=true`

#### 4. Cron job not running
- Verify the path is correct (Hostinger uses `/home/u720615217/public_html/`)
- Check cron job syntax in Hostinger panel
- Ensure PHP CLI is enabled on your hosting plan

#### 5. Execution timeout
- Scripts are limited to 50 seconds (shared hosting)
- Reduce number of posts processed per run
- Split large tasks into smaller batches

---

## Email Notifications for Cron Jobs

To receive email notifications when cron jobs run, add to the command:
```bash
/usr/bin/php /home/u720615217/public_html/backend/cron/process_social_queue.php | mail -s "Social Queue Cron" admin@adilcreator.com
```

Or use Hostinger's built-in email notification settings in the Cron Jobs panel.

---

## Recommended Cron Schedule Summary

| Cron Job | Frequency | Time | Priority |
|----------|-----------|------|----------|
| Cache Cleanup | Daily | 3:00 AM | Low |
| Auto Blog Generator | Daily | 9:00 AM | Medium |
| Social Media Queue | Hourly | Every hour | High |

---

## Advanced: Custom Cron Frequencies

If you need more control (requires Premium/Business plan):

### Post every 2 hours:
```bash
0 */2 * * * /usr/bin/php .../process_social_queue.php
```

### Post twice daily (9 AM and 6 PM):
```bash
0 9,18 * * * /usr/bin/php .../auto_blog_generator.php
```

### Run on weekdays only:
```bash
0 9 * * 1-5 /usr/bin/php .../auto_blog_generator.php
```

---

## Security Best Practices

1. ✅ **Never** expose cron scripts to direct web access
2. ✅ Remove `?cron_key` test parameter after testing
3. ✅ Use `.htaccess` to block access to `/backend/cron/` directory
4. ✅ Monitor logs for suspicious activity
5. ✅ Rotate API keys regularly

---

## Support

If cron jobs are not working:
1. Check Hostinger documentation: https://support.hostinger.com/en/articles/1583368-how-to-set-up-a-cron-job
2. Contact Hostinger support with your cron command
3. Verify your hosting plan supports cron jobs (Premium plans include this)

---

**Last Updated:** 2025-10-16
