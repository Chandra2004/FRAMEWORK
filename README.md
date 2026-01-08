# The Framework
**A High-Performance, Secure, and Elegant PHP MVC Framework.**

Designed by **Chandra Tri Antomo**, this framework combines the elegance of Laravel-like syntax with the speed of native PHP. It features a powerful routing engine, robust security features (CSRF, WAF, XSS Protection), and a flexible Database ORM.

---

## 🚀 Features

- **MVC Architecture**: Clean separation of concerns.
- **Eloquent-like ORM**: Magic methods for Database operations (`User::all()`, `User::create()`).
- **Advanced Routing**: Support for Groups, Middleware, and Regex Constraints.
- **Security First**: Built-in CSRF Protection, XSS Filtering, SQL Injection Prevention, and Secure Session handling.
- **Artisan CLI**: Powerful command-line interface for generating code.
- **Blade-like View Engine**: Simple yet powerful templating.

---

## 🛠 Installation

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/your-repo/the-framework.git
    cd the-framework
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Setup Environment**
    Copy the example environment file:
    ```bash
    cp .env.example .env
    ```
    Then configure your Database credentials in `.env`.

4.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

5.  **Serve Application**
    ```bash
    php artisan serve
    ```
    Access your app at `http://127.0.0.1:8080`.

---

## 📚 Documentation

### 1. Database ORM (Models)
The Model system is designed to mimic Laravel's Eloquent syntax.

**Defining a Model:**
Use Artisan to create a model:
```bash
php artisan make:model Product
```
This generates `app/Models/Product.php` with built-in `$fillable` and `$hidden` protection property.

**Using Models:**

-   **Fetch All:**
    ```php
    $products = Product::all();
    ```

-   **Find by ID (or Fail):**
    ```php
    // Throws 404 Exception if not found
    $product = Product::findOrFail(1);
    ```

-   **Create New Data (Mass Assignment):**
    ```php
    // Automatically handles timestamps (created_at, updated_at)
    // Automatically filters input based on $fillable
    $product = Product::create([
        'name' => 'Laptop',
        'price' => 5000000
    ]);
    ```

-   **Update Data:**
    ```php
    $product = Product::find(1);
    $product->update([
        'price' => 5500000
    ]);
    ```

-   **Delete Data:**
    ```php
    Product::delete(1);
    ```

-   **Query Builder:**
    ```php
    $users = User::where('active', '=', 1)
                 ->orderBy('created_at', 'DESC')
                 ->limit(10)
                 ->get();
    ```

### 2. Controllers & Routing
**Creating a Controller:**
```bash
# Standard Controller
php artisan make:controller HomeController

# Resource Controller (CRUD ready)
php artisan make:controller ProductController --resource --model=Product
```

**Defining Routes (`routes/web.php`):**
```php
use TheFramework\App\Router;
use TheFramework\Http\Controllers\HomeController;

// Basic GET
Router::add('GET', '/home', HomeController::class, 'index');

// Route with Parameter
Router::add('GET', '/user/{id}', HomeController::class, 'show');

// Group with Middleware
Router::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function() {
    Router::add('GET', '/dashboard', AdminController::class, 'index');
});
```

### 3. Request & Validation
In your controller methods (especially for Resource controllers), `Request` object is injected automatically if generated via Artisan.

```php
use TheFramework\App\Request;

public function store(Request $request)
{
    // Validate inputs
    $validated = $request->validate([
        'title' => 'required|min:5',
        'email' => 'required|email'
    ]);

    // Create user securely
    User::create($validated);

    Helper::redirect('/users');
}
```

### 4. Security
-   **CSRF Protection**: Enabled by default for all POST requests. Ensure your forms have `Warning: CSRF token invalid` handling or use `Helper::csrf_field()`.
-   **XSS Protection**: Use `Helper::e($string)` when outputting user data in views.
-   **SQL Injection**: All QueryBuilder methods use PDO Parameter Binding.

---

## 🤝 Contributing
1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

---

**Made with ❤️ by Chandra Tri Antomo**
