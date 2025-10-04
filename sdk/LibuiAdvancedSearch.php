<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Box;
use Kingbes\Libui\SDK\Enums\SearchDirection;

/**
 * 高级搜索组件
 * 支持横向/纵向布局，包含文本框、搜索按钮和透明键盘监听区域
 */
class LibuiAdvancedSearch extends LibuiComponent
{
    private SearchDirection $direction;
    private LibuiEntry $searchEntry;
    private LibuiButton $searchButton;
    private LibuiDrawArea $keyboardArea;
    private LibuiVBox $mainContainer;
    private LibuiHBox|LibuiVBox $inputContainer;
    private $onSearch = null;
    private $onTextChanged = null;
    private string $placeholder = "请输入搜索内容...";
    private bool $autoFocus = true;

    public function __construct(SearchDirection $direction = SearchDirection::HORIZONTAL) {
        parent::__construct();
        $this->direction = $direction;
        $this->setupComponents();
        $this->setupLayout();
        $this->setupEvents();
        $this->handle = $this->mainContainer->getHandle();
    }

    protected function createHandle(): CData {
        // 这个方法不会被调用，因为我们直接设置了句柄
        // 但为了满足抽象类的要求，我们需要实现它
        return Box::newVerticalBox();
    }

    private function setupComponents(): void {
        // 创建主容器
        $this->mainContainer = new LibuiVBox();
        $this->mainContainer->setPadded(true);
        
        // 创建输入容器（根据方向选择横向或纵向）
        $this->inputContainer = $this->direction === SearchDirection::HORIZONTAL
            ? new LibuiHBox()
            : new LibuiVBox();
        $this->inputContainer->setPadded(true);
        
        // 创建搜索文本框
        $this->searchEntry = new LibuiEntry();
        $this->searchEntry->setText($this->placeholder);

        // 创建搜索按钮
        $this->searchButton = new LibuiButton("🔍 搜索");

        // 创建透明的键盘监听区域
        $this->keyboardArea = new LibuiDrawArea(1, 1); // 最小尺寸，透明
    }

    private function setupLayout(): void {
        // 设置输入容器布局
        if ($this->direction === SearchDirection::HORIZONTAL) {
            // 横向布局: [文本框] [按钮]
            $this->inputContainer->append($this->searchEntry, true); // 文本框拉伸填充
            $this->inputContainer->append($this->searchButton, false);
        } else {
            // 纵向布局: [文本框]
            //          [按钮]
            $this->inputContainer->append($this->searchEntry, false);
            $this->inputContainer->append($this->searchButton, false);
        }

        // 将输入容器和键盘区域添加到主容器
        $this->mainContainer->append($this->inputContainer, true);
        $this->mainContainer->append($this->keyboardArea, false); // 键盘区域不占空间
        
        // 添加组件到父级管理
        $this->addChild($this->mainContainer);
    }

    private function setupEvents(): void {
        // 搜索按钮点击事件
        $this->searchButton->on('button.clicked', function() {
            $this->performSearch();
        });

        // 文本框内容变化事件
        $this->searchEntry->on('entry.changed', function($source, $text) {
            if ($this->onTextChanged) {
                ($this->onTextChanged)($text, $this);
            }
            $this->emit('search.text_changed', $text);
        });

        // 透明区域键盘事件监听
        $this->keyboardArea->onKey(function($keyEvent) {
            return $this->handleKeyPress($keyEvent);
        });

        // 让键盘区域透明绘制
        $this->keyboardArea->onDraw(function($ctx) {
            // 不绘制任何内容，保持透明
        });

        // 文本框获得焦点时清除placeholder
        $this->setupFocusEvents();
    }

    private function setupFocusEvents(): void {
        // 模拟焦点事件 - 当文本等于placeholder时清空
        $this->searchEntry->on('entry.changed', function($source, $text) {
            if ($text === $this->placeholder) {
                // 用户开始输入，清除placeholder
                $this->searchEntry->setText('');
            }
        });
    }

    private function handleKeyPress(CData $keyEvent): bool {
        // 检查是否按下回车键
        $key = $keyEvent->Key ?? 0;
        $modifiers = $keyEvent->Modifiers ?? 0;

        // 回车键的键码通常是13或者特定的ExtKey
        if ($key === 13 || $this->isEnterKey($keyEvent)) {
            $this->performSearch();
            return true; // 表示事件已处理
        }

        // Ctrl+F 快捷键聚焦搜索框
        if ($this->isCtrlF($keyEvent)) {
            $this->focusSearchEntry();
            return true;
        }

        // ESC键清空搜索框
        if ($this->isEscapeKey($keyEvent)) {
            $this->clearSearch();
            return true;
        }

        return false; // 事件未处理，继续传播
    }

    private function isEnterKey(CData $keyEvent): bool {
        // 检查多种可能的回车键表示
        $key = $keyEvent->Key ?? 0;
        $extKey = $keyEvent->ExtKey ?? 0;

        return $key === 13 || // 标准回车键
            $key === 10 || // 换行符
            $extKey === 13; // 扩展键回车
    }

    private function isCtrlF(CData $keyEvent): bool {
        $key = $keyEvent->Key ?? 0;
        $modifiers = $keyEvent->Modifiers ?? 0;

        // 检查是否是Ctrl+F (F键=70, Ctrl修饰符通常是2)
        return ($key === 70 || $key === 102) && ($modifiers & 2);
    }

    private function isEscapeKey(CData $keyEvent): bool {
        $extKey = $keyEvent->ExtKey ?? 0;
        return $extKey === 27; // ESC键
    }

    private function performSearch(): void {
        $searchText = $this->getSearchText();

        if (empty($searchText) || $searchText === $this->placeholder) {
            $this->logger->debug("Search attempted with empty text");
            return;
        }

        $this->logger->info("Search performed", ['query' => $searchText]);

        if ($this->onSearch) {
            ($this->onSearch)($searchText, $this);
        }

        $this->emit('search.performed', $searchText);
    }

    private function focusSearchEntry(): void {
        // 清空并聚焦搜索框
        if ($this->searchEntry->getText() === $this->placeholder) {
            $this->searchEntry->setText('');
        }
        // 注意: libui可能没有直接的focus方法，这里是概念性实现
        $this->emit('search.focused');
    }

    private function clearSearch(): void {
        $this->searchEntry->setText($this->placeholder);
        $this->emit('search.cleared');
    }

    // 公共API方法

    /**
     * 设置搜索事件回调
     */
    public function onSearch(callable $callback): self {
        $this->onSearch = $callback;
        return $this;
    }

    /**
     * 设置文本变化事件回调
     */
    public function onTextChanged(callable $callback): self {
        $this->onTextChanged = $callback;
        return $this;
    }

    /**
     * 设置占位符文本
     */
    public function setPlaceholder(string $placeholder): self {
        $oldText = $this->searchEntry->getText();
        if ($oldText === $this->placeholder || empty($oldText)) {
            $this->searchEntry->setText($placeholder);
        }
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 获取搜索文本
     */
    public function getSearchText(): string {
        $text = $this->searchEntry->getText();
        return $text === $this->placeholder ? '' : $text;
    }

    /**
     * 设置搜索文本
     */
    public function setSearchText(string $text): self {
        $this->searchEntry->setText($text);
        return $this;
    }

    /**
     * 设置按钮文本
     */
    public function setButtonText(string $text): self {
        $this->searchButton->setText($text);
        return $this;
    }

    /**
     * 启用/禁用搜索功能
     */
    public function setEnabled(bool $enabled): self {
        $this->searchEntry->setReadOnly(!$enabled);
        // 注意: 这里需要Button有enable/disable方法
        $this->emit('search.enabled_changed', $enabled);
        return $this;
    }

    /**
     * 触发搜索（编程方式）
     */
    public function triggerSearch(): self {
        $this->performSearch();
        return $this;
    }

    /**
     * 获取搜索框组件（用于高级定制）
     */
    public function getSearchEntry(): LibuiEntry {
        return $this->searchEntry;
    }

    /**
     * 获取搜索按钮组件（用于高级定制）
     */
    public function getSearchButton(): LibuiButton {
        return $this->searchButton;
    }

    /**
     * 设置搜索框宽度（仅横向布局有效）
     */
    public function setSearchWidth(int $width): self {
        if ($this->direction === SearchDirection::HORIZONTAL) {
            // 这里需要Entry组件支持宽度设置
            $this->emit('search.width_changed', $width);
        }
        return $this;
    }

    /**
     * 添加快捷键提示
     */
    public function addKeyboardHints(): self {
        $hintLabel = new LibuiLabel("提示: 回车搜索 | Ctrl+F聚焦 | ESC清空");

        // 添加提示标签到主容器
        $this->mainContainer->append($hintLabel, false);

        return $this;
    }
}