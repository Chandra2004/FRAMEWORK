# 📚 The Framework Documentation

<div align="center">

**Complete Guide to The Framework v5.0.0**

[Home](../README.md) • Documentation • [Get Started](#getting-started)

</div>

---

## 📖 Table of Contents

### 🚀 Getting Started

1. [Introduction](#introduction) - What is The Framework?
2. [5-Minute Quick Start](quick-start.md) - Get up and running fast ⚡
3. [Installation](installation.md) - Setup your first project
4. [Configuration](environment.md) - Environment variables
5. [Structure](structure.md) - Project folder structure
6. [Deployment](deployment.md) - Deploy to production

### 💻 The Basics

7. [Routing](routing.md) - URL routing system
8. [Controllers](controllers.md) - Handle HTTP requests
9. [Views & Blade](views.md) - Template engine
10. [Security](security.md) - CSRF, XSS, WAF
11. [Validation](validation.md) - Input validation

### 🗄️ Database

12. [Database](database.md) - Query Builder & connections
13. [Migrations](migrations.md) - Database version control
14. [ORM & Models](orm.md) - Eloquent-like ORM
15. [Relationships](relationships.md) - Model relations

### 🔧 Advanced Topics

16. [Architecture](architecture.md) - MVC pattern
17. [Middleware](middleware.md) - HTTP middleware
18. [Helpers](helpers.md) - Global functions
19. [Performance](performance.md) - Caching & optimization
20. [Testing](testing-guide.md) - Unit & feature tests
21. [Localization](localization.md) - Multi-language
22. [Docker](docker.md) - Containerization
23. [Error Handling](error-handling.md) - Standard Patterns
24. [Rate Limiting](rate-limiting.md) - Brute-force protection

### 🌐 Unique Features

25. [Web Command Center](web-command-center.md) - Manage without SSH ⭐
26. [Artisan CLI](artisan.md) - Command-line tools
27. [Queue System](queue.md) - Background jobs
28. [SEO & Sitemap](seo.md) - Search engine optimization
29. [Email](email.md) - SMTP sending
30. [Payments](payment.md) - Midtrans integration
31. [File Uploads](file-uploads.md) - UploadHandler

### 📝 Tutorials

32. [Build a Blog](tutorial-blog.md) - Complete tutorial
33. [API Development](tutorial-api.md) - REST API guide
34. [Authentication](tutorial-auth.md) - User login system

---

## Introduction

### What is The Framework?

**The Framework** is a modern PHP framework built with a unique mission: make professional web development accessible to **everyone**, including developers who can only afford free shared hosting.

### Philosophy

```
🎯 Simplicity First    - Easy to learn, powerful to use
🌍 Universal Access    - Works on ANY hosting
🛡️ Security by Default - Production-ready from day one
📚 Well Documented     - Comprehensive guides
```

### Why Choose The Framework?

#### Compared to Laravel

| Feature        | Laravel       | The Framework           |
| -------------- | ------------- | ----------------------- |
| Shared Hosting | ❌ Needs VPS  | ✅ Works perfectly      |
| Learning Curve | Medium-High   | Low-Medium              |
| Performance    | Heavy         | Lightweight             |
| SSH Required   | Yes (artisan) | No (Web Command Center) |
| Syntax         | Laravel       | Laravel-like            |
| Documentation  | Excellent     | Comprehensive           |

#### Compared to CodeIgniter

| Feature         | CodeIgniter 4 | The Framework            |
| --------------- | ------------- | ------------------------ |
| Modern PHP      | ✅ PHP 8.1+   | ✅ PHP 8.3+              |
| ORM Quality     | Basic         | Eloquent-like            |
| Security        | Good          | Excellent (WAF built-in) |
| Web Management  | ❌ No         | ✅ Web Command Center    |
| Blade Templates | ❌ No         | ✅ Yes                   |

### System Requirements

```
PHP      >= 8.3
MySQL    >= 5.7 (or MariaDB >= 10.2)
Composer >= 2.0
```

**Extensions Required:**

- PDO PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- JSON PHP Extension
- Ctype PHP Extension

### Quick Example

```php
// routes/web.php
Router::get('/hello/{name}', function($name) {
    return view('hello', ['name' => $name]);
});

// resources/views/hello.blade.php
<!DOCTYPE html>
<html>
<head>
    <title>Hello {{ $name }}</title>
</head>
<body>
    <h1>Hello, {{ $name }}!</h1>
</body>
</html>
```

Visit `/hello/World` → See "Hello, World!"

---

## Next Steps

### For Beginners

1. 📖 Read [Installation Guide](installation.md)
2. 🎓 Follow [Blog Tutorial](tutorial-blog.md)
3. 🔍 Explore [Routing](routing.md)

### For Intermediate

1. 🗄️ Master [Database](database.md)
2. 🔐 Learn [Security](security.md)
3. 🚀 Optimize [Performance](performance.md)

### For Advanced

1. 🏗️ Understand [Architecture](architecture.md)
2. 🧪 Write [Tests](testing.md)
3. 🚢 Deploy [Production](deployment.md)

---

## Need Help?

- 📖 Check [FAQ](faq.md)
- 💬 Join Community Forum _(coming soon)_
- 📧 Email: support@the-framework.ct.ws
- 🐛 Report bugs: [GitHub Issues](https://github.com/chandra2004/the-framework/issues)

---

## Version Guide

| Version   | Status           | PHP  | Release Date | End of Life |
| --------- | ---------------- | ---- | ------------ | ----------- |
| **5.0.0** | ✅ **Current**   | 8.3+ | Jan 2026     | Jan 2028    |
| 4.0.0     | ⚠️ Security only | 8.3+ | Jan 2026     | Jul 2026    |
| 3.x       | ❌ End of life   | 8.1+ | -            | -           |

**Always use the latest version for security patches!**

---

<div align="center">

**Made with ❤️ in Indonesia**

[Back to Top](#-the-framework-documentation) • [Main README](../README.md) • [GitHub](https://github.com/chandra2004/the-framework)

</div>
