<?php

namespace TheFramework\App\Http;

use PDO;
use Exception;
use TheFramework\App\Database\Database;

class SkipValidationException extends Exception
{
}

class Validator
{
    protected array $errors = [];
    protected array $inputData = [];
    protected array $customLabels = [];
    protected array $customMessages = [];

    /**
     * Validasi data berdasarkan rules.
     * 
     * @param array $data Data input
     * @param array $rules Rule validasi (e.g. ['email' => 'required|email'])
     * @param array $labels Custom labels (opsional)
     * @return bool True jika valid, False jika ada error
     */
    public function validate(array $data, array $rules, array $labels = [], array $messages = []): bool
    {
        $this->inputData = $data;
        $this->customLabels = $labels;
        $this->customMessages = $messages;
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);

            // Handle array wildcard foo.*.bar
            if (str_contains($field, '*')) {
                $this->validateWildcard($field, $ruleList);
                continue;
            }

            $this->validateField($field, $ruleList);
        }

        return empty($this->errors);
    }

    protected function validateWildcard(string $field, array $ruleList): void
    {
        // Simple wildcard implementation: users.*.email => matches users.0.email, users.1.email
        $segments = explode('.*.', $field, 2);
        if (count($segments) !== 2)
            return;

        $arrayData = $this->getValue($this->inputData, $segments[0]);
        if (!is_array($arrayData))
            return;

        foreach ($arrayData as $i => $item) {
            $computedField = "{$segments[0]}.{$i}.{$segments[1]}";
            $this->validateField($computedField, $ruleList);
        }
    }

    protected function validateField(string $field, array $ruleList): void
    {
        $value = $this->getValue($this->inputData, $field);
        $label = $this->customLabels[$field] ?? $this->formatLabel($field);
        $skipFurther = false;

        foreach ($ruleList as $ruleItem) {
            if ($skipFurther)
                break;

            [$rule, $params] = $this->parseRule($ruleItem);

            $method = "validate_" . $rule;
            if (method_exists($this, $method)) {
                if (!$this->shouldValidate($rule, $value) && $rule !== 'required' && $rule !== 'accepted' && $rule !== 'present' && $rule !== 'required_if') {
                    continue;
                }
                try {
                    $this->$method($field, $label, $value, $params);
                } catch (SkipValidationException $e) {
                    $skipFurther = true;
                }
            }
        }
    }

    protected function parseRule(string $ruleItem): array
    {
        if (!str_contains($ruleItem, ':')) {
            return [$ruleItem, []];
        }
        [$rule, $paramString] = explode(':', $ruleItem, 2);
        return [$rule, explode(',', $paramString)];
    }

    protected function shouldValidate($rule, $value): bool
    {
        if (in_array($rule, ['required', 'required_if', 'required_unless', 'required_with', 'required_without', 'nullable', 'present', 'prohibited', 'prohibited_if']))
            return true;
        return !in_array($value, [null, '', []], true);
    }

    protected function formatLabel(string $field): string
    {
        $field = str_replace('.*.', ' ', $field);
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }

    protected function getValue(array $data, string $field)
    {
        if (isset($data[$field]))
            return $data[$field];
        foreach (explode('.', $field) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)][0] ?? null;
    }

    protected function addError(string $field, string $rule, string $defaultMessage): void
    {
        $messageKey = "{$field}.{$rule}";
        $message = $this->customMessages[$messageKey] ?? $this->customMessages[$rule] ?? $defaultMessage;

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
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
            $this->addError($field, 'required', "{$label} is required.");
            throw new SkipValidationException();
        }
    }

    protected function validate_accepted(string $field, string $label, $value, array $params): void
    {
        if (!in_array($value, ['yes', 'on', '1', 1, true, 'true'], true)) {
            $this->addError($field, 'accepted', "{$label} must be accepted.");
        }
    }

    protected function validate_confirmed(string $field, string $label, $value, array $params): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->getValue($this->inputData, $confirmField);
        if ($value !== $confirmValue) {
            $this->addError($field, 'confirmed', "{$label} confirmation does not match.");
        }
    }

    protected function validate_same(string $field, string $label, $value, array $params): void
    {
        $targetField = $params[0] ?? '';
        $targetValue = $this->getValue($this->inputData, $targetField);
        if ($value !== $targetValue) {
            $this->addError($field, 'same', "{$label} must match {$targetField}.");
        }
    }

    protected function validate_different(string $field, string $label, $value, array $params): void
    {
        $targetField = $params[0] ?? '';
        $targetValue = $this->getValue($this->inputData, $targetField);
        if ($value === $targetValue) {
            $this->addError($field, 'different', "{$label} and {$targetField} must be different.");
        }
    }

    /* ==================================================
       🔹 DATA TYPES
    ================================================== */

    protected function validate_string(string $field, string $label, $value, array $params): void
    {
        if (!is_string($value)) {
            $this->addError($field, 'string', "{$label} must be a string.");
        }
    }

    protected function validate_numeric(string $field, string $label, $value, array $params): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, 'numeric', "{$label} must be a number.");
        }
    }

    protected function validate_integer(string $field, string $label, $value, array $params): void
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, 'integer', "{$label} must be an integer.");
        }
    }

    protected function validate_boolean(string $field, string $label, $value, array $params): void
    {
        $acceptable = [true, false, 0, 1, '0', '1'];
        if (!in_array($value, $acceptable, true)) {
            $this->addError($field, 'boolean', "{$label} must be true or false.");
        }
    }

    protected function validate_array(string $field, string $label, $value, array $params): void
    {
        if (!is_array($value)) {
            $this->addError($field, 'array', "{$label} must be an array.");
        }
    }

    protected function validate_json(string $field, string $label, $value, array $params): void
    {
        if (!is_string($value)) {
            $this->addError($field, 'json', "{$label} must be a valid JSON string.");
            return;
        }
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, 'json', "{$label} must be a valid JSON string.");
        }
    }

    /* ==================================================
       🔹 STRING FORMATS
    ================================================== */

    protected function validate_alpha(string $field, string $label, $value, array $params): void
    {
        if (!ctype_alpha(str_replace(' ', '', (string) $value))) {
            $this->addError($field, 'alpha', "{$label} may only contain letters.");
        }
    }

    protected function validate_alpha_num(string $field, string $label, $value, array $params): void
    {
        if (!ctype_alnum(str_replace(' ', '', (string) $value))) {
            $this->addError($field, 'alpha_num', "{$label} may only contain letters and numbers.");
        }
    }

    protected function validate_alpha_dash(string $field, string $label, $value, array $params): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, 'alpha_dash', "{$label} may only contain letters, numbers, dashes and underscores.");
        }
    }

    protected function validate_email(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', "{$label} must be a valid email address.");
        }
    }

    protected function validate_url(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'url', "{$label} must be a valid URL.");
        }
    }

    protected function validate_active_url(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL) || !checkdnsrr(parse_url($value, PHP_URL_HOST), 'ANY')) {
            $this->addError($field, 'active_url', "{$label} is not a valid, active URL.");
        }
    }

    protected function validate_ip(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, 'ip', "{$label} must be a valid IP address.");
        }
    }

    protected function validate_mac_address(string $field, string $label, $value, array $params): void
    {
        if (!filter_var($value, FILTER_VALIDATE_MAC)) {
            $this->addError($field, 'mac_address', "{$label} must be a valid MAC address.");
        }
    }

    protected function validate_uuid(string $field, string $label, $value, array $params): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $this->addError($field, 'uuid', "{$label} must be a valid UUID.");
        }
    }

    protected function validate_regex(string $field, string $label, $value, array $params): void
    {
        $pattern = $params[0] ?? '';
        if (!preg_match($pattern, $value)) {
            $this->addError($field, 'regex', "{$label} format is invalid.");
        }
    }

    /* ==================================================
       🔹 SIZE & LENGTH
    ================================================== */

    protected function getSize($value)
    {
        if (is_numeric($value) && !is_string($value))
            return $value;
        if (is_string($value))
            return mb_strlen($value);
        if ($this->is_file_input($value)) {
            // Handle single or multiple files
            if (isset($value['size']) && !is_array($value['size'])) {
                return $value['size'];
            }
            // For multiple files, sum the sizes
            $sum = 0;
            foreach ($value as $file) {
                $sum += $file['size'] ?? 0;
            }
            return $sum;
        }
        if (is_array($value))
            return count($value);
        return 0;
    }

    /**
     * Parses size strings like "2MB", "500KB", "1GB" into bytes.
     * Defaults to KB (Kilobytes) if no unit is provided, matching Laravel.
     */
    protected function parseSizeToBytes(string $sizeParam): float
    {
        $sizeParam = strtoupper(trim($sizeParam));
        $value = (float) preg_replace('/[^0-9.]/', '', $sizeParam);

        if (str_ends_with($sizeParam, 'GB') || str_ends_with($sizeParam, 'G')) {
            return $value * 1024 * 1024 * 1024;
        } elseif (str_ends_with($sizeParam, 'MB') || str_ends_with($sizeParam, 'M')) {
            return $value * 1024 * 1024;
        } elseif (str_ends_with($sizeParam, 'KB') || str_ends_with($sizeParam, 'K')) {
            return $value * 1024;
        } else {
            // Default assumes KB
            return $value * 1024;
        }
    }

    /**
     * Converts a raw byte amount into a smart human-readable string (KB, MB, GB).
     */
    protected function formatBytesToSmartString(float $bytes): string
    {
        if ($bytes >= 1073741824) {
            $val = $bytes / 1073741824;
            return (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' GB';
        } elseif ($bytes >= 1048576) {
            $val = $bytes / 1048576;
            return (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' MB';
        } else {
            $val = max(0, $bytes / 1024);
            return (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' KB';
        }
    }

    protected function validate_min(string $field, string $label, $value, array $params): void
    {
        $paramRaw = $params[0] ?? '0';
        $size = $this->getSize($value);

        if (is_array($value) && isset($value['size'])) {
            $minBytes = $this->parseSizeToBytes($paramRaw);
            if ($size < $minBytes) {
                $smartSize = $this->formatBytesToSmartString($minBytes);
                $this->addError($field, 'min.file', "{$label} must be at least {$smartSize}.");
            }
        } else {
            $min = (float) $paramRaw;
            if ($size < $min) {
                $this->addError($field, 'min', "{$label} must be at least {$min}.");
            }
        }
    }

    protected function validate_max(string $field, string $label, $value, array $params): void
    {
        $paramRaw = $params[0] ?? '0';
        $size = $this->getSize($value);

        if (is_array($value) && isset($value['size'])) {
            $maxBytes = $this->parseSizeToBytes($paramRaw);
            if ($size > $maxBytes) {
                $smartSize = $this->formatBytesToSmartString($maxBytes);
                $this->addError($field, 'max.file', "{$label} may not be greater than {$smartSize}.");
            }
        } else {
            $max = (float) $paramRaw;
            if ($size > $max) {
                $this->addError($field, 'max', "{$label} may not be greater than {$max}.");
            }
        }
    }

    protected function validate_between(string $field, string $label, $value, array $params): void
    {
        $minRaw = $params[0] ?? '0';
        $maxRaw = $params[1] ?? '0';
        $size = $this->getSize($value);

        if (is_array($value) && isset($value['size'])) {
            $minBytes = $this->parseSizeToBytes($minRaw);
            $maxBytes = $this->parseSizeToBytes($maxRaw);
            if ($size < $minBytes || $size > $maxBytes) {
                $smartMin = $this->formatBytesToSmartString($minBytes);
                $smartMax = $this->formatBytesToSmartString($maxBytes);
                $this->addError($field, 'between.file', "{$label} must be between {$smartMin} and {$smartMax}.");
            }
        } else {
            $min = (float) $minRaw;
            $max = (float) $maxRaw;
            if ($size < $min || $size > $max) {
                $this->addError($field, 'between', "{$label} must be between {$min} and {$max}.");
            }
        }
    }

    protected function validate_size(string $field, string $label, $value, array $params): void
    {
        $sizeRaw = $params[0] ?? '0';
        $size = $this->getSize($value);

        if (is_array($value) && isset($value['size'])) {
            $exactBytes = $this->parseSizeToBytes($sizeRaw);
            if ($size !== $exactBytes) {
                $smartExact = $this->formatBytesToSmartString($exactBytes);
                $this->addError($field, 'size.file', "{$label} must be exactly {$smartExact}.");
            }
        } else {
            $sizeReq = (float) $sizeRaw;
            if ($size !== $sizeReq) {
                $this->addError($field, 'size', "{$label} must be exactly {$sizeReq}.");
            }
        }
    }

    protected function validate_digits(string $field, string $label, $value, array $params): void
    {
        $digits = (int) ($params[0] ?? 0);
        if (strlen((string) $value) !== $digits || !is_numeric($value)) {
            $this->addError($field, 'digits', "{$label} must be {$digits} digits.");
        }
    }

    protected function validate_digits_between(string $field, string $label, $value, array $params): void
    {
        $min = (int) ($params[0] ?? 0);
        $max = (int) ($params[1] ?? 0);
        $len = strlen((string) $value);
        if ($len < $min || $len > $max || !is_numeric($value)) {
            $this->addError($field, 'digits_between', "{$label} must be between {$min} and {$max} digits.");
        }
    }

    /* ==================================================
       🔹 SELECTION
    ================================================== */

    protected function validate_in(string $field, string $label, $value, array $params): void
    {
        if (!in_array((string) $value, $params)) {
            $this->addError($field, 'in', "The selected {$label} is invalid.");
        }
    }

    protected function validate_not_in(string $field, string $label, $value, array $params): void
    {
        if (in_array((string) $value, $params)) {
            $this->addError($field, 'not_in', "The selected {$label} is invalid.");
        }
    }

    /* ==================================================
       🔹 DATES
    ================================================== */

    protected function validate_date(string $field, string $label, $value, array $params): void
    {
        if (strtotime($value) === false) {
            $this->addError($field, 'date', "{$label} is not a valid date.");
        }
    }

    protected function validate_date_format(string $field, string $label, $value, array $params): void
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        if (!($d && $d->format($format) === $value)) {
            $this->addError($field, 'date_format', "{$label} does not match the format {$format}.");
        }
    }

    protected function validate_after(string $field, string $label, $value, array $params): void
    {
        $dateStr = $params[0] ?? 'today';
        if (strtotime($value) <= strtotime($dateStr)) {
            $this->addError($field, 'after', "{$label} must be a date after {$dateStr}.");
        }
    }

    protected function validate_before(string $field, string $label, $value, array $params): void
    {
        $dateStr = $params[0] ?? 'today';
        if (strtotime($value) >= strtotime($dateStr)) {
            $this->addError($field, 'before', "{$label} must be a date before {$dateStr}.");
        }
    }

    /* ==================================================
       🔹 DATABASE RULES
    ================================================== */

    protected function validate_unique(string $field, string $label, $value, array $params): void
    {
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;
        $idColumn = $params[3] ?? 'id';

        if (!$table)
            throw new Exception("Rule unique must specify a table.");

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $table) || !preg_match('/^[a-zA-Z0-9_.]+$/', $column) || !preg_match('/^[a-zA-Z0-9_.]+$/', $idColumn)) {
            throw new Exception("Invalid table or column name in unique rule.");
        }

        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = :val";

        if ($exceptId && $exceptId !== 'NULL') {
            $sql .= " AND `$idColumn` != :except";
        }

        $db->query($sql);
        $db->bind(':val', $value);
        if ($exceptId && $exceptId !== 'NULL') {
            $db->bind(':except', $exceptId);
        }

        $result = $db->single();
        if ($result && $result['count'] > 0) {
            $this->addError($field, 'unique', "{$label} has already been taken.");
        }
    }

    protected function validate_exists(string $field, string $label, $value, array $params): void
    {
        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;

        if (!$table)
            throw new Exception("Rule exists must specify a table.");

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $table) || !preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
            throw new Exception("Invalid table or column name in exists rule.");
        }

        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) as count FROM `$table` WHERE `$column` = :val");
        $db->bind(':val', $value);
        $result = $db->single();

        if (!$result || $result['count'] == 0) {
            $this->addError($field, 'exists', "The selected {$label} is invalid.");
        }
    }

    /* ==================================================
       🔹 FILE RULES
    ================================================== */

    protected function validate_mimes(string $field, string $label, $value, array $params): void
    {
        if (!$this->is_file_input($value))
            return;

        // Handle single or multiple files
        $files = isset($value['name']) && !is_array($value['name']) ? [$value] : $value;

        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK)
                continue;

            if (!$this->checkExtension($file['name'], $params)) {
                $this->addError($field, 'mimes', "{$label} must be a file of type: " . implode(', ', $params) . ".");
                break;
            }
        }
    }

    protected function validate_image(string $field, string $label, $value, array $params): void
    {
        $imageMimes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $this->validate_mimes($field, $label, $value, $imageMimes);
    }

    private function checkExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
    }

    private function is_file_input($value): bool
    {
        return is_array($value) && (isset($value['tmp_name']) || (isset($value[0]['tmp_name'])));
    }

    /* ==================================================
       🔹 ENTERPRISE VALIDATION RULES (v5.1)
    ================================================== */

    /**
     * nullable — Jika value kosong/null, skip seluruh validasi untuk field ini.
     * 
     * @example 'bio' => 'nullable|string|max:500'
     */
    protected function validate_nullable(string $field, string $label, $value, array $params): void
    {
        if (is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
            throw new SkipValidationException();
        }
    }

    /**
     * required_if:field,value — Wajib jika field lain bernilai tertentu.
     * 
     * @example 'company' => 'required_if:type,business'
     */
    protected function validate_required_if(string $field, string $label, $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $expectedValues = array_slice($params, 1);
        $otherValue = $this->getValue($this->inputData, $otherField);

        if (in_array((string) $otherValue, $expectedValues, true)) {
            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                $this->addError($field, 'required_if', "{$label} is required when {$otherField} is " . implode('/', $expectedValues) . ".");
                throw new SkipValidationException();
            }
        }
    }

    /**
     * required_unless:field,value — Wajib KECUALI field lain bernilai tertentu.
     */
    protected function validate_required_unless(string $field, string $label, $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $exceptValues = array_slice($params, 1);
        $otherValue = $this->getValue($this->inputData, $otherField);

        if (!in_array((string) $otherValue, $exceptValues, true)) {
            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                $this->addError($field, 'required_unless', "{$label} is required unless {$otherField} is " . implode('/', $exceptValues) . ".");
                throw new SkipValidationException();
            }
        }
    }

    /**
     * required_with:field1,field2 — Wajib jika SALAH SATU field lain ada.
     */
    protected function validate_required_with(string $field, string $label, $value, array $params): void
    {
        $anyPresent = false;
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($this->inputData, $otherField);
            if (!is_null($otherValue) && !(is_string($otherValue) && trim($otherValue) === '')) {
                $anyPresent = true;
                break;
            }
        }

        if ($anyPresent && (is_null($value) || (is_string($value) && trim($value) === ''))) {
            $this->addError($field, 'required_with', "{$label} is required when " . implode(' / ', $params) . " is present.");
            throw new SkipValidationException();
        }
    }

    /**
     * required_without:field1,field2 — Wajib jika SALAH SATU field lain TIDAK ada.
     */
    protected function validate_required_without(string $field, string $label, $value, array $params): void
    {
        $anyMissing = false;
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($this->inputData, $otherField);
            if (is_null($otherValue) || (is_string($otherValue) && trim($otherValue) === '')) {
                $anyMissing = true;
                break;
            }
        }

        if ($anyMissing && (is_null($value) || (is_string($value) && trim($value) === ''))) {
            $this->addError($field, 'required_without', "{$label} is required when " . implode(' / ', $params) . " is not present.");
            throw new SkipValidationException();
        }
    }

    /**
     * present — Field HARUS ada di data (boleh kosong).
     */
    protected function validate_present(string $field, string $label, $value, array $params): void
    {
        $exists = false;
        $data = $this->inputData;
        foreach (explode('.', $field) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
                $exists = true;
            } else {
                $exists = false;
                break;
            }
        }

        if (!$exists && !array_key_exists($field, $this->inputData)) {
            $this->addError($field, 'present', "{$label} must be present.");
        }
    }

    /**
     * prohibited — Field TIDAK BOLEH ada atau harus kosong.
     */
    protected function validate_prohibited(string $field, string $label, $value, array $params): void
    {
        if (!is_null($value) && !(is_string($value) && trim($value) === '')) {
            $this->addError($field, 'prohibited', "{$label} is prohibited.");
        }
    }

    /**
     * prohibited_if:field,value — Prohibited if another field has value.
     */
    protected function validate_prohibited_if(string $field, string $label, $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $expectedValues = array_slice($params, 1);
        $otherValue = $this->getValue($this->inputData, $otherField);

        if (in_array((string) $otherValue, $expectedValues, true)) {
            if (!is_null($value) && !(is_string($value) && trim($value) === '')) {
                $this->addError($field, 'prohibited_if', "{$label} is prohibited when {$otherField} is " . implode('/', $expectedValues) . ".");
            }
        }
    }



    /**
     * in_array:field — Value harus ada dalam array dari field lain.
     */
    protected function validate_in_array(string $field, string $label, $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->getValue($this->inputData, $otherField);

        if (!is_array($otherValue) || !in_array($value, $otherValue)) {
            $this->addError($field, 'in_array', "{$label} must exist in {$otherField}.");
        }
    }

    /**
     * distinct — Untuk array items, setiap value harus unik.
     */
    protected function validate_distinct(string $field, string $label, $value, array $params): void
    {
        // Find parent array and check for duplicates
        $segments = explode('.', $field);
        $lastSegment = array_pop($segments);
        $parentPath = implode('.', $segments);

        // Get sibling values
        $parent = $this->getValue($this->inputData, $parentPath);

        if (is_array($parent)) {
            $values = array_column($parent, $lastSegment);
            $filtered = array_filter($values, fn($v) => $v === $value);

            if (count($filtered) > 1) {
                $this->addError($field, 'distinct', "{$label} must be distinct.");
            }
        }
    }

    /**
     * password — Password strength validation.
     * 
     * @example 'password' => 'required|password:8,mixed,numbers,symbols'
     * - min length (default 8)
     * - mixed: uppercase + lowercase
     * - numbers: min 1 digit
     * - symbols: min 1 special char
     * - uncompromised: basic check against common passwords
     */
    protected function validate_password(string $field, string $label, $value, array $params): void
    {
        if (!is_string($value)) return;

        $minLength = intval($params[0] ?? 8);

        if (strlen($value) < $minLength) {
            $this->addError($field, 'password', "{$label} must be at least {$minLength} characters.");
            return;
        }

        $flags = array_slice($params, 1);

        if (in_array('mixed', $flags)) {
            if (!preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value)) {
                $this->addError($field, 'password', "{$label} must contain both uppercase and lowercase letters.");
            }
        }

        if (in_array('numbers', $flags)) {
            if (!preg_match('/[0-9]/', $value)) {
                $this->addError($field, 'password', "{$label} must contain at least one number.");
            }
        }

        if (in_array('symbols', $flags)) {
            if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
                $this->addError($field, 'password', "{$label} must contain at least one symbol.");
            }
        }

        if (in_array('uncompromised', $flags)) {
            $common = ['password', '12345678', 'qwerty123', 'admin123', 'letmein', 'welcome', 'monkey', 'dragon', 'master'];
            if (in_array(strtolower($value), $common)) {
                $this->addError($field, 'password', "{$label} is too common. Choose a stronger password.");
            }
        }
    }
}
