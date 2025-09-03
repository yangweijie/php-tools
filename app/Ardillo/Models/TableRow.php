<?php

namespace App\Ardillo\Models;

/**
 * Model for table row data with selection state
 */
class TableRow extends BaseModel
{
    protected array $validationRules = [
        'id' => ['required', 'string'],
        'selected' => ['boolean'],
        'data' => ['required'],
    ];

    public function __construct(string $id, array $data, bool $selected = false)
    {
        $this->data = [
            'id' => $id,
            'selected' => $selected,
            'data' => $data,
        ];
    }

    /**
     * Get the unique identifier for this model
     */
    public function getId(): string
    {
        return $this->data['id'];
    }

    /**
     * Check if this row is selected
     */
    public function isSelected(): bool
    {
        return (bool) $this->data['selected'];
    }

    /**
     * Set the selection state
     */
    public function setSelected(bool $selected): void
    {
        $this->data['selected'] = $selected;
    }

    /**
     * Get the row data
     */
    public function getData(): array
    {
        return $this->data['data'];
    }

    /**
     * Set the row data
     */
    public function setData(array $data): void
    {
        $this->data['data'] = $data;
    }

    /**
     * Create model from array data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            $data['id'] ?? '',
            $data['data'] ?? [],
            $data['selected'] ?? false
        );
    }
}