<?php

use App\Ardillo\Models\PortInfo;

describe('PortInfo Model', function () {
    it('can be created with required parameters', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        
        expect($portInfo->getPort())->toBe('8080');
        expect($portInfo->getPid())->toBe('1234');
        expect($portInfo->getProtocol())->toBe('TCP');
        expect($portInfo->getLocalAddress())->toBe('127.0.0.1:8080');
        expect($portInfo->getId())->toBe('1234');
    });

    it('can be created with all parameters', function () {
        $portInfo = new PortInfo(
            '8080',
            '1234',
            'TCP',
            '127.0.0.1:8080',
            '192.168.1.1:80',
            'ESTABLISHED',
            'nginx',
            '/usr/bin/nginx -g daemon off;'
        );
        
        expect($portInfo->getPort())->toBe('8080');
        expect($portInfo->getPid())->toBe('1234');
        expect($portInfo->getProtocol())->toBe('TCP');
        expect($portInfo->getLocalAddress())->toBe('127.0.0.1:8080');
        expect($portInfo->getRemoteAddress())->toBe('192.168.1.1:80');
        expect($portInfo->getState())->toBe('ESTABLISHED');
        expect($portInfo->getProcessName())->toBe('nginx');
        expect($portInfo->getCommandLine())->toBe('/usr/bin/nginx -g daemon off;');
    });

    it('validates required fields correctly', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080', '', 'LISTENING');
        expect($portInfo->validate())->toBeTrue();
    });

    it('fails validation with missing required fields', function () {
        $portInfo = new PortInfo('', '', '', '');
        expect($portInfo->validate())->toBeFalse();
        expect($portInfo->hasValidationErrors())->toBeTrue();
    });

    it('can be converted to array', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        $array = $portInfo->toArray();
        
        expect($array)->toBeArray();
        expect($array['port'])->toBe('8080');
        expect($array['pid'])->toBe('1234');
        expect($array['protocol'])->toBe('TCP');
        expect($array['localAddress'])->toBe('127.0.0.1:8080');
    });

    it('can be created from array', function () {
        $data = [
            'port' => '8080',
            'pid' => '1234',
            'protocol' => 'TCP',
            'localAddress' => '127.0.0.1:8080',
            'remoteAddress' => '',
            'state' => 'LISTENING',
            'processName' => 'nginx',
            'commandLine' => '/usr/bin/nginx'
        ];
        
        $portInfo = PortInfo::fromArray($data);
        
        expect($portInfo->getPort())->toBe('8080');
        expect($portInfo->getPid())->toBe('1234');
        expect($portInfo->getProtocol())->toBe('TCP');
        expect($portInfo->getState())->toBe('LISTENING');
    });

    it('can be created from command output', function () {
        $parsedData = [
            'port' => '80',
            'pid' => '5678',
            'protocol' => 'TCP',
            'localAddress' => '0.0.0.0:80',
            'remoteAddress' => '',
            'state' => 'LISTENING',
            'processName' => 'apache2',
            'commandLine' => '/usr/sbin/apache2 -DFOREGROUND'
        ];
        
        $portInfo = PortInfo::fromCommandOutput($parsedData);
        
        expect($portInfo->getPort())->toBe('80');
        expect($portInfo->getPid())->toBe('5678');
        expect($portInfo->getProtocol())->toBe('TCP');
        expect($portInfo->getProcessName())->toBe('apache2');
    });

    it('handles magic methods correctly', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        
        // Test __get
        expect($portInfo->port)->toBe('8080');
        expect($portInfo->pid)->toBe('1234');
        
        // Test __set
        $portInfo->customField = 'test';
        expect($portInfo->customField)->toBe('test');
        
        // Test __isset
        expect(isset($portInfo->port))->toBeTrue();
        expect(isset($portInfo->nonExistentField))->toBeFalse();
    });

    it('validates numeric port correctly', function () {
        $portInfo = new PortInfo('8080', '1234', 'TCP', '127.0.0.1:8080');
        expect($portInfo->isValidPort())->toBeTrue();
        expect($portInfo->isValidPid())->toBeTrue();
        
        $portInfo = new PortInfo('invalid', '1234', 'TCP', '127.0.0.1:8080');
        expect($portInfo->isValidPort())->toBeFalse();
        
        $portInfo = new PortInfo('8080', 'invalid', 'TCP', '127.0.0.1:8080');
        expect($portInfo->isValidPid())->toBeFalse();
    });
});