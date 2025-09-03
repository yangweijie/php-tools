<?php

namespace App\Ardillo\Models;

/**
 * Base abstract class for data models
 */
abstract class BaseModel implements ModelInterface
{
    protected array $data = [];
    protected array $validationRules = [];

    /**
     * Validate the model data
     */
    public function validate(): bool
    {
        foreach ($this->validationRules as $field => $rules) {
            if (!$this->validateField($field, $rules)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Convert model to array representation
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Create model from array data
     */
    public static function fromArray(array $data): static
    {
        $instance = new static();
        $instance->data = $data;
        return $instance;
    }

    /**
     * Create instance without constructor validation (for fromArray)
     */
    protected static function createWithoutConstructor(): static
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Get the unique identifier for this model
     */
    abstract public function getId(): string;

    /**
     * Get a property value
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Set a property value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    /**
     * Check if a property exists
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Validate a single field against its rules
     */
    protected function validateField(string $field, array $rules): bool
    {
        $value = $this->data[$field] ?? null;

        foreach ($rules as $rule) {
            if (!$this->applyValidationRule($value, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a single validation rule
     */
    protected function applyValidationRule(mixed $value, string $rule): bool
    {
        switch ($rule) {
            case 'required':
                return !empty($value) || $value === 0 || $value === false;
            case 'numeric':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'positive_integer':
                return is_numeric($value) && (int) $value > 0;
            case 'port_range':
                return is_numeric($value) && (int) $value >= 1 && (int) $value <= 65535;
            default:
                return true;
        }
    }

    /**
     * Get validation errors for the model
     */
    public function getValidationErrors(): array
    {
        $errors = [];
        
        foreach ($this->validationRules as $field => $rules) {
            foreach ($rules as $rule) {
                if (!$this->applyValidationRule($this->data[$field] ?? null, $rule)) {
                    $errors[$field][] = "Field '{$field}' failed validation rule '{$rule}'";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Check if the model has validation errors
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->getValidationErrors());
    }
}