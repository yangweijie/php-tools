<?php

require_once 'vendor/autoload.php';

use Kingbes\Webui;
use Kingbes\JavaScript;

class SystemToolbox
{
    private $webui;
    private $window;
    private $assetServerUrl;

    public function __construct()
    {
        $this->webui = new Webui();
        $this->window = $this->webui->newWindow();
        
        // Set window size and center it on screen
        $this->webui->setSize($this->window, 1200, 800);
        $this->webui->setPosition($this->window, 100, 50); // Top-left position
        
        // Set the root folder to serve assets from ui directory
        $this->webui->setRootFolder($this->window, __DIR__ . DIRECTORY_SEPARATOR . 'ui');
        
        $this->bindFunctions();
        $this->setupAssetRouting();
    }

    private function setupWindow()
    {
        $this->webui->setSize($this->window, 1200, 800);
        // Set the root folder to serve assets from ui directory
        $this->webui->setRootFolder($this->window, __DIR__ . DIRECTORY_SEPARATOR . 'ui');
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

    private function setupAssetRouting()
    {
        // Handle asset requests
        $this->webui->bind($this->window, '__asset_handler', function($event, $js) {
            return $this->handleAssetRequest($event, $js);
        });
    }

    public function handleAssetRequest($event, JavaScript $js)
    {
        $requestPath = $js->getString($event);
        
        // Check if request starts with /assets
        if (strpos($requestPath, '/assets') === 0) {
            $filePath = __DIR__ . '/ui' . $requestPath;
            
            if (file_exists($filePath) && is_file($filePath)) {
                $content = file_get_contents($filePath);
                $mimeType = $this->getMimeType($filePath);
                
                $js->returnString($event, json_encode([
                    'success' => true,
                    'content' => base64_encode($content),
                    'mimeType' => $mimeType
                ]));
                return;
            }
        }
        
        $js->returnString($event, json_encode([
            'success' => false,
            'message' => 'Asset not found'
        ]));
    }

    private function getMimeType($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'html' => 'text/html',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    public function searchByPort($event, JavaScript $js)
    {
        $port = $js->getString($event);
        
        if (empty($port)) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'Port number is required'
            ]));
            return;
        }

        try {
            $processes = $this->getProcessesByPort($port);
            $js->returnString($event, json_encode([
                'success' => true,
                'data' => $processes
            ]));
        } catch (Exception $e) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'Error searching processes: ' . $e->getMessage()
            ]));
        }
    }

    public function searchByProcess($event, JavaScript $js)
    {
        $keyword = $js->getString($event);
        
        if (empty($keyword)) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'Process name/keyword is required'
            ]));
            return;
        }

        try {
            $processes = $this->getProcessesByName($keyword);
            $js->returnString($event, json_encode([
                'success' => true,
                'data' => $processes
            ]));
        } catch (Exception $e) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'Error searching processes: ' . $e->getMessage()
            ]));
        }
    }

    public function killProcess($event, JavaScript $js)
    {
        $pid = $js->getString($event);
        
        if (empty($pid)) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'PID is required'
            ]));
            return;
        }

        try {
            $this->killProcessByPID($pid);
            $js->returnString($event, json_encode([
                'success' => true,
                'message' => "Process {$pid} killed successfully"
            ]));
        } catch (Exception $e) {
            $js->returnString($event, json_encode([
                'success' => false,
                'message' => 'Error killing process: ' . $e->getMessage()
            ]));
        }
    }

    private function getProcessesByPort($port)
    {
        $processes = [];
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            $output = shell_exec("netstat -ano | findstr :{$port}");
        } else {
            $output = shell_exec("lsof -i :{$port}");
        }

        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                if ($os === 'Windows') {
                    $fields = preg_split('/\s+/', trim($line));
                    if (count($fields) >= 5) {
                        $pid = end($fields);
                        if ($pid !== '0' && is_numeric($pid)) {
                            $processName = $this->getProcessNameByPID($pid);
                            $processes[] = [
                                'pid' => $pid,
                                'command' => $processName,
                                'port' => $port
                            ];
                        }
                    }
                } else {
                    $fields = preg_split('/\s+/', trim($line));
                    if (count($fields) >= 2 && is_numeric($fields[1])) {
                        $processes[] = [
                            'pid' => $fields[1],
                            'command' => $fields[0],
                            'port' => $port
                        ];
                    }
                }
            }
        }

        return $processes;
    }

    private function getProcessesByName($keyword)
    {
        $processes = [];
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            $output = shell_exec('tasklist /fo csv');
        } else {
            $output = shell_exec('ps aux');
        }

        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                if (stripos($line, $keyword) !== false) {
                    if ($os === 'Windows') {
                        $fields = str_getcsv($line);
                        if (count($fields) >= 2) {
                            $processes[] = [
                                'pid' => $fields[1],
                                'command' => $fields[0]
                            ];
                        }
                    } else {
                        $fields = preg_split('/\s+/', trim($line));
                        if (count($fields) >= 11) {
                            $processes[] = [
                                'pid' => $fields[1],
                                'command' => implode(' ', array_slice($fields, 10))
                            ];
                        }
                    }
                }
            }
        }

        return $processes;
    }

    private function getProcessNameByPID($pid)
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            $output = shell_exec("tasklist /fi \"PID eq {$pid}\" /fo csv");
            if ($output) {
                $lines = explode("\n", trim($output));
                if (count($lines) > 1) {
                    $fields = str_getcsv($lines[1]);
                    return $fields[0] ?? 'Unknown';
                }
            }
        } else {
            $output = shell_exec("ps -p {$pid} -o comm=");
            if ($output) {
                return trim($output);
            }
        }

        return 'Unknown';
    }

    private function killProcessByPID($pid)
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            $result = shell_exec("taskkill /PID {$pid} /F 2>&1");
        } else {
            $result = shell_exec("kill -9 {$pid} 2>&1");
        }

        if ($result && (stripos($result, 'error') !== false || stripos($result, 'failed') !== false)) {
            throw new Exception($result);
        }
    }

    public function run()
    {
        // Start a simple HTTP server to handle asset requests
        $this->startAssetServer();
        
        $html = $this->getHTML();
        $this->webui->show($this->window, $html);
        $this->webui->wait();
        $this->webui->clean();
    }

    private function startAssetServer()
    {
        // Get available port for asset server
        $port = $this->findAvailablePort(8080);
        
        // Start asset server in background
        $command = "php -S localhost:{$port} -t " . __DIR__ . "/ui > nul 2>&1 &";
        if (PHP_OS_FAMILY !== 'Windows') {
            $command = "php -S localhost:{$port} -t " . __DIR__ . "/ui > /dev/null 2>&1 &";
        }
        
        popen($command, 'r');
        
        // Wait a moment for server to start
        usleep(500000); // 0.5 seconds
        
        // Store the asset server URL for use in HTML
        $this->assetServerUrl = "http://localhost:{$port}";
    }

    private function findAvailablePort($startPort = 8080)
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

    private function getHTML()
    {
        $html = file_get_contents(__DIR__ . '/ui/index.html');
        
        // Replace asset paths with full URLs if asset server is running
        if (isset($this->assetServerUrl)) {
            $html = str_replace('assets/', $this->assetServerUrl . '/assets/', $html);
        }
        
        return $html;
    }
}

// Run the application
$app = new SystemToolbox();
$app->run();