<?php

require_once 'vendor/autoload.php';

use Kingbes\Webui;

class WebSystemToolbox
{
    private $webui;
    private $window;
    private $port;

    public function __construct()
    {
        $this->webui = new Webui();
        $this->window = $this->webui->newWindow();
        
        // Try browser mode instead of WebView2
        $this->webui->setBrowser($this->window, 'chrome'); // Try Chrome first
        $this->webui->setSize($this->window, 1200, 800);
        
        $this->bindFunctions();
    }

    private function bindFunctions()
    {
        $this->webui->bind($this->window, 'searchByPort', function($event, $js) {
            return $this->searchByPort($event, $js);
        });
        $this->webui->bind($this->window, 'searchByProcess', function($event, $js) {
            return $this->searchByProcess($event, $js);
        });
        $this->webui->bind($this->window, 'killProcess', function($event, $js) {
            return $this->killProcess($event, $js);
        });
    }

    public function searchByPort($event, $js)
    {
        $port = $js;
        $command = "netstat -ano | findstr :$port";
        $output = shell_exec($command);
        
        $processes = [];
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (preg_match('/\s+(\d+)$/', $line, $matches)) {
                    $pid = $matches[1];
                    $processInfo = shell_exec("tasklist /fi \"PID eq $pid\" /fo csv /nh");
                    if ($processInfo) {
                        $info = str_getcsv(trim($processInfo));
                        $processes[] = [
                            'pid' => $pid,
                            'name' => $info[0] ?? 'Unknown',
                            'memory' => $info[4] ?? 'Unknown'
                        ];
                    }
                }
            }
        }
        
        return json_encode(['success' => true, 'processes' => $processes]);
    }

    public function searchByProcess($event, $js)
    {
        $keyword = $js;
        $command = "tasklist /fi \"IMAGENAME eq *$keyword*\" /fo csv /nh";
        $output = shell_exec($command);
        
        $processes = [];
        if ($output && !strpos($output, 'INFO: No tasks')) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (!empty(trim($line))) {
                    $info = str_getcsv(trim($line));
                    if (count($info) >= 5) {
                        $processes[] = [
                            'pid' => $info[1],
                            'name' => $info[0],
                            'memory' => $info[4]
                        ];
                    }
                }
            }
        }
        
        return json_encode(['success' => true, 'processes' => $processes]);
    }

    public function killProcess($event, $js)
    {
        $pid = $js;
        $command = "taskkill /f /pid $pid 2>&1";
        $output = shell_exec($command);
        
        $success = strpos($output, 'SUCCESS') !== false;
        return json_encode([
            'success' => $success,
            'message' => $success ? "进程 $pid 已成功终止" : "终止进程失败: $output"
        ]);
    }

    private function getHTML()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>系统管理工具箱</title>
    <script src="webui.js"></script>
    <style>
        body { font-family: Arial; margin: 0; background: #f5f5f5; }
        .container { display: flex; height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .content { flex: 1; padding: 20px; }
        .nav-item { padding: 10px; margin: 5px 0; cursor: pointer; border-radius: 5px; }
        .nav-item:hover { background: #34495e; }
        .nav-item.active { background: #3498db; }
        .tool-section { display: none; }
        .tool-section.active { display: block; }
        input, button { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #3498db; color: white; cursor: pointer; }
        button:hover { background: #2980b9; }
        .process-list { margin-top: 20px; }
        .process-item { background: white; padding: 10px; margin: 5px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .kill-btn { background: #e74c3c; padding: 5px 10px; font-size: 12px; }
        .kill-btn:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>🛠️ 工具箱</h2>
            <div class="nav-item active" onclick="showTool(\'port\')">🔌 端口查杀</div>
            <div class="nav-item" onclick="showTool(\'process\')">⚙️ 进程查杀</div>
        </div>
        <div class="content">
            <div id="port-tool" class="tool-section active">
                <h2>端口查杀</h2>
                <input type="number" id="port-input" placeholder="请输入端口号" />
                <button onclick="searchPort()">查询进程</button>
                <div id="port-results" class="process-list"></div>
            </div>
            <div id="process-tool" class="tool-section">
                <h2>进程查杀</h2>
                <input type="text" id="process-input" placeholder="请输入进程名关键词" />
                <button onclick="searchProcess()">查询进程</button>
                <div id="process-results" class="process-list"></div>
            </div>
        </div>
    </div>

    <script>
        function showTool(tool) {
            document.querySelectorAll(\'.tool-section\').forEach(el => el.classList.remove(\'active\'));
            document.querySelectorAll(\'.nav-item\').forEach(el => el.classList.remove(\'active\'));
            
            document.getElementById(tool + \'-tool\').classList.add(\'active\');
            event.target.classList.add(\'active\');
        }

        async function searchPort() {
            const port = document.getElementById(\'port-input\').value;
            if (!port) return alert(\'请输入端口号\');
            
            try {
                const result = await webui.call(\'searchByPort\', port);
                const response = JSON.parse(result);
                displayResults(\'port-results\', response.processes);
            } catch (error) {
                alert(\'查询失败: \' + error.message);
            }
        }

        async function searchProcess() {
            const keyword = document.getElementById(\'process-input\').value;
            if (!keyword) return alert(\'请输入进程名关键词\');
            
            try {
                const result = await webui.call(\'searchByProcess\', keyword);
                const response = JSON.parse(result);
                displayResults(\'process-results\', response.processes);
            } catch (error) {
                alert(\'查询失败: \' + error.message);
            }
        }

        function displayResults(containerId, processes) {
            const container = document.getElementById(containerId);
            if (processes.length === 0) {
                container.innerHTML = \'<p>未找到相关进程</p>\';
                return;
            }
            
            container.innerHTML = processes.map(proc => `
                <div class="process-item">
                    <div>
                        <strong>PID: ${proc.pid}</strong> - ${proc.name}<br>
                        <small>内存: ${proc.memory}</small>
                    </div>
                    <button class="kill-btn" onclick="killProcess(${proc.pid})">终止</button>
                </div>
            `).join(\'\');
        }

        async function killProcess(pid) {
            if (!confirm(`确定要终止进程 ${pid} 吗？`)) return;
            
            try {
                const result = await webui.call(\'killProcess\', pid);
                const response = JSON.parse(result);
                alert(response.message);
                
                if (response.success) {
                    // 刷新当前显示的结果
                    const activeSection = document.querySelector(\'.tool-section.active\');
                    if (activeSection.id === \'port-tool\') {
                        searchPort();
                    } else {
                        searchProcess();
                    }
                }
            } catch (error) {
                alert(\'操作失败: \' + error.message);
            }
        }
    </script>
</body>
</html>';
    }

    public function run()
    {
        echo "启动Web版本系统管理工具箱...\n";
        echo "尝试使用浏览器模式而不是WebView2...\n";
        
        $html = $this->getHTML();
        
        echo "显示应用程序窗口...\n";
        $result = $this->webui->show($this->window, $html);
        
        if ($result) {
            echo "✅ 应用程序启动成功！\n";
            echo "🌐 如果没有看到窗口，请检查浏览器是否打开了新标签页\n";
            $this->webui->wait();
        } else {
            echo "❌ 应用程序启动失败\n";
            echo "尝试启动本地Web服务器作为备选方案...\n";
            $this->startWebServer();
        }
        
        $this->webui->clean();
    }

    private function startWebServer()
    {
        $port = 8080;
        echo "启动本地Web服务器在端口 $port...\n";
        
        // 创建临时HTML文件
        file_put_contents('temp-toolbox.html', $this->getHTML());
        
        echo "请在浏览器中打开: http://localhost:$port/temp-toolbox.html\n";
        echo "按 Ctrl+C 停止服务器\n";
        
        // 启动PHP内置服务器
        $command = "php -S localhost:$port";
        passthru($command);
    }
}

$app = new WebSystemToolbox();
$app->run();
?>