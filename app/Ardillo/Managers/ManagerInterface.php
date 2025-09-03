<?php

namespace App\Ardillo\Managers;

/**
 * Base interface for manager components (Port and Process managers)
 */
interface ManagerInterface
{
    /**
     * Query for data based on input criteria
     */
    public function query(string $input): array;

    /**
     * Kill selected items by their IDs
     */
    public function killSelected(array $selectedIds): array;

    /**
     * Get the table column definitions for this manager
     */
    public function getTableColumns(): array;

    /**
     * Validate input before processing
     */
    public function validateInput(string $input): bool;

    /**
     * Get the display name for this manager
     */
    public function getDisplayName(): string;
}