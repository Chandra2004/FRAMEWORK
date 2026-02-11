<?php

namespace TheFramework\App;

use TheFramework\Helpers\Helper;

/**
 * Base Form Request with Auto-Validation
 * 
 * Form requests will automatically validate themselves when instantiated.
 * If validation fails, it will auto-redirect back with errors and old input.
 * 
 * Usage:
 * public function store(CreateUserRequest $request) {
 *     // No need to call validate()!
 *     // If we reach here, validation passed
 *     User::create($request->validated());
 * }
 */
abstract class FormRequest extends Request
{
    protected bool $autoValidate = true;
    protected bool $validated = false;
    protected array $validatedData = [];

    /**
     * Constructor - Auto validate on instantiation
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->autoValidate && !$this->validated) {
            $this->performValidation();
        }
    }

    /**
     * Perform validation automatically
     */
    protected function performValidation(): void
    {
        // Check authorization first
        if (!$this->authorize()) {
            $this->failedAuthorization();
        }

        // Validate
        $validator = new Validator();
        $isValid = $validator->validate(
            $this->all(),
            $this->rules(),
            $this->labels()
        );

        if (!$isValid) {
            $this->failedValidation($validator->errors());
        }

        // Mark as validated
        $this->validated = true;

        // Store validated data
        $this->validatedData = array_intersect_key(
            $this->all(),
            $this->rules()
        );
    }

    /**
     * Handle failed authorization
     */
    protected function failedAuthorization(): void
    {
        Helper::redirect('/403', 'danger', 'Unauthorized access!');
        exit;
    }

    /**
     * Handle failed validation (auto redirect back)
     */
    protected function failedValidation(array $errors): void
    {
        // Flash errors
        $_SESSION['errors'] = $errors;

        // Flash old input
        $_SESSION['old'] = $this->all();

        // Redirect back to previous page
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: $referer");
        exit;
    }

    /**
     * Get the validated data.
     * Automatically returns validated data after auto-validation.
     */
    public function validated(): array
    {
        return $this->validatedData;
    }

    /**
     * Determine if the user is authorized to make this request.
     * Override this in child classes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     * Override this in child classes.
     */
    abstract public function rules(): array;

    /**
     * Get custom labels for validation errors.
     * Override this in child classes if needed.
     */
    public function labels(): array
    {
        return [];
    }
}
