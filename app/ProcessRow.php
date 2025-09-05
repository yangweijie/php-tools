<?php
namespace App;

class ProcessRow {
    public $checked; // 复选框状态
    public $pid;     // 进程ID
    public $user;    // 用户
    public $command; // 命令

    public function __construct($checked, $pid, $user, $command) {
        $this->checked = $checked;
        $this->pid = $pid;
        $this->user = $user;
        $this->command = $command;
    }
}