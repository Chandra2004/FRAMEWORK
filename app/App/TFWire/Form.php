<?php

namespace TheFramework\App\TFWire;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Form Object — Livewire v3 Form, But Better           ║
 * ║  Version: 1.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Encapsulate form logic (validation, filling, resetting)     ║
 * ║  into a dedicated reusable class instead of polluting the    ║
 * ║  component with dozens of form properties.                   ║
 * ║                                                              ║
 * ║  BEYOND Livewire:                                            ║
 * ║  • Built-in Model binding (fill from & save to Model)        ║
 * ║  • Dirty tracking per-form                                   ║
 * ║  • Transform/Sanitize before validation                      ║
 * ║  • Cast types automatically                                  ║
 * ╚══════════════════════════════════════════════════════════════╝
 * 
 * Usage:
 *   class UserForm extends Form {
 *       public string $name = '';
 *       public string $email = '';
 *       public string $role = 'user';
 * 
 *       protected array $rules = [
 *           'name'  => 'required|min:3',
 *           'email' => 'required|email',
 *           'role'  => 'required|in:user,admin',
 *       ];
 *   }
 * 
 *   class UserManager extends Component {
 *       public UserForm $form;
 * 
 *       public function mount() { $this->form = new UserForm(); }
 * 
 *       public function save() {
 *           $data = $this->form->validate();
 *           User::create($data);
 *           $this->form->reset();
 *           $this->flashSuccess('User saved!');
 *       }
 *   }
 */
class Form
{
    protected array $rules = [];
    protected array $messages = [];
    protected array $errors = [];
    private array $originalState = [];

    public function __construct()
    {
        $this->originalState = $this->toArray();
    }

    /**
     * Get all public properties as array
     */
    public function toArray(): array
    {
        $reflect = new \ReflectionClass($this);
        $data = [];

        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isInitialized($this)) {
                $data[$prop->getName()] = $prop->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Fill form from array data
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $this->castProperty($key, $value);
            }
        }
        return $this;
    }

    /**
     * Fill form from a Model object
     */
    public function fillFromModel(object $model): self
    {
        $properties = array_keys($this->toArray());
        foreach ($properties as $prop) {
            if (isset($model->{$prop})) {
                $this->{$prop} = $model->{$prop};
            }
        }
        $this->originalState = $this->toArray();
        return $this;
    }

    /**
     * Cast value to match property type
     */
    private function castProperty(string $key, $value)
    {
        $type = (new \ReflectionProperty($this, $key))->getType();
        if (!$type || !($type instanceof \ReflectionNamedType)) return $value;

        return match ($type->getName()) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array'  => is_array($value) ? $value : [],
            default  => $value,
        };
    }

    /**
     * Validate form data
     * @throws \TheFramework\App\Exceptions\ValidationException
     */
    public function validate(?array $rules = null, ?array $messages = null): array
    {
        $rules    = $rules ?? $this->rules;
        $messages = $messages ?? $this->messages;
        $data     = $this->toArray();
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (empty($rule)) continue;
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->applyRule($field, $value, $rule, $params);
                if ($error) {
                    $customKey = "{$field}.{$rule}";
                    $this->errors[$field][] = $messages[$customKey] ?? $messages[$field] ?? $error;
                    break;
                }
            }
        }

        if (!empty($this->errors)) {
            throw new \TheFramework\App\Exceptions\ValidationException($this->errors);
        }

        return array_intersect_key($data, $rules);
    }

    protected function applyRule(string $field, $value, string $rule, array $params): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'  => (empty($value) && $value !== '0' && $value !== 0 && $value !== false)
                            ? "{$label} wajib diisi." : null,
            'string'    => (!is_null($value) && !is_string($value))
                            ? "{$label} harus berupa teks." : null,
            'email'     => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                            ? "{$label} harus berupa email yang valid." : null,
            'min'       => (is_string($value) && strlen($value) < (int)($params[0] ?? 0))
                            ? "{$label} minimal {$params[0]} karakter." : null,
            'max'       => (is_string($value) && strlen($value) > (int)($params[0] ?? 0))
                            ? "{$label} maksimal {$params[0]} karakter." : null,
            'in'        => (!in_array($value, $params))
                            ? "{$label} harus salah satu dari: " . implode(', ', $params) . "." : null,
            'confirmed' => ($value !== ($this->{$field . '_confirmation'} ?? null))
                            ? "Konfirmasi {$label} tidak cocok." : null,
            'nullable'  => null,
            default     => null,
        };
    }

    /**
     * Reset form to initial values
     */
    public function reset(): void
    {
        foreach ($this->originalState as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->errors = [];
    }

    /**
     * Check if form has been modified
     */
    public function isDirty(): bool
    {
        return $this->toArray() !== $this->originalState;
    }

    /**
     * Get changed fields only
     */
    public function getDirty(): array
    {
        $dirty = [];
        $current = $this->toArray();
        foreach ($current as $key => $value) {
            if (($this->originalState[$key] ?? null) !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /** Get errors */
    public function getErrors(): array { return $this->errors; }
    public function hasError(string $field): bool { return isset($this->errors[$field]); }
    public function getError(string $field): ?string { return $this->errors[$field][0] ?? null; }
    public function addError(string $field, string $msg): void { $this->errors[$field][] = $msg; }
}
