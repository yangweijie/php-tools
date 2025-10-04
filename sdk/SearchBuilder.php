<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\SDK\Enums\SearchDirection;

/**
 * 搜索组件构建器 - 提供流畅的API
 */
class SearchBuilder
{
    private SearchDirection $direction = SearchDirection::HORIZONTAL;
    private string $placeholder = "搜索...";
    private string $buttonText = "🔍 搜索";
    private bool $showHints = false;
    private $onSearch = null;
    private $onTextChanged = null;

    public static function create(): self {
        return new self();
    }

    public function horizontal(): self {
        $this->direction = SearchDirection::HORIZONTAL;
        return $this;
    }

    public function vertical(): self {
        $this->direction = SearchDirection::VERTICAL;
        return $this;
    }

    public function placeholder(string $text): self {
        $this->placeholder = $text;
        return $this;
    }

    public function buttonText(string $text): self {
        $this->buttonText = $text;
        return $this;
    }

    public function showKeyboardHints(bool $show = true): self {
        $this->showHints = $show;
        return $this;
    }

    public function onSearch(callable $callback): self {
        $this->onSearch = $callback;
        return $this;
    }

    public function onTextChanged(callable $callback): self {
        $this->onTextChanged = $callback;
        return $this;
    }

    public function build(): LibuiAdvancedSearch {
        $search = new LibuiAdvancedSearch($this->direction);

        $search->setPlaceholder($this->placeholder)
            ->setButtonText($this->buttonText);

        if ($this->onSearch) {
            $search->onSearch($this->onSearch);
        }

        if ($this->onTextChanged) {
            $search->onTextChanged($this->onTextChanged);
        }

        if ($this->showHints) {
            $search->addKeyboardHints();
        }

        return $search;
    }
}
