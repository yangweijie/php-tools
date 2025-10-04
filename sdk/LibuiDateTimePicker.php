<?php

namespace Kingbes\Libui\SDK;

use DateMalformedStringException;
use Kingbes\Libui\DateTime;
use Kingbes\Libui\DateTimePicker;
use Kingbes\Libui\SDK\Enums\DateTimePickerType;

/**
 * 日期时间选择器组件
 */
class LibuiDateTimePicker extends LibuiComponent
{
    private DateTimePickerType $type;
    private ?DateTime $currentDateTime = null;
    private $onChanged = null;

    public function __construct(DateTimePickerType $type = DateTimePickerType::DATE_TIME) {
        parent::__construct();
        $this->type = $type;
        $this->handle = $this->createHandle();
        $this->currentDateTime = $this->getCurrentDateTime();
        $this->setupEvents();
    }

    protected function createHandle(): \FFI\CData {
        return match($this->type) {
            DateTimePickerType::DATE_TIME => DateTimePicker::createDataTime(),
            DateTimePickerType::DATE_ONLY => DateTimePicker::createDate(),
            DateTimePickerType::TIME_ONLY => DateTimePicker::createTime(),
        };
    }

    private function setupEvents(): void {
        DateTimePicker::onChanged($this->handle, function() {
            $this->currentDateTime = $this->getCurrentDateTime();

            if ($this->onChanged) {
                ($this->onChanged)($this->currentDateTime, $this);
            }

            $this->emit('datetime.changed.' . $this->getId(), $this->currentDateTime);
        });
    }

    // 便捷方法
    public function onChange(callable $callback, int $priority = 0): string {
        return $this->on('datetime.changed', $callback, $priority);
    }

    private function getCurrentDateTime(): DateTime
    {
        return DateTimePicker::time($this->handle);
    }

    public function setDateTime(DateTime $dateTime): self {
        DateTimePicker::setTime($this->handle, $dateTime);
        $this->currentDateTime = $dateTime;
        return $this;
    }

    public function setFromPhpDateTime(\DateTime $phpDateTime): self {
        $libUiDateTime = new DateTime(
            (int)$phpDateTime->format('s'),
            (int)$phpDateTime->format('i'),
            (int)$phpDateTime->format('H'),
            (int)$phpDateTime->format('j'),
            (int)$phpDateTime->format('n'),
            (int)$phpDateTime->format('Y'),
            (int)$phpDateTime->format('N'),
            (int)$phpDateTime->format('z'),
            -1
        );

        return $this->setDateTime($libUiDateTime);
    }

    public function setToCurrentTime(): self {
        return $this->setFromPhpDateTime(new \DateTime());
    }

    public function getDateTime(): DateTime {
        return $this->currentDateTime ?? $this->getCurrentDateTime();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getAsPhpDateTime(): \DateTime {
        $dt = $this->getDateTime();
        return new \DateTime(sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $dt->year,
            $dt->mon,
            $dt->mday,
            $dt->hour,
            $dt->min,
            $dt->sec
        ));
    }

    public function getFormattedDateTime(string $format = 'Y-m-d H:i:s'): string {
        return $this->getAsPhpDateTime()->format($format);
    }

    public function onChanged(callable $callback): self {
        $this->onChanged = $callback;
        return $this;
    }

    public function getType(): DateTimePickerType {
        return $this->type;
    }
}
