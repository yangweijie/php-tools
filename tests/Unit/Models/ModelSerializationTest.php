<?php

use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Models\TableRow;

describe('Model Serialization', function () {
    it('can serialize and deserialize PortInfo', function () {
        $original = new PortInfo(
            '8080',
            '1234',
            'TCP',
            '127.0.0.1:8080',
            '192.168.1.1:80',
            'ESTABLISHED',
            'nginx',
            '/usr/bin/nginx -g daemon off;'
        );
        
        $array = $original->toArray();
        $deserialized = PortInfo::fromArray($array);
        
        expect($deserialized->getPort())->toBe($original->getPort());
        expect($deserialized->getPid())->toBe($original->getPid());
        expect($deserialized->getProtocol())->toBe($original->getProtocol());
        expect($deserialized->getLocalAddress())->toBe($original->getLocalAddress());
        expect($deserialized->getRemoteAddress())->toBe($original->getRemoteAddress());
        expect($deserialized->getState())->toBe($original->getState());
        expect($deserialized->getProcessName())->toBe($original->getProcessName());
        expect($deserialized->getCommandLine())->toBe($original->getCommandLine());
    });

    it('can serialize and deserialize ProcessInfo', function () {
        $original = new ProcessInfo(
            '1234',
            'nginx',
            'www-data',
            '2.5%',
            '1024000',
            '/usr/bin/nginx -g daemon off;',
            'running'
        );
        
        $array = $original->toArray();
        $deserialized = ProcessInfo::fromArray($array);
        
        expect($deserialized->getPid())->toBe($original->getPid());
        expect($deserialized->getName())->toBe($original->getName());
        expect($deserialized->getUser())->toBe($original->getUser());
        expect($deserialized->getCpuUsage())->toBe($original->getCpuUsage());
        expect($deserialized->getMemoryUsage())->toBe($original->getMemoryUsage());
        expect($deserialized->getCommandLine())->toBe($original->getCommandLine());
        expect($deserialized->getStatus())->toBe($original->getStatus());
    });

    it('can serialize and deserialize TableRow', function () {
        $data = ['column1' => 'value1', 'column2' => 'value2'];
        $original = new TableRow('row-1', $data, true);
        
        $array = $original->toArray();
        $deserialized = TableRow::fromArray($array);
        
        expect($deserialized->getId())->toBe($original->getId());
        expect($deserialized->getData())->toBe($original->getData());
        expect($deserialized->isSelected())->toBe($original->isSelected());
    });

    it('handles JSON serialization correctly', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        $processInfo = new ProcessInfo('5678', 'apache2');
        $tableRow = new TableRow('row-1', ['test' => 'data']);
        
        // Test JSON encoding/decoding
        $portJson = json_encode($portInfo->toArray());
        $processJson = json_encode($processInfo->toArray());
        $tableJson = json_encode($tableRow->toArray());
        
        expect($portJson)->toBeString();
        expect($processJson)->toBeString();
        expect($tableJson)->toBeString();
        
        $portDecoded = json_decode($portJson, true);
        $processDecoded = json_decode($processJson, true);
        $tableDecoded = json_decode($tableJson, true);
        
        $restoredPort = PortInfo::fromArray($portDecoded);
        $restoredProcess = ProcessInfo::fromArray($processDecoded);
        $restoredTable = TableRow::fromArray($tableDecoded);
        
        expect($restoredPort->getPort())->toBe('8080');
        expect($restoredProcess->getName())->toBe('apache2');
        expect($restoredTable->getId())->toBe('row-1');
    });

    it('validates data integrity after serialization', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        
        // Serialize and deserialize
        $array = $portInfo->toArray();
        $restored = PortInfo::fromArray($array);
        
        // Both should validate successfully
        expect($portInfo->validate())->toBeTrue();
        expect($restored->validate())->toBeTrue();
        
        // Both should have same validation state
        expect($portInfo->hasValidationErrors())->toBe($restored->hasValidationErrors());
    });
});