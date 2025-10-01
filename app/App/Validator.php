<?php

namespace TheFramework\App;

class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $labels = [];
    protected array $messages = [];
    protected array $errors = [];

    /**
     * Jalankan validasi
     */
    public function validate(array $data, array $rules, array $labels = [], array $messages = []): bool
    {
        $this->data     = $data;
        $this->rules    = $rules;
        $this->labels   = $labels;
        $this->messages = $messages;
        $this->errors   = [];

        foreach ($rules as $field => $ruleSet) {
            $value    = $data[$field] ?? null;
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);

            // Label default = ucfirst(field), override jika ada di $labels
            $label = $labels[$field] ?? ucfirst($field);

            foreach ($ruleList as $rule) {
                $param = null;

                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $method = "validate_" . $rule;

                if (method_exists($this, $method)) {
                    $this->$method($field, $label, $value, $param);
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Ambil error pertama
     */
    public function firstError(): ?string
    {
        if (!empty($this->errors)) {
            return array_values($this->errors)[0][0];
        }
        return null;
    }

    /**
     * Ambil semua error
     */
    public function allErrors(): array
    {
        return $this->errors;
    }

    /**
     * Tambah error ke array
     */
    protected function addError(string $field, string $messageKey, string $default): void
    {
        // Gunakan custom message jika tersedia
        $message = $this->messages[$field][$messageKey]
            ?? $this->messages[$messageKey]
            ?? $default;

        $this->errors[$field][] = $message;
    }

    /* ===========================
       RULES IMPLEMENTATION
       =========================== */

    protected function validate_required(string $field, string $label, $value, $param = null): void
    {
        if (is_null($value) || $value === '') {
            $this->addError($field, 'required', "{$label} wajib diisi.");
        }
    }

    protected function validate_email(string $field, string $label, $value, $param = null): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', "{$label} harus berupa email yang valid.");
        }
    }

    protected function validate_min(string $field, string $label, $value, $param): void
    {
        if (!empty($value) && strlen((string)$value) < (int)$param) {
            $this->addError($field, 'min', "{$label} minimal {$param} karakter.");
        }
    }

    protected function validate_max(string $field, string $label, $value, $param): void
    {
        if (!empty($value) && strlen((string)$value) > (int)$param) {
            $this->addError($field, 'max', "{$label} maksimal {$param} karakter.");
        }
    }

    protected function validate_numeric(string $field, string $label, $value, $param = null): void
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, 'numeric', "{$label} harus berupa angka.");
        }
    }

    protected function validate_alpha(string $field, string $label, $value, $param = null): void
    {
        if (!empty($value) && !ctype_alpha($value)) {
            $this->addError($field, 'alpha', "{$label} hanya boleh berisi huruf.");
        }
    }

    protected function validate_alphanum(string $field, string $label, $value, $param = null): void
    {
        if (!empty($value) && !ctype_alnum($value)) {
            $this->addError($field, 'alphanum', "{$label} hanya boleh berisi huruf dan angka.");
        }
    }

    protected function validate_same(string $field, string $label, $value, $param): void
    {
        if (($this->data[$param] ?? null) !== $value) {
            $otherLabel = $this->labels[$param] ?? ucfirst($param);
            $this->addError($field, 'same', "{$label} harus sama dengan {$otherLabel}.");
        }
    }

    protected function validate_in(string $field, string $label, $value, $param): void
    {
        $allowed = explode(',', $param);
        if (!in_array($value, $allowed)) {
            $this->addError($field, 'in', "{$label} tidak valid.");
        }
    }

    protected function validate_not_in(string $field, string $label, $value, $param): void
    {
        $disallowed = explode(',', $param);
        if (in_array($value, $disallowed)) {
            $this->addError($field, 'not_in', "{$label} tidak boleh dipilih.");
        }
    }

    protected function validate_regex(string $field, string $label, $value, $param): void
    {
        if (empty($value)) {
            return;
        }
    
        // Bungkus otomatis jika tidak pakai delimiter
        if ($param[0] !== '/' || substr($param, -1) !== '/') {
            $param = '/' . $param . '/';
        }
    
        // Validasi regex dengan try-catch dan preg_match langsung ke value
        try {
            $result = @preg_match($param, $value);
    
            if ($result === false) {
                throw new \RuntimeException("Invalid regex pattern");
            }
    
            if ($result === 0) {
                $this->addError($field, 'regex', "{$label} tidak sesuai format.");
            }
    
        } catch (\Throwable $e) {
            $this->addError($field, 'regex', "Regex untuk {$label} tidak valid.");
        }
    }    
       

    protected function validate_date(string $field, string $label, $value, $param = null): void
    {
        if (!empty($value) && !strtotime($value)) {
            $this->addError($field, 'date', "{$label} harus berupa tanggal yang valid.");
        }
    }
}
