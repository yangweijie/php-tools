<?php

namespace Kingbes\Libui\SDK;

use InvalidArgumentException;
use Kingbes\Libui\SDK\Enums\DateTimePickerType;

/**
 * 组件工厂 - 方便扩展新组件
 */
class ComponentFactory
{
    private static array $componentMap = [
        'window' => LibuiWindow::class,
        'button' => LibuiButton::class,
        'label' => LibuiLabel::class,
        'entry' => LibuiEntry::class,
        'checkbox' => LibuiCheckbox::class,
        'combobox' => LibuiCombobox::class,
        'editable_combobox' => LibuiEditableCombobox::class,
        'progressbar' => LibuiProgressBar::class,
        'slider' => LibuiSlider::class,
        'spinbox' => LibuiSpinbox::class,
        'radio' => LibuiRadio::class,
        'multiline_entry' => LibuiMultilineEntry::class,
        'vbox' => LibuiVBox::class,
        'hbox' => LibuiHBox::class,
        'form' => LibuiForm::class,
        'grid' => LibuiGrid::class,
        'group' => LibuiGroup::class,
        'table' => LibuiTable::class,
        'tab' => LibuiTab::class,
        'drawarea' => LibuiDrawArea::class,
        'drawtext' => LibuiDrawText::class,
        'browser_window' => BrowserWindow::class,
        'save_window' => SaveWindow::class,
        'message_window' => MessageWindow::class,
        'datetime_picker' => LibuiDateTimePicker::class,
        'date_picker' => [LibuiDateTimePicker::class, DateTimePickerType::DATE_ONLY],
        'time_picker' => [LibuiDateTimePicker::class, DateTimePickerType::TIME_ONLY],
    ];
    public static function create(string $type, array $args = []): LibuiComponent {
        if (!isset(self::$componentMap[$type])) {
            throw new InvalidArgumentException("Unknown component type: {$type}");
        }

        $class = self::$componentMap[$type];
        return new $class(...$args);
    }

    public static function register(string $type, string $className): void {
        if (!is_subclass_of($className, LibuiComponent::class)) {
            throw new InvalidArgumentException("Component must extend LibuiComponent");
        }

        self::$componentMap[$type] = $className;
    }

    public static function getAvailableTypes(): array {
        return array_keys(self::$componentMap);
    }
}
