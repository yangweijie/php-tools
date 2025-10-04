<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\Attribute;
use Kingbes\Libui\Draw;
use Kingbes\Libui\TextItalic;

/**
 * 富文本绘制组件
 */
class LibuiDrawText extends LibuiComponent
{
    private string $text = '';
    private array $attributes = [];
    private \FFI\CData $layout;

    public function __construct(string $text = '') {
        parent::__construct();
        $this->text = $text;
        $this->handle = $this->createHandle();
        $this->layout = $this->createLayout();
    }

    protected function createHandle(): \FFI\CData {
        $handler = \Kingbes\Libui\Area::handler(
            function($handler, $area, $params) {
                $this->drawText($params);
            }
        );

        return \Kingbes\Libui\Area::create($handler);
    }

    private function createLayout(): \FFI\CData {
        $defaultFont = \Kingbes\Libui\Base::ffi()->new("uiFontDescriptor");
        $defaultFont->Family = "Arial";
        $defaultFont->Size = 12;
        $defaultFont->Weight = \Kingbes\Libui\TextWeight::Normal->value;
        $defaultFont->Italic = TextItalic::Normal->value;
        $defaultFont->Stretch = \Kingbes\Libui\TextStretch::Normal->value;

        return Draw::newTextLayout($this->text, $defaultFont, -1);
    }

    private function drawText(\FFI\CData $params): void {
        Draw::text($params->Context, $this->layout, 10, 10);
    }

    public function setText(string $text): self {
        $this->text = $text;
        Draw::freeTextLayout($this->layout);
        $this->layout = $this->createLayout();
        $this->redraw();
        return $this;
    }

    public function setFontFamily(string $family, int $start = 0, int $end = -1): self {
        if ($end === -1) $end = strlen($this->text);

        $attr = Attribute::newFamily($family);
        Draw::textLayoutSetAttribute($this->layout, $attr, $start, $end);
        $this->redraw();
        return $this;
    }

    public function setFontSize(float $size, int $start = 0, int $end = -1): self {
        if ($end === -1) $end = strlen($this->text);

        $attr = Attribute::newSize($size);
        Draw::textLayoutSetAttribute($this->layout, $attr, $start, $end);
        $this->redraw();
        return $this;
    }

    public function setFontWeight(int $weight, int $start = 0, int $end = -1): self {
        if ($end === -1) $end = strlen($this->text);

        $attr = Attribute::newWeight($weight);
        Draw::textLayoutSetAttribute($this->layout, $attr, $start, $end);
        $this->redraw();
        return $this;
    }

    public function setFontColor(float $r, float $g, float $b, float $a = 1.0, int $start = 0, int $end = -1): self {
        if ($end === -1) $end = strlen($this->text);

        $attr = Attribute::newColor($r, $g, $b, $a);
        Draw::textLayoutSetAttribute($this->layout, $attr, $start, $end);
        $this->redraw();
        return $this;
    }

    public function setBold(bool $bold, int $start = 0, int $end = -1): self {
        $weight = $bold ? \Kingbes\Libui\TextWeight::Bold->value : \Kingbes\Libui\TextWeight::Normal->value;
        return $this->setFontWeight($weight, $start, $end);
    }

    public function setItalic(bool $italic, int $start = 0, int $end = -1): self {
        if ($end === -1) $end = strlen($this->text);

        $italicValue = $italic ? TextItalic::Italic->value : TextItalic::Normal->value;
        $attr = Attribute::newItalic($italicValue);
        Draw::textLayoutSetAttribute($this->layout, $attr, $start, $end);
        $this->redraw();
        return $this;
    }

    private function redraw(): void {
        \Kingbes\Libui\Area::queueRedraw($this->handle);
    }

    public function __destruct() {
        Draw::freeTextLayout($this->layout);
    }
}

