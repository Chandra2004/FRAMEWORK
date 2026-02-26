<?php

namespace TheFramework\App\Http;

use TheFramework\App\Exceptions\ValidationException;
use TheFramework\App\Exceptions\AuthorizationException;

/**
 * Base Form Request with Auto-Validation
 * 
 * Form requests will automatically validate themselves when instantiated.
 * If validation fails, it throws a ValidationException handled by Exception Handler.
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
            throw new AuthorizationException('This action is unauthorized.');
        }

        // Validate
        $validator = new Validator();
        $isValid = $validator->validate(
            $this->all(),
            $this->rules(),
            $this->labels()
        );

        if (!$isValid) {
            throw ValidationException::withMessages($validator->errors());
        }

        // Mark as validated
        $this->validated = true;

        // Store validated data (only the intersecting keys)
        $this->validatedData = array_intersect_key(
            $this->all(),
            $this->rules()
        );
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
