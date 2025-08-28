# PHP 工具集

基于 kingbes/libui 开发的图形化工具集，包含系统管理和 HTTP 压测工具，支持跨平台运行。

## 功能特性

- 🖥️ **图形化界面**: 基于 libui 的原生桌面应用
- 🛠️ **系统工具**: 端口查杀、进程查杀等系统管理工具
- 🚀 **HTTP 压测**: 支持高并发异步 HTTP 请求压测
- ⚙️ **灵活配置**: 支持多种 HTTP 方法和自定义参数
- 💾 **配置管理**: JSON 格式的配置文件保存和加载
- 📊 **实时监控**: 压测过程中实时显示性能指标
- 📈 **详细分析**: 提供详细的性能统计和响应时间分析

## 系统要求

### 命令行版本 (推荐)
- PHP 8.0 或更高版本
- curl 扩展
- json 扩展
- 支持所有架构 (x86_64, ARM64 等)

### GUI 版本 (有架构限制)
- PHP 8.2 或更高版本
- PHP-FFI 扩展
- curl 扩展
- json 扩展
- **仅支持 x86_64 架构** (不支持 ARM64/Apple Silicon)

⚠️ **重要提示**: kingbes/libui 库目前只支持 x86_64 架构。在 ARM64 系统（如 Apple Silicon Mac）上建议使用功能完整的命令行版本。

## 安装

1. 克隆项目：
```bash
git clone <repository-url>
cd tools
```

2. 安装依赖：
```bash
composer install
```

3. 确保已安装 libui PHP 扩展：
```bash
# 根据你的系统安装 libui 扩展
# 具体安装方法请参考 kingbes/libui 文档
```

## 使用方法

### 工具集 GUI 版本
```bash
# 启动工具集 GUI 应用
php toolkit.php
```

**特性**:
- ✅ 端口查杀工具：查询和终止占用指定端口的进程
- ✅ 进程查杀工具：查询和终止指定进程
- ✅ HTTP 压测工具：进行 HTTP 接口性能测试
- ✅ 跨平台支持：支持 Windows、macOS 和 Linux

### HTTP 压测工具

#### CLI版本 - 交互式界面 (推荐)
```bash
# 启动交互式CLI工具 - 压测完成后不会自动退出
php test_cli.php

# 查看帮助信息
php test_cli.php -h
```

**特性**:
- ✅ 持续运行，压测完成后不退出
- ✅ 交互式菜单操作
- ✅ 支持多次连续压测
- ✅ 配置保存和加载
- ✅ 详细结果显示
- ✅ 支持 Ctrl+C 安全退出

#### GUI版本 - 增强版 (可视化界面)
```bash
# 启动改进的GUI版本 - 压测完成后界面保持打开
php gui_enhanced.php
```

**特性**:
- ✅ 压测完成后界面保持打开
- ✅ 关闭时确认对话框
- ✅ 更好的错误处理
- ✅ 提供CLI版本备选方案

### 原始版本 (压测完成后自动退出)

#### 基础CLI版本
```bash
# 简单演示版本 (压测完成后进程自动退出)
php simple_gui_demo.php
```

#### 标准GUI版本
⚠️ **注意**: 这些版本在压测完成后可能会自动退出

```bash
php gui_test.php    # 基础GUI版本
php app.php         # 完整GUI应用
```

**GUI 版本要求**:
- kingbes/libui 库 (已包含在依赖中)
- PHP-FFI 扩展
- 兼容的系统架构 (目前库支持 x86_64，ARM64 支持可能有限)

如果 GUI 版本无法运行，建议使用功能完整的命令行版本。

## 系统工具使用说明

### 端口查杀工具
1. 在"端口查杀"标签页中输入要查询的端口号
2. 点击"查询占用进程"按钮查看占用该端口的进程信息
3. 点击"杀进程"按钮终止占用该端口的所有进程

### 进程查杀工具
1. 在"进程查杀"标签页中输入进程名或PID
2. 点击"查询进程"按钮查看匹配的进程信息
3. 点击"杀进程"按钮终止匹配的所有进程

## HTTP 压测参数说明

| 参数 | 说明 | 默认值 |
|------|------|--------|
| 目标URL | 要压测的HTTP接口地址 | - |
| HTTP方法 | GET、POST、PUT、DELETE等 | GET |
| 并发数 | 同时发起的请求数量 | 1 |
| 请求总数 | 总共发送的请求数量 | 100 |
| 持续时间 | 按时间压测（秒），0为按请求数 | 0 |
| 超时时间 | 单个请求的超时时间（秒） | 30 |
| 请求间延迟 | 请求之间的延迟（毫秒） | 0 |
| Content-Type | 请求内容类型 | application/json |

## 目录结构

```
tools/
├── toolkit.php          # 工具集主程序入口
├── app.php              # HTTP压测主程序入口
├── composer.json        # 依赖配置
├── configs/             # 配置文件目录
│   └── example.json     # 示例配置
├── src/                 # 源代码目录
│   ├── App.php          # GUI应用主类
│   ├── PortKiller.php   # 端口查杀工具类
│   ├── ProcessKiller.php# 进程查杀工具类
│   ├── Config/          # 配置相关类
│   │   ├── LoadTestConfig.php
│   │   ├── LoadTestResult.php
│   │   └── ConfigManager.php
│   ├── Engine/          # 压测引擎
│   │   └── LoadTestEngine.php
│   └── GUI/             # 图形界面
│       └── MainWindow.php
└── vendor/              # 依赖包目录
```

## 开发

### 代码结构

- `App\App`: GUI 应用主类
- `App\PortKiller`: 端口查杀工具类
- `App\ProcessKiller`: 进程查杀工具类
- `App\Config\LoadTestConfig`: 压测配置数据结构
- `App\Config\LoadTestResult`: 压测结果数据结构  
- `App\Config\ConfigManager`: 配置文件管理器
- `App\Engine\LoadTestEngine`: HTTP 压测引擎
- `App\GUI\MainWindow`: 主窗口界面

### 扩展功能

1. **添加新的工具**：
   - 创建新的工具类
   - 在 toolkit.php 中添加标签页

2. **自定义报告格式**：
   - 扩展 `LoadTestResult` 类的 `toArray()` 方法
   - 添加新的导出功能

## 许可证

本项目采用 MIT 许可证，详见 LICENSE 文件。

## 贡献

欢迎提交 Issue 和 Pull Request！

## 常见问题

### Q: 如何安装 kingbes/libui 扩展？
A: kingbes/libui 已包含在 composer 依赖中，但需要 PHP-FFI 扩展支持。请确保 PHP 已启用 FFI 扩展。

### Q: GUI 版本报错 "incompatible architecture" 怎么办？
A: 这是 kingbes/libui 库的架构兼容性问题。目前库主要支持 x86_64，在 ARM64 (Apple Silicon) 系统上可能无法正常运行。建议使用功能完整的命令行版本。

### Q: 压测时出现内存不足错误怎么办？
A: 可以适当降低并发数，或者增加 PHP 的内存限制：`php -d memory_limit=1G test_cli.php`

### Q: 支持 HTTPS 吗？
A: 是的，工具支持 HTTPS 请求，默认会忽略 SSL 证书验证。

### Q: 可以压测需要认证的 API 吗？
A: 可以，在请求头中添加相应的认证信息，如 `Authorization: Bearer token`。

### Q: 为什么推荐使用命令行版本？
A: 命令行版本无需 GUI 依赖，兼容性更好，功能完整，且适合在服务器环境中使用。