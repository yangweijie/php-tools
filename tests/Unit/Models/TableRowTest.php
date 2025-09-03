<?php

use App\Ardillo\Models\TableRow;

describe('TableRow Model', function () {
    it('can be created with required parameters', function () {
        $data = ['column1' => 'value1', 'column2' => 'value2'];
        $tableRow = new TableRow('row-1', $data);
        
        expect($tableRow->getId())->toBe('row-1');
        expect($tableRow->getData())->toBe($data);
        expect($tableRow->isSelected())->toBeFalse();
    });

    it('can be created with selection state', function () {
        $data = ['column1' => 'value1'];
        $tableRow = new TableRow('row-1', $data, true);
        
        expect($tableRow->getId())->toBe('row-1');
        expect($tableRow->isSelected())->toBeTrue();
    });

    it('can change selection state', function () {
        $data = ['column1' => 'value1'];
        $tableRow = new TableRow('row-1', $data, false);
        
        expect($tableRow->isSelected())->toBeFalse();
        
        $tableRow->setSelected(true);
        expect($tableRow->isSelected())->toBeTrue();
        
        $tableRow->setSelected(false);
        expect($tableRow->isSelected())->toBeFalse();
    });

    it('can update row data', function () {
        $initialData = ['column1' => 'value1'];
        $newData = ['column1' => 'updated', 'column2' => 'new'];
        
        $tableRow = new TableRow('row-1', $initialData);
        expect($tableRow->getData())->toBe($initialData);
        
        $tableRow->setData($newData);
        expect($tableRow->getData())->toBe($newData);
    });

    it('validates required fields correctly', function () {
        $data = ['column1' => 'value1'];
        $tableRow = new TableRow('row-1', $data);
        
        expect($tableRow->validate())->toBeTrue();
    });

    it('fails validation with missing required fields', function () {
        // Create with empty ID
        $tableRow = new TableRow('', []);
        expect($tableRow->validate())->toBeFalse();
        expect($tableRow->hasValidationErrors())->toBeTrue();
    });

    it('can be converted to array', function () {
        $data = ['column1' => 'value1', 'column2' => 'value2'];
        $tableRow = new TableRow('row-1', $data, true);
        
        $array = $tableRow->toArray();
        
        expect($array)->toBeArray();
        expect($array['id'])->toBe('row-1');
        expect($array['selected'])->toBeTrue();
        expect($array['data'])->toBe($data);
    });

    it('can be created from array', function () {
        $arrayData = [
            'id' => 'row-2',
            'selected' => true,
            'data' => ['column1' => 'value1', 'column2' => 'value2']
        ];
        
        $tableRow = TableRow::fromArray($arrayData);
        
        expect($tableRow->getId())->toBe('row-2');
        expect($tableRow->isSelected())->toBeTrue();
        expect($tableRow->getData())->toBe($arrayData['data']);
    });

    it('handles magic methods correctly', function () {
        $data = ['column1' => 'value1'];
        $tableRow = new TableRow('row-1', $data);
        
        // Test __get
        expect($tableRow->id)->toBe('row-1');
        expect($tableRow->selected)->toBeFalse();
        expect($tableRow->data)->toBe($data);
        
        // Test __set
        $tableRow->customField = 'test';
        expect($tableRow->customField)->toBe('test');
        
        // Test __isset
        expect(isset($tableRow->id))->toBeTrue();
        expect(isset($tableRow->nonExistentField))->toBeFalse();
    });

    it('validates boolean selection field correctly', function () {
        $data = ['column1' => 'value1'];
        $tableRow = new TableRow('row-1', $data, true);
        expect($tableRow->validate())->toBeTrue();
        
        $tableRow = new TableRow('row-1', $data, false);
        expect($tableRow->validate())->toBeTrue();
    });
});