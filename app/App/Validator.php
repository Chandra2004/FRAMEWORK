<?php

namespace TheFramework\App;

use PDO;

class Validator
{
    protected array $errors = [];
    protected array $inputData = [];

    public function validate(array $data, array $rules, array $labels = []): bool
    {
        $this->inputData = $data; // Simpan untuk rule cross-field (confirmed, required_if)

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            $ruleList = explode('|', $ruleString);
            $skipFurther = false;

            foreach ($ruleList as $rule) {
                if ($skipFurther)
                    break;

                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $method = "validate_" . $rule;
                if (method_exists($this, $method)) {
                    try {
                        $this->$method($field, $label, $value, $param);
                    } catch (\Exception $e) {
                        if ($e->getMessage() === "__SKIP_VALIDATION__") {
                            $skipFurther = true;
                        }
                    }
                }
            }
        }
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    // --------------------------
    // RULE DEFINITIONS
    // --------------------------

    protected function validate_required(string $field, string $label, $value, $param = null): void
    {
        if (is_array($value) && isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            $this->addError($field, "{$label} wajib diisi.");
        } elseif (empty($value) && $value !== '0') {
            $this->addError($field, "{$label} wajib diisi.");
        }
    }

    protected function validate_nullable(string $field, string $label, $value, $param = null): void
    {
        if (!isset($value) || empty($value) || (is_array($value) && ($value['error'] ?? 0) === UPLOAD_ERR_NO_FILE)) {
            unset($this->errors[$field]);
            throw new \Exception("__SKIP_VALIDATION__"); // skip semua rule berikutnya
        }
    }


    protected function validate_string(string $field, string $label, $value, $param = null): void
    {
        if (!is_string($value)) {
            $this->addError($field, "{$label} harus berupa teks.");
        }
    }

    protected function validate_integer(string $field, string $label, $value, $param = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "{$label} harus berupa angka bulat.");
        }
    }

    protected function validate_email(string $field, string $label, $value, $param = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "{$label} tidak valid.");
        }
    }

    protected function validate_min(string $field, string $label, $value, $param): void
    {
        if (is_string($value) && strlen($value) < (int) $param) {
            $this->addError($field, "{$label} minimal {$param} karakter.");
        }
    }

    protected function validate_max(string $field, string $label, $value, $param): void
    {
        if (is_string($value) && strlen($value) > (int) $param) {
            $this->addError($field, "{$label} maksimal {$param} karakter.");
        } elseif (is_array($value) && isset($value['size']) && $value['size'] > ((int) $param * 1024)) {
            $this->addError($field, "{$label} maksimal {$param} KB.");
        }
    }

    protected function validate_file(string $field, string $label, $value, $param = null): void
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            $this->addError($field, "{$label} harus berupa file.");
        }
    }

    protected function validate_mimes(string $field, string $label, $value, $param): void
    {
        if (!is_array($value) || empty($value['name']))
            return;

        $allowed = explode(',', $param);
        $ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $this->addError($field, "{$label} hanya boleh berformat: " . implode(', ', $allowed));
        }
    }

    protected function validate_unique(string $field, string $label, $value, $param): void
    {
        if (!$value || !$param)
            return;

        [$table, $column] = explode(',', $param);

        // Gunakan Singleton Database agar lebih cepat (reuse connection)
        $db = Database::getInstance();

        // Gunakan prepared statement via wrapper Database
        $db->query("SELECT COUNT(*) as count FROM `$table` WHERE `$column` = :val");
        $db->bind(':val', $value);
        $result = $db->single();

        if ($result && $result['count'] > 0) {
            $this->addError($field, "{$label} sudah digunakan.");
        }
    }

    protected function validate_regex(string $field, string $label, $value, $param): void
    {
        if (empty($value)) {
            return;
        }

        if ($param[0] !== '/' || substr($param, -1) !== '/') {
            $param = '/' . $param . '/';
        }

        $result = @preg_match($param, $value);

        if ($result === false) {
            $this->addError($field, "Regex untuk {$label} tidak valid.");
            return;
        }

        if ($result === 0) {
            $this->addError($field, "{$label} tidak sesuai format.");
        }
    }

    protected function validate_confirmed(string $field, string $label, $value, $param = null): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->inputData[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            $this->addError($field, "Konfirmasi {$label} tidak cocok.");
        }
    }

    // --- RULES BARU ---

    protected function validate_date(string $field, string $label, $value, $param = null): void
    {
        if (empty($value))
            return;
        if (strtotime($value) === false) {
            $this->addError($field, "{$label} bukan tanggal yang valid.");
        }
    }

    protected function validate_numeric(string $field, string $label, $value, $param = null): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, "{$label} harus berupa angka.");
        }
    }

    protected function validate_boolean(string $field, string $label, $value, $param = null): void
    {
        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false'];
        if (!in_array($value, $acceptable, true)) {
            $this->addError($field, "{$label} harus berupa boolean.");
        }
    }

    protected function validate_alpha(string $field, string $label, $value, $param = null): void
    {
        if (!preg_match('/^[\pL\pM]+$/u', $value)) {
            $this->addError($field, "{$label} hanya boleh berisi huruf.");
        }
    }

    protected function validate_alpha_num(string $field, string $label, $value, $param = null): void
    {
        if (!preg_match('/^[\pL\pM\pN]+$/u', $value)) {
            $this->addError($field, "{$label} hanya boleh berisi huruf dan angka.");
        }
    }

    protected function validate_active_url(string $field, string $label, $value, $param = null): void
    {
        if (!checkdnsrr(parse_url($value, PHP_URL_HOST))) {
            $this->addError($field, "{$label} bukan URL yang aktif.");
        }
    }
}
