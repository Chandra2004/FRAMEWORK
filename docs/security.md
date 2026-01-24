# 🛡️ Security Guide (v5.0.0)

The Framework dibangun dengan prinsip **Security First**. Version 5.0.0 memiliki grade keamanan **A-** dengan implementasi defense-in-depth yang comprehensive.

---

## 📋 Table of Contents

1. [Security Features Overview](#-security-features-overview)
2. [CSRF Protection](#-csrf-protection)
3. [XSS Protection](#-xss-protection)
4. [SQL Injection Prevention](#-sql-injection-prevention)
5. [Web Application Firewall (WAF)](#-web-application-firewall-waf)
6. [Web Command Center Security](#-web-command-center-security-new-in-v500)
7. [Secure Headers](#-secure-headers)
8. [Data Encryption](#-data-encryption)
9. [Password Hashing](#-password-hashing)
10. [Security Best Practices](#-security-best-practices)

---

## 🎯 Security Features Overview

### Built-in Protection (v5.0.0)

| Threat                            | Protection            | Status      |
| --------------------------------- | --------------------- | ----------- |
| SQL Injection                     | Prepared Statements   | ✅ 100%     |
| XSS (Cross-Site Scripting)        | Auto-escaping + WAF   | ✅ 100%     |
| CSRF (Cross-Site Request Forgery) | Token validation      | ✅ 100%     |
| Command Injection                 | WAF Pattern Detection | ✅ Enhanced |
| Path Traversal                    | Realpath validation   | ✅ 100%     |
| Clickjacking                      | X-Frame-Options       | ✅ 100%     |
| HTTPS Downgrade                   | HSTS Headers          | ✅ 100%     |

**Security Grade:** **A-** (Production Ready)

---

## 🔐 CSRF Protection

**Cross-Site Request Forgery** adalah serangan yang memaksa user terautentikasi melakukan aksi yang tidak diinginkan.

### How It Works

```
1. User login → Server generate CSRF token
2. Token disimpan di session
3. Setiap form POST harus include token
4. Server validate: token match? → proceed : reject (403)
```

### Implementation

#### In Blade Templates

```html
<form method="POST" action="/profile">
  @csrf
  <input type="text" name="name" value="{{ $user->name }}" />
  <button type="submit">Update</button>
</form>
```

#### In Plain PHP

```html
<form method="POST" action="/profile">
  <input
    type="hidden"
    name="_token"
    value="<?= \TheFramework\Helpers\Helper::generateCsrfToken() ?>"
  />
  <input type="text" name="name" />
  <button type="submit">Update</button>
</form>
```

#### In AJAX

```javascript
fetch("/api/profile", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
  },
  body: JSON.stringify({ name: "John" }),
});
```

### Bypass for API Routes (Optional)

```php
// routes/api.php
Router::group(['middleware' => []], function() {
    // No CSRF for API endpoints (use API token instead)
    Router::post('/api/data', [ApiController::class, 'store']);
});
```

---

## 🦠 XSS Protection

**Cross-Site Scripting** terjadi saat aplikasi menampilkan input user tanpa escaping.

### Auto-Escaping in Blade

```blade
{{-- ✅ Safe (auto-escaped) --}}
<h1>Hello {{ $userName }}</h1>

{{-- ⚠️ Unsafe (raw output) - only use for trusted data --}}
<div>{!! $trustedHtml !!}</div>
```

### Manual Escaping

```php
// ✅ SAFE
echo Helper::e($userInput);
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ DANGEROUS
echo $userInput;
```

### Content Security Policy (CSP)

```php
// bootstrap/app.php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");
```

---

## 💉 SQL Injection Prevention

Framework menggunakan **PDO Prepared Statements** di semua database operations.

### ✅ Safe Examples

```php
// Query Builder (automatic binding)
User::where('email', '=', $email)->first();
User::whereIn('id', [1, 2, 3])->get();

// Raw query with bindings
$db->query("SELECT * FROM users WHERE email = :email");
$db->bind(':email', $email);
$db->execute();
```

### ❌ Dangerous (Never Do This!)

```php
// VULNERABLE TO SQL INJECTION
$db->query("SELECT * FROM users WHERE email = '$email'");
Model::whereRaw("id = $userId"); // If $userId not validated
```

### Order By Column Validation

```php
// ❌ VULNERABLE
$column = $_GET['sort'] ?? 'id';
User::orderBy($column, 'DESC'); // Attacker can inject: "id; DROP TABLE users--"

// ✅ SAFE (whitelist)
$allowedColumns = ['id', 'name', 'created_at'];
$column = in_array($_GET['sort'], $allowedColumns) ? $_GET['sort'] : 'id';
User::orderBy($column, 'DESC');
```

---

## 🛡️ Web Application Firewall (WAF)

**NEW in v5.0.0:** Enhanced WAF dengan detection untuk semua attack vectors.

### Protected Patterns

```php
// app/Middleware/WAFMiddleware.php
$patterns = [
    'sql_injection' => '/(\b(union\s+select|insert\s+into|delete\s+from|drop\s+table)\b)/i',
    'xss' => '/(<script|javascript:|on(load|error|click)=)/i',
    'path_traversal' => '/(\.\.\/|\.\.\\)/i',
    'command_injection' => '/(\b(exec|system|shell_exec)\s*\(|`|\$\()/i' // Enhanced v5.0.0
];
```

### How It Works

```
1. WAF scans $_GET, $_POST, $_COOKIE
2. Regex match against attack patterns
3. If detected → 403 Forbidden + log
4. Clean data → proceed to application
```

### Disable for Specific Routes (Not Recommended)

```php
// routes/web.php
Router::group(['middleware' => []], function() {
    // No WAF (e.g., for file upload with special chars)
    Router::post('/upload', [UploadController::class, 'store']);
});
```

---

## 🌐 Web Command Center Security (NEW in v5.0.0)

Version 5.0.0 implements **3-layer security** for system routes.

### Security Layers

#### Layer 1: Feature Toggle

```bash
# .env
ALLOW_WEB_MIGRATION=true  # Must be explicitly enabled
```

#### Layer 2: IP Whitelist

```bash
# .env
SYSTEM_ALLOWED_IPS=127.0.0.1,182.8.66.200
```

**How it works:**

```php
// routes/system.php
$clientIp = Helper::get_client_ip();
$whitelist = explode(',', env('SYSTEM_ALLOWED_IPS'));

if (!in_array($clientIp, $whitelist)) {
    abort(403, "IP not whitelisted");
}
```

#### Layer 3: Basic Authentication (Required)

```bash
# .env
SYSTEM_AUTH_USER=admin
SYSTEM_AUTH_PASS=$2y$12$ES0eTHxRrDNkEvIW1u3sB.XV3hRn379PogFbcI0OJuMH9rD0g7jRe
```

Browser will prompt for credentials before accessing system routes. Use `php artisan setup` to generate the hashed password.

### Production Best Practices

```bash
# Production .env
APP_ENV=production
ALLOW_WEB_MIGRATION=false  # ⚠️ Disable after deployment!

# If you must enable:
SYSTEM_ALLOWED_IPS=YOUR_OFFICE_IP  # NEVER use '*'
SYSTEM_AUTH_USER=admin123
SYSTEM_AUTH_PASS=$(openssl rand -base64 32)
```

**📖 [Full Documentation](web-command-center.md)**

---

## 🔒 Secure Headers

Framework sets security headers by default:

```php
// bootstrap/app.php
header('X-Powered-By: TheFramework-v1');
header('X-Frame-Options: DENY');  // Prevent clickjacking
header('X-Content-Type-Options: nosniff');  // Prevent MIME sniffing
header('X-XSS-Protection: 1; mode=block');  // Legacy XSS filter
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');  // HSTS
```

### Customize Headers

```php
// app/Middleware/SecurityHeadersMiddleware.php
public function before() {
    header('Content-Security-Policy: default-src \'self\'; img-src * data:');
    header('X-Frame-Options: SAMEORIGIN'); // Allow same-origin iframe
}
```

---

## 🔐 Data Encryption

Framework uses **Defuse PHP Encryption** (industry standard).

### Encrypt Sensitive Data

```php
use TheFramework\Helpers\Crypter;

// Encrypt
$encrypted = Crypter::encrypt('Sensitive data');

// Store in database
User::where('id', 1)->update(['secret' => $encrypted]);

// Decrypt
$decrypted = Crypter::decrypt($user->secret);
```

### APP_KEY Importance

```bash
# .env
APP_KEY=base64:abc123...  # NEVER share this!
```

**⚠️ If `APP_KEY` changes, all encrypted data becomes unreadable!**

### Secure Key Storage

```bash
# Generate strong key
php artisan key:generate

# Backup securely
cp .env .env.backup  # Store offline
```

---

## 🔑 Password Hashing

**NEVER store plain passwords!**

### Hashing

```php
use TheFramework\Helpers\Helper;

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Store in database
User::create([
    'email' => $email,
    'password' => $hashedPassword
]);
```

### Verification

```php
// Login authentication
$user = User::where('email', $email)->first();

if ($user && password_verify($inputPassword, $user->password)) {
    // Login success
    $_SESSION['user_id'] = $user->id;
} else {
    // Failed
    echo "Invalid credentials";
}
```

### Password Requirements

```php
// Validation
$validator = new Validator([
    'password' => [
        'required',
        'min:8',
        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        // Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
    ]
]);
```

---

## 🎯 Security Best Practices

### 1. Environment Configuration

```bash
# ✅ Production
APP_ENV=production
APP_DEBUG=false  # NEVER true in production
ALLOW_WEB_MIGRATION=false

# ✅ Development
APP_ENV=local
APP_DEBUG=true
ALLOW_WEB_MIGRATION=true
```

### 2. Git Ignore

```gitignore
# .gitignore
.env
.env.backup
.env.production
vendor/
storage/logs/*.log
```

### 3. File Permissions

```bash
# Set correct permissions
chmod 755 -R .
chmod 777 -R storage/
chmod 777 -R storage/logs/
chmod 777 -R storage/framework/views/
chmod 600 .env
```

### 4. Regular Updates

```bash
composer update  # Update dependencies
composer audit   # Check for vulnerabilities
```

### 5. Security Checklist

- [ ] `.env` not in Git
- [ ] `APP_DEBUG=false` in production
- [ ] HTTPS enabled
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] File upload validation enabled
- [ ] Web Command Center disabled or IP-restricted
- [ ] Error logging enabled
- [ ] Backup strategy in place

---

## 🚨 Reporting Security Vulnerabilities

If you discover a security vulnerability:

**📧 Email:** security@the-framework.ct.ws

**DO NOT** create public GitHub issues!

**Include:**

- Description of vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We will respond within **48 hours**.

---

## 📚 Related Documentation

- [Web Command Center](web-command-center.md)
- [Deployment Guide](deployment.md)
- [Environment Configuration](environment.md)
- [Middleware](middleware.md)

---

<div align="center">

**Security adalah prioritas #1 kami!**

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
