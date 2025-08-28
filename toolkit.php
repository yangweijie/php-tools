<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\App;
use App\PortKiller;
use App\ProcessKiller;

// 创建应用
$application = new App();

// 创建端口查杀工具
$portKiller = new PortKiller();
$application->addTab("端口查杀", $portKiller->getControl());

// 创建进程查杀工具
$processKiller = new ProcessKiller();
$application->addTab("进程查杀", $processKiller->getControl());

// 运行应用
$application->run();