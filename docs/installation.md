# 📥 Installation Guide

## System Requirements

Before installing The Framework, make sure your system meets these requirements:

### Minimum Requirements

```
PHP       >= 8.3.0
MySQL     >= 5.7 (or MariaDB >= 10.2)
Composer  >= 2.0
Apache    >= 2.4 (with mod_rewrite)
```

### Required PHP Extensions

```
✅ PDO
✅ pdo_mysql
✅ mbstring
✅ openssl
✅ json
✅ ctype
✅ fileinfo
✅ tokenizer
```

### Check Requirements

```bash
php -v                    # Check PHP version
php -m | grep pdo        # Check PDO extension
composer --version       # Check Composer
```

---

## Installation Methods

### Method 1: Git Clone (Recommended)

```bash
# Clone repository
git clone https://github.com/chandra2004/the-framework.git my-project
cd my-project

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate

# Run development server
php artisan serve
```

Visit: **http://127.0.0.1:8080**

---

### Method 2: Composer Create-Project

```bash
composer create-project chandra2004/the-framework my-project
cd my-project
php artisan setup
php artisan serve
```

---

### Method 3: Download ZIP

1. Download from: https://github.com/chandra2004/the-framework/archive/refs/heads/main.zip
2. Extract to your project folder
3. Open terminal in project directory
4. Run:
   ```bash
   composer install
   php artisan setup
   php artisan serve
   ```

---

## Configuration

### Database Setup

Edit `.env` file:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=root
DB_PASS=your_password
```

### Create Database

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or via PHPMyAdmin:

1. Open http://localhost/phpmyadmin
2. Click "New"
3. Database name: `your_database_name`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Run Migrations

```bash
php artisan migrate
```

### Seed Sample Data (Optional)

```bash
php artisan db:seed
```

---

## Web Server Configuration

### Apache

**Public Directory:**
Point your web server's document root to the `public` directory.

**`.htaccess` (Root Directory):**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**`public/.htaccess`:**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### Nginx

```nginx
server {
    listen 80;
    server_name yoursite.com;
    root /path/to/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Shared Hosting Installation

### via FTP

1. **Upload files:**
   - Upload ALL files to `public_html/` or `htdocs/`
   - OR upload to subfolder: `public_html/my-app/`

2. **Create `.env` file:**
   - Copy `.env.example` dan rename to `.env`
   - Edit database credentials

3. **Install dependencies:**
   - **Option A:** Upload `vendor/` folder from local
   - **Option B:** Use hosting's composer (if available)
   - **Option C:** Use Web Command Center (no composer needed!)

4. **Run migration:**

   ```
   https://yoursite.com/_system/migrate
   ```

5. **Done!**

📖 [Deployment Guide](deployment.md) untuk detail lengkap.

---

## Troubleshooting

### Error: "Class not found"

```bash
composer dump-autoload
```

### Error: "Permission denied"

```bash
chmod 755 -R .
chmod 777 -R storage/
chmod 777 -R storage/logs/
chmod 777 -R storage/framework/views/
```

### Error: "APP_KEY not set"

```bash
php artisan key:generate
```

### Error: "PDO driver not found"

Install PHP MySQL extension:

```bash
# Ubuntu/Debian
sudo apt-get install php8.3-mysql

# CentOS/RHEL
sudo yum install php83-mysqlnd

# Windows (xampp)
Edit php.ini, uncomment: extension=pdo_mysql
```

---

## Verification

### Check Installation

```bash
php artisan --version
# Output: The Framework v5.0.0

php artisan route:list
# Shows all registered routes

php artisan db:status
# Check database connection
```

### Test Homepage

Visit: `http://127.0.0.1:8080` or your domain

You should see: **"Welcome to The Framework"**

---

## Next Steps

1. 📖 Read [Structure Guide](structure.md)
2. 🛣️ Learn [Routing](routing.md)
3. 🎨 Create [Views](views.md)
4. 🗄️ Setup [Database](database.md)
5. 🎓 Follow [Tutorial](tutorial-blog.md)

---

<div align="center">

**Installation complete! Happy coding! 🚀**

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
