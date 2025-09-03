<?php

namespace App\Ardillo\Components;

use App\Ardillo\Models\TableRow;

/**
 * Interface for table components with checkbox selection support
 */
interface TableInterface extends ComponentInterface
{
    /**
     * Set the column definitions for the table
     */
    public function setColumns(array $columns): void;

    /**
     * Set the data to display in the table
     */
    public function setData(array $data): void;

    /**
     * Get the currently selected rows
     */
    public function getSelectedRows(): array;

    /**
     * Select all rows in the table
     */
    public function selectAll(): void;

    /**
     * Clear all selections
     */
    public function clearSelection(): void;

    /**
     * Refresh the table display
     */
    public function refresh(): void;

    /**
     * Add a single row to the table
     */
    public function addRow(array|TableRow $rowData): void;

    /**
     * Clear all rows from the table
     */
    public function clearTable(): void;
}