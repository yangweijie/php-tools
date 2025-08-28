## kingbes/libui 常用组件语法总结

很高兴听到测试没有问题！以下是 kingbes/libui 库中常用组件的语法总结，可作为开发参考：

### 1. 应用程序管理
```php
// 初始化应用
App::init();

// 运行主循环
App::main();

// 退出应用
App::quit();
```

### 2. 窗口管理
```php
// 创建窗口 (标题, 宽度, 高度, 是否有菜单栏)
$window = Window::create("窗口标题", 800, 600, 1);

// 设置窗口边距
Window::setMargined($window, true);

// 设置窗口内容
Window::setChild($window, $control);

// 显示窗口
Control::show($window);

// 窗口关闭事件
Window::onClosing($window, function ($window) {
    App::quit();
    return 1; // 返回1表示允许关闭
});

// 显示消息框
Window::msgBox($window, "标题", "内容");
```

### 3. 布局容器
```php
// 创建垂直布局容器
$vbox = Box::newVerticalBox();

// 创建水平布局容器
$hbox = Box::newHorizontalBox();

// 设置容器内边距
Box::setPadded($box, true);

// 添加子控件 (stretchy 参数控制是否随容器大小变化)
Box::append($box, $control, false);

// 删除子控件 (按索引删除)
Box::delete($box, 0);
```

### 4. 标签页
```php
// 创建标签页容器
$tab = Tab::create();

// 添加标签页
Tab::append($tab, "标签名称", $control);

// 获取标签页数量
$count = Tab::numPages($tab);

// 设置标签页边距
Tab::setMargined($tab, 0, true);
```

### 5. 文本标签
```php
// 创建标签
$label = Label::create("标签文本");

// 获取标签文本
$text = Label::text($label);

// 设置标签文本
Label::setText($label, "新标签文本");
```

### 6. 按钮
```php
// 创建按钮
$button = Button::create("按钮文本");

// 按钮点击事件
Button::onClicked($button, function($button) {
    // 处理点击事件
});
```

### 7. 输入框
```php
// 创建单行输入框
$entry = Entry::create();

// 创建密码输入框
$pwdEntry = Entry::createPwd();

// 创建搜索输入框
$searchEntry = Entry::createSearch();

// 获取输入框文本
$text = Entry::text($entry);

// 设置输入框文本
Entry::setText($entry, "文本内容");

// 设置只读状态
Entry::setReadOnly($entry, true);

// 文本改变事件
Entry::onChanged($entry, function($entry) {
    // 处理文本变化
});
```

### 8. 多行文本框
```php
// 创建多行文本框
$multiEntry = MultilineEntry::create();

// 创建不换行多行文本框
$nonWrapEntry = MultilineEntry::createNonWrapping();

// 获取文本内容
$text = MultilineEntry::text($multiEntry);

// 设置文本内容
MultilineEntry::setText($multiEntry, "多行文本内容");

// 追加文本
MultilineEntry::append($multiEntry, "追加的文本");

// 设置只读状态
MultilineEntry::setReadOnly($multiEntry, true);

// 文本改变事件
MultilineEntry::onChanged($multiEntry, function($multiEntry) {
    // 处理文本变化
});
```

### 9. 复选框
```php
// 创建复选框
$checkbox = Checkbox::create("复选框文本");

// 获取选中状态
$checked = Checkbox::checked($checkbox);

// 设置选中状态
Checkbox::setChecked($checkbox, true);

// 状态改变事件
Checkbox::onToggled($checkbox, function($checkbox) {
    // 处理状态变化
});
```

### 10. 注意事项和开发技巧

1. **控件引用管理**：
   - 保存重要控件的引用，避免依赖不存在的子控件检索方法
   - 使用数组管理相关控件组，如 `$this->checkboxes[$pid] = $checkbox`

2. **容器更新**：
   - 不要依赖 `numChildren()` 和 `getChild()` 方法，它们在当前库中不存在
   - 使用创建新容器替换旧容器的方式进行UI更新
   ```php
   $newContainer = Box::newVerticalBox();
   Box::delete($parent, $index);
   Box::append($parent, $newContainer, false);
   ```

3. **事件处理**：
   - 使用PHP闭包函数处理控件事件
   - 可以通过 `use` 关键字引用外部变量
   ```php
   Button::onClicked($button, function($button) use ($window, $data) {
       // 在这里可以访问 $window 和 $data
   });
   ```

4. **跨平台考虑**：
   - Windows、macOS 和 Linux 的布局可能有细微差异
   - 设置适当的容器边距和控件大小，确保在各平台上显示正常

5. **错误处理**：
   - 使用 try/catch 捕获可能的 FFI 异常
   - 提供用户友好的错误信息，避免应用崩溃

这些组件和语法构成了使用 kingbes/libui 库创建 PHP GUI 应用的基础。通过正确组合这些组件，可以构建出功能丰富的跨平台桌面应用程序。