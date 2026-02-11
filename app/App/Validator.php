<?php

namespace TheFramework\App;

use PDO;
use Exception;

class Validator
{
    protected array $errors = [];
    protected array $inputData = [];
    protected array $customLabels = [];

    /**
     * Validasi data berdasarkan rules.
     * 
     * @param array $data Data input (biasanya $_POST atau $_FILES)
     * @param array $rules Rule validasi (e.g. ['email' => 'required|email'])
     * @param array $labels Custom labels (opsional)
     * @return bool True jika valid, False jika ada error
     */
    public function validate(array $data, array $rules, array $labels = []): bool
    {
        $this->inputData = $data;
        $this->customLabels = $labels;
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            // Ambil value (support dot notation untuk nested array nanti jika perlu)
            $value = $this->getValue($data, $field);

            $label = $labels[$field] ?? $this->formatLabel($field);

            $ruleList = explode('|', $ruleString);
            $skipFurther = false;

            foreach ($ruleList as $ruleItem) {
                if ($skipFurther)
                    break;

                // Parse rule dan parameter (e.g. "min:5" -> rule="min", params=["5"])
                [$rule, $params] = $this->parseRule($ruleItem);

                $method = "validate_" . $rule;

                if (method_exists($this, $method)) {
                    // Cek apakah rule ini harus jalan jika value kosong (e.g. required)
                    if (!$this->shouldValidate($rule, $value) && $rule !== 'required' && $rule !== 'accepted') {
                        continue;
                    }

                    try {
                        $isValid = $this->$method($field, $label, $value, $params);
                        if ($isValid === false) {
                            // Method validasi return false = error default sudah diset di dalam method atau belum?
                            // Kita asumsikan method validasi menambahkan error sendiri.
                        }
                    } catch (Exception $e) {
                        if ($e->getMessage() === "__SKIP_VALIDATION__") {
                            $skipFurther = true;
                        }
                    }
                } else {
                    // Logic fallback atau throw exception jika rule tidak ditemukan?
                    // Untuk saat ini ignore saja biar tidak crash di production
                }
            }
        }

        return empty($this->errors);
    }

    protected function parseRule(string $ruleItem): array
    {
        if (!str_contains($ruleItem, ':')) {
            return [$ruleItem, []];
        }

        [$rule, $paramString] = explode(':', $ruleItem, 2);
        $params = explode(',', $paramString);

        return [$rule, $params];
    }

    protected function shouldValidate($rule, $value): bool
    {
        // Rule 'required' selalu dijalankan
        if ($rule === 'required' || $rule === 'required_if')
            return true;

        // Skip validasi lain jika value kosong (null, empty string, empty array)
        // Kecuali nilai '0' atau 0 atau false dianggap ada value.
        return !in_array($value, [null, '', []], true);
    }

    protected function formatLabel(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }

    protected function getValue(array $data, string $field)
    {
        return $data[$field] ?? null;
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
        // Hanya simpan 1 error per field (error pertama yang ditemui)
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /* ==================================================
       🔹 CORE VALIDATION RULES
    ================================================== */

    protected function validate_required(string $field, string $label, $value, array $params): void
    {
        $isValid = true;
        if (is_null($value)) {
            $isValid = false;
        } elseif (is_string($value) && trim($value) === '') {
            $isValid = false;
        } elseif (is_array($value) && empty($value)) {
            $isValid = false;
        } elseif (is_array($value) && isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            $isValid = false;
        }

        if (!$isValid) {
            $this->addError($field, "{$label} wajib diisi.");
            throw new Exception("__SKIP_VALIDATION__");
        }
    }

    protected function validate_accepted(string $field, string $label, $value, array $params): void
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        if (!in_array($value, $acceptable, true)) {
            $this->addError($field, "{$label} harus disetujui.");
        }
    }

    protected function validate_same(string $field, string $label, $value, array $params): void
    {
        $targetField = $params[0] ?? '';
        $targetValue = $this->getValue($this->inputData, $targetField);
        $targetLabel = $this->customLabels[$targetField] ?? $this->formatLabel($targetField);

        if ($value !== $targetValue) {
            $this->addError($field, "{$label} harus sama dengan {$targetLabel}.");
        }
    }

    /* ==================================================
       🔹 STRING & FORMAT RULES
    ================================================== */

    protected function validate_string(string $field, string $label, $value, array $params): void
    {
        if (!is_string($value)) {
            $this->addError($field, "{$label} harus berupa teks.");
        }
    }

    protected function validate_alpha(string $field, string $label, $value, array $params): void
    {
        // Gunakan ctype_alpha untuk basic A-Za-z (lebih cepat dan safe)
        if (!ctype_alpha(str_replace(' ', '', (string) $value))) {
            // Fallback regex jika ingin support spasi: '/^[a-zA-Z\s]+$/'
            $this->addError($field, "{$label} hanya boleh berisi huruf.");
        }
    }

    protected function validate_alpha_num(string $field, string $label, $value, array $params): void
    {
        if (!ctype_alnum(str_replace(' ', '', (string) $value))) {
            $this->addError($field, "{$label} hanya boleh berisi huruf dan angka.");
        }
    }

    protected function validate_alpha_dash(string $field, string $label, $value, array $params): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, "{$label} hanya boleh berisi huruf, angka, strip, dan underscore.");
        }
    }

    protected function validate_numeric(string $field, string $label, $value, array $params): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, "{$label} harus berupa angka.");
        }
    }

    protected function validate_integer(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "{$label} harus berupa bilangan buat (integer).");
        }
    }

    protected function validate_email(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "{$label} format email tidak valid.");
        }
    }

    protected function validate_url(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "{$label} format URL tidak valid.");
        }
    }

    protected function validate_ip(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, "{$label} alamat IP tidak valid.");
        }
    }

    protected function validate_json(string $field, string $label, $value, array $params): void
    {
        if (!is_string($value) || !json_validate($value)) { // PHP 8.3+ has json_validate
            // Fallback for older PHP
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError($field, "{$label} harus berupa JSON string yang valid.");
            }
        }
    }

    /* ==================================================
       🔹 SIZE & COMPARISON RULES
    ================================================== */

    protected function validate_min(string $field, string $label, $value, array $params): void
    {
        $min = (float) ($params[0] ?? 0);

        if (is_numeric($value)) {
            if ($value < $min)
                $this->addError($field, "{$label} minimal bernilai {$min}.");
        } elseif (is_string($value)) {
            if (mb_strlen($value) < $min)
                $this->addError($field, "{$label} minimal {$min} karakter.");
        } elseif (is_array($value)) {
            if (count($value) < $min)
                $this->addError($field, "{$label} minimal memiliki {$min} item.");
        }
    }

    protected function validate_max(string $field, string $label, $value, array $params): void
    {
        $max = (float) ($params[0] ?? 0);

        if (is_numeric($value)) {
            if ($value > $max)
                $this->addError($field, "{$label} maksimal bernilai {$max}.");
        } elseif (is_string($value)) {
            if (mb_strlen($value) > $max)
                $this->addError($field, "{$label} maksimal {$max} karakter.");
        } elseif (is_array($value)) {
            // Cek file upload size
            if (isset($value['size'])) {
                $maxParamsKb = $max; // Parameter max biasanya dianggap KB untuk file
                if ($value['size'] > $maxParamsKb * 1024) {
                    $this->addError($field, "{$label} maksimal berukuran {$maxParamsKb} KB.");
                }
            } else {
                if (count($value) > $max)
                    $this->addError($field, "{$label} maksimal memiliki {$max} item.");
            }
        }
    }

    protected function validate_between(string $field, string $label, $value, array $params): void
    {
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? 0);

        if (is_numeric($value)) {
            if ($value < $min || $value > $max)
                $this->addError($field, "{$label} harus bernilai antara {$min} dan {$max}.");
        } elseif (is_string($value)) {
            $len = mb_strlen($value);
            if ($len < $min || $len > $max)
                $this->addError($field, "{$label} panjang harus antara {$min} dan {$max} karakter.");
        }
    }

    protected function validate_in(string $field, string $label, $value, array $params): void
    {
        // params berisi array [val1, val2, val3]
        if (!in_array((string) $value, $params)) {
            $this->addError($field, "{$label} pilihan tidak valid.");
        }
    }

    protected function validate_not_in(string $field, string $label, $value, array $params): void
    {
        if (in_array((string) $value, $params)) {
            $this->addError($field, "{$label} pilihan tidak diizinkan.");
        }
    }

    /* ==================================================
       🔹 DATABASE RULES (Super "Wah")
    ================================================== */

    /**
     * Cek apakah value ini unique di database.
     * Usage: unique:table,column,except_id,id_column
     * Example: unique:users,email
     * Example Update: unique:users,email,1,id (ignore id=1)
     */
    protected function validate_unique(string $field, string $label, $value, array $params): void
    {
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;
        $idColumn = $params[3] ?? 'id';

        if (!$table)
            throw new Exception("Rule unique butuh nama tabel.");

        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = :val";

        if ($exceptId) {
            $sql .= " AND `$idColumn` != :except";
        }

        $db->query($sql);
        $db->bind(':val', $value);
        if ($exceptId) {
            $db->bind(':except', $exceptId);
        }

        $result = $db->single();

        if ($result && $result['count'] > 0) {
            $this->addError($field, "{$label} sudah terdaftar.");
        }
    }

    /**
     * Cek apakah value ini EXIST di database.
     * Usage: exists:table,column
     */
    protected function validate_exists(string $field, string $label, $value, array $params): void
    {
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;

        if (!$table)
            throw new Exception("Rule exists butuh nama tabel.");

        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = :val";

        $db->query($sql);
        $db->bind(':val', $value);
        $result = $db->single();

        if (!$result || $result['count'] == 0) {
            $this->addError($field, "{$label} tidak ditemukan di database.");
        }
    }

    /* ==================================================
       🔹 FILE RULES
    ================================================== */

    protected function validate_mimes(string $field, string $label, $value, array $params): void
    {
        if (!is_array($value) || empty($value['name']))
            return;

        $ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

        // params berisi ['jpg', 'png', 'pdf']
        if (!in_array($ext, $params)) {
            $this->addError($field, "{$label} harus berformat: " . implode(', ', $params));
        }
    }

    protected function validate_image(string $field, string $label, $value, array $params): void
    {
        $this->validate_mimes($field, $label, $value, ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'svg', 'webp']);
    }

    /* ==================================================
       🔹 DATE RULES
    ================================================== */

    protected function validate_date(string $field, string $label, $value, array $params): void
    {
        if (strtotime($value) === false) {
            $this->addError($field, "{$label} bukan tanggal yang valid.");
        }
    }

    protected function validate_date_format(string $field, string $label, $value, array $params): void
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        if (!($d && $d->format($format) === $value)) {
            $this->addError($field, "{$label} format harus {$format}.");
        }
    }
}
