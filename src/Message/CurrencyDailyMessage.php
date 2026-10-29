<?php declare(strict_types=1);

namespace App\Message;

/**
 * Задание на получение курсов валют ЦБ за одну дату
 */
final class CurrencyDailyMessage
{
    public const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly string $date,
    ) {
    }

    public function getDate(): string
    {
        return $this->date;
    }
}
