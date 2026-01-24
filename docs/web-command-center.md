# 🌐 Web Command Center

## Overview

**Web Command Center** adalah fitur killer dari The Framework yang memungkinkan Anda menjalankan operasi maintenance database dan sistem **langsung dari browser**, tanpa perlu akses SSH.

### Why This Matters?

Mayoritas hosting gratis (InfinityFree, 000webhost, Hostinger Free) **tidak menyediakan SSH access**. Developer terpaksa:

- ❌ Upload SQL file manual via phpMyAdmin
- ❌ Edit file via FTP satu-per-satu
- ❌ Tidak bisa run migration otomatis

**The Framework solves this!** ✅

---

## 🔐 Security (v5.0.0)

Web Command Center dilindungi dengan **4-layer security**:

### Layer 1: Feature Toggle

```bash
# .env
ALLOW_WEB_MIGRATION=true  # Must be explicitly enabled
```

### Layer 2: IP Whitelist

```bash
# .env
SYSTEM_ALLOWED_IPS=127.0.0.1,203.45.67.89
# Comma-separated IP addresses
# Use '*' to allow all (NOT RECOMMENDED)
```

### Layer 3: Basic Authentication (Optional)

```bash
# .env
SYSTEM_AUTH_USER=admin
SYSTEM_AUTH_PASS=your_secure_password
```

### Layer 4: APP_KEY Validation

```bash
# Every request must include your APP_KEY
?key=YOUR_APP_KEY
```

---

## 🚀 Available Endpoints

### 1. Database Migration

**URL:** `/_system/migrate?key=YOUR_APP_KEY`

**Function:** Menjalankan semua migration files yang belum dieksekusi

**Example:**

```
https://yoursite.com/_system/migrate?key=base64:abcd1234...
```

**Response:**

```
⚙️ SYSTEM MIGRATION TOOL
==============================
✔ Migrated: 2026_01_01_create_users_table
✔ Migrated: 2026_01_02_create_posts_table

✨ Migration Completed!
```

---

### 2. Database Seeding

**URL:** `/_system/seed?key=YOUR_APP_KEY`

**Function:** Menjalankan semua seeder files

**Example:**

```
https://yoursite.com/_system/seed?key=base64:abcd1234...
```

**Response:**

```
🌱 SYSTEM DATABASE SEEDER
==============================
✔ Seeded: UserSeeder
✔ Seeded: PostSeeder

✨ Database Seeding Completed!
```

---

### 3. Clear Cache

**URL:** `/_system/clear-cache?key=YOUR_APP_KEY`

**Function:** Menghapus cache views dan framework cache

**Use Case:**

- Setelah update view templates
- Setelah update configuration
- Aplikasi menampilkan data lama

---

### 4. System Status

**URL:** `/_system/status?key=YOUR_APP_KEY`

**Function:** Check PHP version, extensions, dan system info

**Response:**

```
📊 SYSTEM STATUS
==============================
PHP Version: 8.3.0
Server Software: LiteSpeed

Extension Status:
pdo_mysql      : OK
mbstring       : OK
openssl        : OK
json           : OK
ctype          : OK
```

---

## 📋 Setup Guide

### For Development (Local)

**Step 1:** Update `.env`

```bash
APP_ENV=local
ALLOW_WEB_MIGRATION=true
SYSTEM_ALLOWED_IPS=127.0.0.1,*
SYSTEM_AUTH_USER=
SYSTEM_AUTH_PASS=
```

**Step 2:** Test access

```bash
# Open browser
http://localhost:8080/_system/status?key=YOUR_APP_KEY
```

---

### For Production (Shared Hosting)

**Step 1:** Update `.env` di server

```bash
APP_ENV=production
ALLOW_WEB_MIGRATION=true

# IMPORTANT: Replace with your actual IP
SYSTEM_ALLOWED_IPS=203.45.67.89

# HIGHLY RECOMMENDED: Enable Basic Auth
SYSTEM_AUTH_USER=admin
SYSTEM_AUTH_PASS=super_secure_password_123
```

**Step 2:** Get your IP address

```bash
# Visit this URL to see your IP
https://api.ipify.org
```

**Step 3:** Test with your IP whitelisted

```
https://yoursite.com/_system/status?key=YOUR_APP_KEY
```

**Step 4:** If successful, run migration

```
https://yoursite.com/_system/migrate?key=YOUR_APP_KEY
```

---

## ⚠️ Security Best Practices

### DO's ✅

1. **Always use HTTPS** in production
2. **Whitelist only your IP** (never use `*` in production)
3. **Enable Basic Auth** for extra security
4. **Change APP_KEY regularly**
5. **Disable** after deployment complete:
   ```bash
   ALLOW_WEB_MIGRATION=false
   ```

### DON'Ts ❌

1. ❌ Never commit `.env` to Git
2. ❌ Never share your `APP_KEY` publicly
3. ❌ Never use `SYSTEM_ALLOWED_IPS=*` in production
4. ❌ Never leave `ALLOW_WEB_MIGRATION=true` always on

---

## 🔧 Troubleshooting

### Error: "ACCESS DENIED: Your IP is not whitelisted"

**Solution:**

1. Check your current IP: https://api.ipify.org
2. Add it to `.env`:
   ```bash
   SYSTEM_ALLOWED_IPS=127.0.0.1,YOUR_NEW_IP
   ```

### Error: "AUTHENTICATION REQUIRED"

**Solution:**
Browser will ask for username/password. Enter:

- Username: Value dari `SYSTEM_AUTH_USER`
- Password: Value dari `SYSTEM_AUTH_PASS`

### Error: "Invalid Security Key"

**Solution:**

1. Check your `APP_KEY` in `.env`
2. Make sure URL includes `?key=YOUR_APP_KEY`
3. Example:
   ```
   https://site.com/_system/status?key=base64:abc123...
   ```

---

## 🎯 Real-World Workflow

### Scenario: Deploying to InfinityFree

**Step 1:** Upload files via FTP

```
- Upload all files EXCEPT vendor/
- Upload .env.example and rename to .env
```

**Step 2:** Install dependencies

```
# InfinityFree provides composer via SSH alternative
# OR upload vendor/ folder from local
```

**Step 3:** Configure `.env`

```bash
DB_HOST=sqlxxx.infinityfreeapp.com
DB_NAME=ifxxxx_database
DB_USER=ifxxxx_user
DB_PASS=your_password

ALLOW_WEB_MIGRATION=true
SYSTEM_ALLOWED_IPS=YOUR_HOME_IP
```

**Step 4:** Run migration via browser

```
https://yoursite.rf.gd/_system/migrate?key=YOUR_APP_KEY
```

**Step 5:** Run seeder

```
https://yoursite.rf.gd/_system/seed?key=YOUR_APP_KEY
```

**Step 6:** Disable Web Command Center

```bash
ALLOW_WEB_MIGRATION=false
```

**Done!** 🎉

---

## 📊 Comparison

| Feature               | Traditional Laravel | The Framework WCC            |
| --------------------- | ------------------- | ---------------------------- |
| SSH Required          | ✅ Yes              | ❌ No                        |
| Works on Free Hosting | ❌ No               | ✅ Yes                       |
| Migration via Browser | ❌ No               | ✅ Yes                       |
| Security Layers       | 1 (SSH key)         | 4 (Toggle + IP + Auth + Key) |
| Setup Time            | 30+ min             | 5 min                        |

---

## 🎓 Advanced: Block via Webserver

For extra security, block `/_system/*` via Apache/Nginx and only use SSH:

### Apache (.htaccess)

```apache
<LocationMatch "^/_system">
    Require all denied
</LocationMatch>
```

### Nginx

```nginx
location ~ ^/_system/ {
    deny all;
    return 404;
}
```

Then use SSH:

```bash
php artisan migrate
php artisan db:seed
```

---

## 🔗 Related Documentation

- [Security Guide](security.md)
- [Deployment Guide](deployment.md)
- [Environment Configuration](environment.md)
- [Migrations](migrations.md)

---

<div align="center">

**Web Command Center adalah fitur yang membedakan The Framework!**

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
