<?php

class WebServerToolbox
{
    private $port = 8080;

    public function __construct()
    {
        echo "System Administration Toolbox - Web Server Version\n";
        echo "================================================\n";
    }

    public function run()
    {
        // Find available port
        $this->port = $this->findAvailablePort(8080);
        
        // Create the web interface
        $this->createWebInterface();
        
        // Start the server
        $this->startServer();
    }

    private function findAvailablePort($startPort)
    {
        for ($port = $startPort; $port < $startPort + 100; $port++) {
            $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
            if (!$connection) {
                return $port; // Port is available
            }
            fclose($connection);
        }
        return $startPort; // Fallback
    }

    private function createWebInterface()
    {
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统管理工具箱</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f5f7fa; }
        .container { display: flex; height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #fff;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #fff;
        }
        
        .nav-item .icon {
            font-size: 20px;
            margin-right: 12px;
            width: 24px;
        }
        
        .content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }
        
        .tool-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tool-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-header {
            margin-bottom: 30px;
        }
        
        .section-header h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .section-header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .search-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .input-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .input-group input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .results-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .results-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .results-header h3 {
            color: #2c3e50;
            font-size: 18px;
        }
        
        .process-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .process-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }
        
        .process-item:hover {
            background: #f8f9fa;
        }
        
        .process-item:last-child {
            border-bottom: none;
        }
        
        .process-info h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .process-info p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .kill-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .kill-btn:hover {
            transform: translateY(-1px);
        }
        
        .no-results {
            text-align: center;
            padding: 40px 25px;
            color: #7f8c8d;
        }
        
        .loading {
            text-align: center;
            padding: 40px 25px;
            color: #667eea;
        }
        
        .status-message {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>🛠️ 工具箱</h1>
                <p>系统管理工具集合</p>
            </div>
            <div class="nav-menu">
                <div class="nav-item active" onclick="showTool(\'port\')">
                    <span class="icon">🔌</span>
                    <span>端口查杀</span>
                </div>
                <div class="nav-item" onclick="showTool(\'process\')">
                    <span class="icon">⚙️</span>
                    <span>进程查杀</span>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div id="port-tool" class="tool-section active">
                <div class="section-header">
                    <h2>端口查杀</h2>
                    <p>查找并终止占用指定端口的进程</p>
                </div>
                
                <div class="search-box">
                    <div class="input-group">
                        <input type="number" id="port-input" placeholder="请输入端口号 (例如: 8080)" min="1" max="65535" />
                        <button class="btn" onclick="searchPort()">🔍 查询进程</button>
                    </div>
                </div>
                
                <div id="port-results" class="results-container" style="display: none;">
                    <div class="results-header">
                        <h3>查询结果</h3>
                    </div>
                    <div id="port-list" class="process-list"></div>
                </div>
            </div>
            
            <div id="process-tool" class="tool-section">
                <div class="section-header">
                    <h2>进程查杀</h2>
                    <p>根据进程名称查找并终止进程</p>
                </div>
                
                <div class="search-box">
                    <div class="input-group">
                        <input type="text" id="process-input" placeholder="请输入进程名关键词 (例如: chrome)" />
                        <button class="btn" onclick="searchProcess()">🔍 查询进程</button>
                    </div>
                </div>
                
                <div id="process-results" class="results-container" style="display: none;">
                    <div class="results-header">
                        <h3>查询结果</h3>
                    </div>
                    <div id="process-list" class="process-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTool(tool) {
            // Update navigation
            document.querySelectorAll(\'.tool-section\').forEach(el => el.classList.remove(\'active\'));
            document.querySelectorAll(\'.nav-item\').forEach(el => el.classList.remove(\'active\'));
            
            document.getElementById(tool + \'-tool\').classList.add(\'active\');
            event.target.classList.add(\'active\');
            
            // Hide results when switching tools
            document.getElementById(tool + \'-results\').style.display = \'none\';
        }

        async function searchPort() {
            const port = document.getElementById(\'port-input\').value;
            if (!port) {
                alert(\'请输入端口号\');
                return;
            }
            
            const resultsContainer = document.getElementById(\'port-results\');
            const resultsList = document.getElementById(\'port-list\');
            
            resultsContainer.style.display = \'block\';
            resultsList.innerHTML = \'<div class="loading">🔄 正在查询端口 \' + port + \' 的进程...</div>\';
            
            try {
                const response = await fetch(\'api.php?action=searchByPort&port=\' + encodeURIComponent(port));
                const data = await response.json();
                
                if (data.success) {
                    displayResults(\'port-list\', data.processes, \'port\');
                } else {
                    resultsList.innerHTML = \'<div class="no-results">❌ 查询失败: \' + (data.message || \'未知错误\') + \'</div>\';
                }
            } catch (error) {
                resultsList.innerHTML = \'<div class="no-results">❌ 网络错误: \' + error.message + \'</div>\';
            }
        }

        async function searchProcess() {
            const keyword = document.getElementById(\'process-input\').value;
            if (!keyword) {
                alert(\'请输入进程名关键词\');
                return;
            }
            
            const resultsContainer = document.getElementById(\'process-results\');
            const resultsList = document.getElementById(\'process-list\');
            
            resultsContainer.style.display = \'block\';
            resultsList.innerHTML = \'<div class="loading">🔄 正在查询包含 "\' + keyword + \'" 的进程...</div>\';
            
            try {
                const response = await fetch(\'api.php?action=searchByProcess&keyword=\' + encodeURIComponent(keyword));
                const data = await response.json();
                
                if (data.success) {
                    displayResults(\'process-list\', data.processes, \'process\');
                } else {
                    resultsList.innerHTML = \'<div class="no-results">❌ 查询失败: \' + (data.message || \'未知错误\') + \'</div>\';
                }
            } catch (error) {
                resultsList.innerHTML = \'<div class="no-results">❌ 网络错误: \' + error.message + \'</div>\';
            }
        }

        function displayResults(containerId, processes, type) {
            const container = document.getElementById(containerId);
            
            if (processes.length === 0) {
                container.innerHTML = \'<div class="no-results">📭 未找到相关进程</div>\';
                return;
            }
            
            container.innerHTML = processes.map(proc => `
                <div class="process-item">
                    <div class="process-info">
                        <h4>🔹 ${proc.name}</h4>
                        <p><strong>PID:</strong> ${proc.pid} | <strong>内存:</strong> ${proc.memory}</p>
                    </div>
                    <button class="kill-btn" onclick="killProcess(${proc.pid}, \'${type}\')">🗑️ 终止</button>
                </div>
            `).join(\'\');
        }

        async function killProcess(pid, type) {
            if (!confirm(`⚠️ 确定要终止进程 ${pid} 吗？\\n\\n此操作不可撤销，请谨慎操作！`)) {
                return;
            }
            
            try {
                const response = await fetch(\'api.php?action=killProcess&pid=\' + encodeURIComponent(pid));
                const data = await response.json();
                
                // Show status message
                showStatusMessage(data.message, data.success ? \'success\' : \'error\');
                
                if (data.success) {
                    // Refresh current results
                    if (type === \'port\') {
                        setTimeout(searchPort, 1000);
                    } else {
                        setTimeout(searchProcess, 1000);
                    }
                }
            } catch (error) {
                showStatusMessage(\'操作失败: \' + error.message, \'error\');
            }
        }

        function showStatusMessage(message, type) {
            // Remove existing status messages
            document.querySelectorAll(\'.status-message\').forEach(el => el.remove());
            
            const statusDiv = document.createElement(\'div\');
            statusDiv.className = `status-message status-${type}`;
            statusDiv.textContent = message;
            
            const activeSection = document.querySelector(\'.tool-section.active .search-box\');
            activeSection.appendChild(statusDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (statusDiv.parentNode) {
                    statusDiv.remove();
                }
            }, 5000);
        }

        // Enter key support
        document.getElementById(\'port-input\').addEventListener(\'keypress\', function(e) {
            if (e.key === \'Enter\') searchPort();
        });

        document.getElementById(\'process-input\').addEventListener(\'keypress\', function(e) {
            if (e.key === \'Enter\') searchProcess();
        });
    </script>
</body>
</html>';

        file_put_contents('toolbox.html', $html);
        
        // Create API endpoint
        $this->createAPI();
        
        echo "✅ Web interface created: toolbox.html\n";
        echo "✅ API endpoint created: api.php\n";
    }

    private function createAPI()
    {
        $api = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$action = $_GET["action"] ?? "";

switch ($action) {
    case "searchByPort":
        echo json_encode(searchByPort($_GET["port"] ?? ""));
        break;
    case "searchByProcess":
        echo json_encode(searchByProcess($_GET["keyword"] ?? ""));
        break;
    case "killProcess":
        echo json_encode(killProcess($_GET["pid"] ?? ""));
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}

function searchByPort($port) {
    if (empty($port) || !is_numeric($port)) {
        return ["success" => false, "message" => "Invalid port number"];
    }
    
    $command = "netstat -ano | findstr :$port";
    $output = shell_exec($command);
    
    $processes = [];
    if ($output) {
        $lines = explode("\\n", trim($output));
        $seenPids = [];
        
        foreach ($lines as $line) {
            if (preg_match(\'/\\s+(\\d+)$/\', $line, $matches)) {
                $pid = $matches[1];
                
                // Skip if we already processed this PID
                if (in_array($pid, $seenPids)) continue;
                $seenPids[] = $pid;
                
                $processInfo = shell_exec("tasklist /fi \"PID eq $pid\" /fo csv /nh 2>nul");
                if ($processInfo && !strpos($processInfo, "INFO: No tasks")) {
                    $info = str_getcsv(trim($processInfo));
                    if (count($info) >= 5) {
                        $processes[] = [
                            "pid" => $pid,
                            "name" => $info[0] ?? "Unknown",
                            "memory" => $info[4] ?? "Unknown"
                        ];
                    }
                }
            }
        }
    }
    
    return ["success" => true, "processes" => $processes];
}

function searchByProcess($keyword) {
    if (empty($keyword)) {
        return ["success" => false, "message" => "Keyword cannot be empty"];
    }
    
    // Escape special characters for command line
    $safeKeyword = escapeshellarg("*" . $keyword . "*");
    $command = "tasklist /fi \"IMAGENAME eq $safeKeyword\" /fo csv /nh 2>nul";
    $output = shell_exec($command);
    
    $processes = [];
    if ($output && !strpos($output, "INFO: No tasks")) {
        $lines = explode("\\n", trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && $line !== "\"\"") {
                $info = str_getcsv($line);
                if (count($info) >= 5) {
                    $processes[] = [
                        "pid" => $info[1],
                        "name" => $info[0],
                        "memory" => $info[4]
                    ];
                }
            }
        }
    }
    
    return ["success" => true, "processes" => $processes];
}

function killProcess($pid) {
    if (empty($pid) || !is_numeric($pid)) {
        return ["success" => false, "message" => "Invalid PID"];
    }
    
    // Check if process exists first
    $checkCommand = "tasklist /fi \"PID eq $pid\" /fo csv /nh 2>nul";
    $checkOutput = shell_exec($checkCommand);
    
    if (!$checkOutput || strpos($checkOutput, "INFO: No tasks") !== false) {
        return ["success" => false, "message" => "进程 $pid 不存在或已经终止"];
    }
    
    $command = "taskkill /f /pid $pid 2>&1";
    $output = shell_exec($command);
    
    $success = strpos($output, "SUCCESS") !== false;
    $message = $success ? "✅ 进程 $pid 已成功终止" : "❌ 终止进程失败: " . trim($output);
    
    return [
        "success" => $success,
        "message" => $message
    ];
}
?>';

        file_put_contents('api.php', $api);
    }

    private function startServer()
    {
        echo "\n🚀 启动系统管理工具箱...\n";
        echo "📡 服务器地址: http://localhost:{$this->port}\n";
        echo "🌐 工具箱地址: http://localhost:{$this->port}/toolbox.html\n";
        echo "\n";
        echo "✨ 功能说明:\n";
        echo "   🔌 端口查杀 - 查找并终止占用指定端口的进程\n";
        echo "   ⚙️ 进程查杀 - 根据进程名称查找并终止进程\n";
        echo "\n";
        echo "⚠️  注意事项:\n";
        echo "   • 终止系统关键进程可能导致系统不稳定\n";
        echo "   • 建议谨慎操作，确认后再执行终止操作\n";
        echo "   • 按 Ctrl+C 可停止服务器\n";
        echo "\n";
        echo "🎯 服务器启动中...\n";
        echo str_repeat("=", 50) . "\n";
        
        // Open browser automatically
        if (PHP_OS_FAMILY === 'Windows') {
            popen("start http://localhost:{$this->port}/toolbox.html", 'r');
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            popen("open http://localhost:{$this->port}/toolbox.html", 'r');
        } else {
            popen("xdg-open http://localhost:{$this->port}/toolbox.html", 'r');
        }
        
        // Start PHP built-in server
        $command = "php -S localhost:{$this->port}";
        passthru($command);
    }
}

// Start the application
$app = new WebServerToolbox();
$app->run();
?>