<?php

namespace App\Ardillo\Managers;

/**
 * Base abstract class for manager components
 */
abstract class BaseManager implements ManagerInterface
{
    protected array $tableColumns = [];
    protected string $displayName = '';

    /**
     * Query for data based on input criteria
     */
    abstract public function query(string $input): array;

    /**
     * Kill selected items by their IDs
     */
    abstract public function killSelected(array $selectedIds): array;

    /**
     * Get the table column definitions for this manager
     */
    public function getTableColumns(): array
    {
        return $this->tableColumns;
    }

    /**
     * Validate input before processing
     */
    public function validateInput(string $input): bool
    {
        return !empty(trim($input));
    }

    /**
     * Get the display name for this manager
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Format raw command output into structured data
     */
    abstract protected function formatData(string $rawOutput): array;

    /**
     * Execute system command and return output
     */
    abstract protected function executeCommand(string $command): string;
}