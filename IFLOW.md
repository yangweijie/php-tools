# PHP Tools Collection - iFlow 架构文档

## 项目概述

PHP Tools Collection 是一个跨平台的 PHP 工具集合，使用 kingbes/libui 库构建了图形用户界面。该工具包包含系统管理实用程序和 HTTP 负载测试工具。

## 项目架构

### 核心组件

1. **GUI框架**: 基于 kingbes/libui PHP-FFI 绑定构建的原生桌面应用
2. **命令行接口**: 通过 `cli.php` 提供命令行访问
3. **PHAR打包系统**: 使用 humbug/box 构建可分发的 PHAR 文件
4. **热重载开发**: 提供开发时自动重启功能

### 目录结构

```
/Volumes/data/git/php/php-tools/
├── app/                      # 应用程序源代码
│   ├── App.php              # 主GUI应用程序类
│   ├── PortKiller.php       # 端口查杀工具
│   ├── ProcessKiller.php    # 进程查杀工具
│   ├── DownloadAcceleratorTab.php # 下载加速器
│   ├── SQLite2MySQLTab.php  # SQLite转MySQL工具
│   ├── IntelligentPackagerTab.php # 智能打包工具
│   └── ...                  # 其他工具组件
├── sdk/                     # Libui SDK封装
├── scripts/                 # 开发和实用脚本
├── build/                   # 构建输出目录
├── builds/                  # PHAR构建输出目录
├── config/                  # 配置文件
├── docs/                    # 文档文件
├── tests/                   # 测试文件
├── vendor/                  # Composer依赖
└── kingbes/libui/          # 本地libui库
```

## 核心功能模块

### 1. 端口查杀工具 (Port Killer)
- 通过端口号扫描占用进程
- 显示进程详细信息(PID、用户、命令)
- 支持选择性终止进程
- 跨平台支持(Windows、macOS、Linux)

### 2. 进程查杀工具 (Process Killer)
- 通过名称或PID搜索运行进程
- 显示详细进程信息
- 支持批量操作和选择性终止
- 跨平台支持

### 3. 下载加速器 (Download Accelerator)
- 支持多种平台的URL转换
- 集成Xget加速服务
- 支持GitHub、GitLab、PyPI等平台
- 一键复制加速链接到剪贴板

### 4. SQLite转MySQL工具 (SQLite2MySQL)
- 将SQLite数据库同步到MySQL远程数据库
- 支持批处理和排除表功能
- 提供进度条显示转换进度
- 自动下载sqlite2mysql.phar工具

### 5. 智能打包工具 (Intelligent Packager)
- 将PHP命令行程序智能打包成基于GUI的独立应用程序
- 支持参数分析和配置
- 自动生成GUI包装器
- 支持PHAR文件创建

### 6. 其他工具
- Wifi破解工具
- 测试步骤切换功能
- 示例和调试工具

## 技术架构

### GUI框架
- 基于 libui 原生UI库
- 使用 PHP FFI 进行绑定
- 提供跨平台一致的原生外观和体验
- 支持标签页式界面组织不同工具

### 命令行接口
```bash
# 运行GUI应用
php cli.php gui

# 构建PHAR文件
php cli.php build

# 显示帮助
php cli.php help
```

### PHAR构建系统
- 使用 Box 工具进行PHAR打包
- 配置文件: `box.json`
- 自动包含必要的依赖和资源文件
- 支持跨平台构建

### 热重载开发
- 监控 `app/` 目录中的PHP文件变更
- 自动重启GUI应用程序以应用更改
- 开发脚本: `dev.sh` 和 `scripts/watcher.php`

## 开发流程

### 环境要求
- PHP 8.2+ with FFI extension
- Composer
- 支持的平台: Windows, macOS, Linux (x86_64架构)

### 安装依赖
```bash
composer install
```

### 运行应用
```bash
# 开发模式运行
php cli.php gui

# 热重载开发
./dev.sh
```

### 构建PHAR
```bash
# 构建PHAR文件
php cli.php build

# 或使用构建脚本
./build.sh
```

### 运行测试
```bash
php run_tests.php
```

## 部署和分发

### 预构建二进制文件
- Windows (x86_64): `tools-windows.exe`
- macOS (x86_64): `tools-macos`
- Linux (x86_64): `tools-linux`

### GitHub Actions 自动化
- 自动构建所有平台的二进制文件
- 推送新标签时自动创建发布版本
- 附加所有平台二进制文件到发布版本

## 依赖关系

### 核心依赖
- kingbes/libui: PHP-FFI绑定的libui库
- humbug/box: PHAR构建工具
- monolog/monolog: 日志记录库

### 开发依赖
- pestphp/pest: 测试框架
- laravel/pint: 代码格式化工具
- phpacker/phpacker: PHP打包工具

## 配置文件

### 应用配置
- `config/app.php`: 应用程序配置
- `config/commands.php`: 命令配置

### 构建配置
- `box.json`: PHAR构建配置
- `composer.json`: Composer依赖配置

## 扩展和定制

### 添加新工具
1. 在 `app/` 目录中创建新的工具类
2. 在 `cli.php` 的 `runGuiApplication()` 函数中注册新工具
3. 实现必要的UI组件和业务逻辑

### 自定义UI组件
1. 使用 kingbes/libui SDK 提供的组件
2. 创建自定义组件类继承基础组件
3. 在工具类中使用自定义组件

## 故障排除

### 常见问题
1. **libui库加载失败**: 确保系统架构匹配且libui库文件存在
2. **PHAR构建失败**: 检查Box工具是否正确安装
3. **GUI显示异常**: 检查libui版本兼容性

### 调试工具
- 使用 `debug_*.php` 文件进行组件测试
- 查看日志输出获取错误信息
- 使用开发模式运行应用以便调试

## 未来规划

### 功能增强
- 增加更多系统管理工具
- 完善网络工具功能
- 添加更多平台支持

### 性能优化
- 优化GUI响应速度
- 减少内存占用
- 提高跨平台兼容性

### 用户体验
- 改进界面设计
- 增加更多自定义选项
- 提供更好的错误处理和用户反馈