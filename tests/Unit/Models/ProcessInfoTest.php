<?php

use App\Ardillo\Models\ProcessInfo;

describe('ProcessInfo Model', function () {
    it('can be created with required parameters', function () {
        $processInfo = new ProcessInfo('1234', 'nginx');
        
        expect($processInfo->getPid())->toBe('1234');
        expect($processInfo->getName())->toBe('nginx');
        expect($processInfo->getId())->toBe('1234');
    });

    it('can be created with all parameters', function () {
        $processInfo = new ProcessInfo(
            '1234',
            'nginx',
            'www-data',
            '2.5%',
            '1024000',
            '/usr/bin/nginx -g daemon off;',
            'running'
        );
        
        expect($processInfo->getPid())->toBe('1234');
        expect($processInfo->getName())->toBe('nginx');
        expect($processInfo->getUser())->toBe('www-data');
        expect($processInfo->getCpuUsage())->toBe('2.5%');
        expect($processInfo->getMemoryUsage())->toBe('1024000');
        expect($processInfo->getCommandLine())->toBe('/usr/bin/nginx -g daemon off;');
        expect($processInfo->getStatus())->toBe('running');
    });

    it('validates required fields correctly', function () {
        $processInfo = new ProcessInfo('1234', 'nginx');
        expect($processInfo->validate())->toBeTrue();
    });

    it('fails validation with missing required fields', function () {
        $processInfo = new ProcessInfo('', '');
        expect($processInfo->validate())->toBeFalse();
        expect($processInfo->hasValidationErrors())->toBeTrue();
    });

    it('can be converted to array', function () {
        $processInfo = new ProcessInfo('1234', 'nginx', 'www-data');
        $array = $processInfo->toArray();
        
        expect($array)->toBeArray();
        expect($array['pid'])->toBe('1234');
        expect($array['name'])->toBe('nginx');
        expect($array['user'])->toBe('www-data');
    });

    it('can be created from array', function () {
        $data = [
            'pid' => '5678',
            'name' => 'apache2',
            'user' => 'www-data',
            'cpuUsage' => '1.2%',
            'memoryUsage' => '2048000',
            'commandLine' => '/usr/sbin/apache2 -DFOREGROUND',
            'status' => 'running'
        ];
        
        $processInfo = ProcessInfo::fromArray($data);
        
        expect($processInfo->getPid())->toBe('5678');
        expect($processInfo->getName())->toBe('apache2');
        expect($processInfo->getUser())->toBe('www-data');
        expect($processInfo->getCpuUsage())->toBe('1.2%');
    });

    it('can be created from command output', function () {
        $parsedData = [
            'pid' => '9999',
            'name' => 'php-fpm',
            'user' => 'www-data',
            'cpuUsage' => '0.5%',
            'memoryUsage' => '512000',
            'commandLine' => 'php-fpm: pool www',
            'status' => 'sleeping'
        ];
        
        $processInfo = ProcessInfo::fromCommandOutput($parsedData);
        
        expect($processInfo->getPid())->toBe('9999');
        expect($processInfo->getName())->toBe('php-fpm');
        expect($processInfo->getUser())->toBe('www-data');
        expect($processInfo->getStatus())->toBe('sleeping');
    });

    it('identifies system processes correctly', function () {
        $systemProcess = new ProcessInfo('1', 'init');
        expect($systemProcess->isSystemProcess())->toBeTrue();
        
        $userProcess = new ProcessInfo('1234', 'nginx');
        expect($userProcess->isSystemProcess())->toBeFalse();
        
        $kernelProcess = new ProcessInfo('0', 'kernel_task');
        expect($kernelProcess->isSystemProcess())->toBeTrue();
    });

    it('formats memory usage correctly', function () {
        $processInfo = new ProcessInfo('1234', 'nginx', '', '', '1024');
        expect($processInfo->getFormattedMemoryUsage())->toBe('1 KB');
        
        $processInfo = new ProcessInfo('1234', 'nginx', '', '', '1048576');
        expect($processInfo->getFormattedMemoryUsage())->toBe('1 MB');
        
        $processInfo = new ProcessInfo('1234', 'nginx', '', '', '1073741824');
        expect($processInfo->getFormattedMemoryUsage())->toBe('1 GB');
        
        $processInfo = new ProcessInfo('1234', 'nginx', '', '', 'invalid');
        expect($processInfo->getFormattedMemoryUsage())->toBe('invalid');
    });

    it('handles magic methods correctly', function () {
        $processInfo = new ProcessInfo('1234', 'nginx');
        
        // Test __get
        expect($processInfo->pid)->toBe('1234');
        expect($processInfo->name)->toBe('nginx');
        
        // Test __set
        $processInfo->customField = 'test';
        expect($processInfo->customField)->toBe('test');
        
        // Test __isset
        expect(isset($processInfo->pid))->toBeTrue();
        expect(isset($processInfo->nonExistentField))->toBeFalse();
    });

    it('validates numeric pid correctly', function () {
        $processInfo = new ProcessInfo('1234', 'nginx');
        expect($processInfo->isValidPid())->toBeTrue();
        
        $processInfo = new ProcessInfo('invalid', 'nginx');
        expect($processInfo->isValidPid())->toBeFalse();
        
        $processInfo = new ProcessInfo('0', 'nginx');
        expect($processInfo->isValidPid())->toBeFalse();
    });
});