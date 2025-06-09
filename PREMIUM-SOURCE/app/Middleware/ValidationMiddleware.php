<?php
    namespace {{NAMESPACE}}\Middleware;

    use Respect\Validation\Validator as v;
    use {{NAMESPACE}}\App\Config;

    class ValidationMiddleware implements Middleware {
        public function before() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // Validasi input untuk createUser atau updateUser
                    $name = $_POST['name'] ?? '';
                    $email = $_POST['email'] ?? '';

                    v::stringType()->length(1, 100)->assert($name);
                    v::email()->assert($email);

                    // Validasi ID dari URL jika ada
                    if (isset($_GET['id'])) {
                        v::intVal()->positive()->assert($_GET['id']);
                    }
                } catch (\Respect\Validation\Exceptions\ValidationException $e) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid input: ' . $e->getMessage()]);
                    exit;
                }
            }
        }
    }
?>