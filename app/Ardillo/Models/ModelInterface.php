<?php

namespace App\Ardillo\Models;

/**
 * Base interface for data models
 */
interface ModelInterface
{
    /**
     * Validate the model data
     */
    public function validate(): bool;

    /**
     * Convert model to array representation
     */
    public function toArray(): array;

    /**
     * Create model from array data
     */
    public static function fromArray(array $data): static;

    /**
     * Get the unique identifier for this model
     */
    public function getId(): string;
}