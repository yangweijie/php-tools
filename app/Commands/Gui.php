<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\App;
use App\PortKiller;
use App\ProcessKiller;

class Gui extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gui';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动GUI工具集';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
