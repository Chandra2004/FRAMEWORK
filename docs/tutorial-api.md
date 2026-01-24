# 🚀 REST API Development Tutorial

**Coming Soon!**

This tutorial will cover:

1. RESTful API Principles
2. JSON Response Formatting
3. API Authentication (JWT/Bearer Token)
4. Rate Limiting
5. API Versioning
6. Error Handling
7. API Documentation (Swagger)

---

## Quick API Setup

### 1. Create API Routes

```php
// routes/api.php
Router::group(['prefix' => '/api/v1'], function() {
    Router::get('/users', [ApiController::class, 'users']);
    Router::post('/users', [ApiController::class, 'createUser']);
    Router::get('/users/{id}', [ApiController::class, 'getUser']);
    Router::put('/users/{id}', [ApiController::class, 'updateUser']);
    Router::delete('/users/{id}', [ApiController::class, 'deleteUser']);
});
```

### 2. API Controller

```php
<?php

namespace TheFramework\Http\Controllers;

use TheFramework\Models\User;

class ApiController
{
    public function users()
    {
        $users = User::all();

        return $this->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $user
        ]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

### 3. Test API

```bash
# GET users
curl http://localhost:8080/api/v1/users

# GET single user
curl http://localhost:8080/api/v1/users/1

# POST create user
curl -X POST http://localhost:8080/api/v1/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com"}'
```

---

📖 **Full API tutorial dengan JWT auth, rate limiting, dan API documentation coming in v5.1.0!**

📧 Request features: support@the-framework.ct.ws

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
