<?php

namespace Kingbes\Libui\SDK;

use DateTime;
use Kingbes\Libui\SDK\Enums\DateTimePickerType;

/**
 * 日期时间选择器构建器
 */
class DateTimePickerBuilder
{
    private DateTimePickerType $type = DateTimePickerType::DATE_TIME;
    private ?DateTime $initialDateTime = null;
    private $onChanged = null;

    public static function create(): self {
        return new self();
    }

    public function dateTime(): self {
        $this->type = DateTimePickerType::DATE_TIME;
        return $this;
    }

    public function dateOnly(): self {
        $this->type = DateTimePickerType::DATE_ONLY;
        return $this;
    }

    public function timeOnly(): self {
        $this->type = DateTimePickerType::TIME_ONLY;
        return $this;
    }

    public function initialValue(DateTime $dateTime): self {
        $this->initialDateTime = $dateTime;
        return $this;
    }

    public function currentTime(): self {
        $this->initialDateTime = new DateTime();
        return $this;
    }

    public function onChanged(callable $callback): self {
        $this->onChanged = $callback;
        return $this;
    }

    public function build(): LibuiDateTimePicker {
        $picker = new LibuiDateTimePicker($this->type);

        if ($this->initialDateTime) {
            $picker->setFromPhpDateTime($this->initialDateTime);
        }

        if ($this->onChanged) {
            $picker->onChanged($this->onChanged);
        }

        return $picker;
    }
}
