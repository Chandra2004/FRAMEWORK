<div align="center">

**Complete Guide to The Framework v5.0.1**

[Home](../README.md) • Documentation • [Get Started](#getting-started)

</div>

---

**✨ New in v5.0.1 (The Fluent DX Update):**

- 🛠️ **Clean Architecture:** Framework internal controllers moved to `App\Internal`.
- 🚀 **Fluent Helpers:** Modern `view()`, `redirect()`, `json()`, and `flash()` syntax.
- 🎨 **Cleaner Controllers:** Expressive return types without redundant imports.

---

## 📖 Table of Contents

### 🚀 Getting Started

1. [Introduction](#introduction) - What is The Framework?
2. [5-Minute Quick Start](quick-start.md) - Get up and running fast ⚡
3. [Installation](installation.md) - Setup your first project
4. [Configuration](environment.md) - Environment variables
5. [Structure](structure.md) - Project folder structure
6. [Providers](providers.md) - Service providers and container binding
7. [Services](services.md) - Business logic encapsulation (Thin Controllers)
8. [Routing](routing.md) - Defining routes and parameters
9. [Deployment](deployment.md) - Deploy to production

### 💻 The Basics

10. [Views & Blade](views.md) - Template engine
11. [Blade Engine](blade.md) - Custom directives & initialization ⭐ NEW
12. [Session Manager](session.md) - Secure session, flash data ⭐ NEW
13. [Security](security.md) - CSRF, XSS, WAF
14. [Encryption](encryption.md) - AES-256 data protection ⭐ NEW
15. [Validation](validation.md) - Input validation

### 🗄️ Database

14. [Database](database.md) - Query Builder & connections
15. [Database Engine (Advanced)](database-engine.md) - PDO wrapper, transactions & savepoints ⭐ NEW
16. [Migrations](migrations.md) - Database version control
17. [Schema Builder](schema-builder.md) - Column types, indexes & introspection
18. [ORM & Models](orm.md) - Eloquent-like ORM
19. [Relationships](relationships.md) - Model relations
20. [Query Builder](query-builder.md) - Advanced queries

**✨ New in v5.0.1:**

- 🔑 [Foreign Keys & JOINs Guide](foreign-keys-joins-guide.md) - Visual guide with examples
- 📋 [Foreign Keys & JOINs Reference](foreign-keys-joins-reference.md) - Quick reference cheat sheet
- 🚀 [Foreign Keys & JOINs Implementation](foreign-keys-joins-implementation.md) - Implementation details

### 🔧 Advanced Topics

21. [Architecture](architecture.md) - MVC pattern
22. [Middleware](middleware.md) - HTTP middleware
23. [DI Container](container.md) - Dependency Injection & auto-wiring ⭐ NEW
24. [Logging](logging.md) - Multi-channel logging system ⭐ NEW
25. [Cache](cache.md) - Caching system
26. [Helpers](helpers.md) - Global functions
27. [Utility Classes](utilities.md) - Arr, Str, Collection, DatabaseHelper ⭐ NEW
28. [CRUD Trait](crud-trait.md) - Auto CRUD via Trait ⭐ NEW
29. [Performance](performance.md) - Caching & optimization
30. [Testing](testing-guide.md) - Unit & feature tests
31. [Localization](localization.md) - Multi-language
32. [Docker](docker.md) - Containerization
33. [Error Handling](error-handling.md) - Standard Patterns
34. [Rate Limiting](rate-limiting.md) - Brute-force protection

### 🌐 Unique Features

35. [Web Command Center](web-command-center.md) - Manage without SSH ⭐
36. [Tinker (Interactive Shell)](tinker.md) - Debug code live (CLI & Web) 💻
37. [Artisan CLI Engine](artisan.md) - Command-line tools v5 ⭐
38. [Console Engine](console.md) - BaseCommand, 41 commands & stubs ⭐ NEW
39. [Queue System](queue.md) - Background jobs
40. [SEO & Sitemap](seo.md) - Search engine optimization
41. [Email Handler](email.md) - SMTP & Queue sending ⭐
42. [Payment Handler](payment.md) - Midtrans integration ⭐
43. [Upload Handler](file-uploads.md) - Smart Image Engine ⭐

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

| Version   | Status         | PHP  | Release Date | End of Life |
| --------- | -------------- | ---- | ------------ | ----------- |
| **5.0.1** | ✅ **Current** | 8.3+ | Feb 2026     | Feb 2028    |
| 5.0.1     | ✅ Supported   | 8.3+ | Feb 2026     | Feb 2028    |
| 5.0.0     | ✅ Supported   | 8.3+ | Jan 2026     | Jan 2028    |
| 3.x       | ❌ End of life | 8.1+ | -            | -           |

**Always use the latest version for security patches!**

---

<div align="center">

**Made with ❤️ in Indonesia**

[Back to Top](#-the-framework-documentation) • [Main README](../README.md) • [GitHub](https://github.com/chandra2004/the-framework)

</div>
