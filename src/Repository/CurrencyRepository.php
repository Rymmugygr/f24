<?php declare(strict_types=1);

namespace App\Repository;

use Predis\ClientInterface;

class CurrencyRepository
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly ClientInterface $redis,
    ) {
    }

    /**
     * Возвращает курс валюты за указанную дату
     * Значение возвращается в виде целого числа, 10 цифр которого являются дробной частью
     * Если значение не найдено, возвращается null
     *
     * @param \DateTimeInterface $date
     * @param string $charCode
     * @return int|null
     */
    public function getRate(\DateTimeInterface $date, string $charCode): ?int
    {
        $val = $this->redis->get(self::key($date, $charCode));
        if ($val === null) {
            return null;
        }

        return (int)$val;
    }

    /**
     * Сохраняет курсы валют в хранилище за указанную дату
     * Параметр $rates имеет формат ['USD' => 123456, 'EUR' => 123456]
     * Значение хранится в виде целого числа, 10 цифр которого являются дробной частью
     *
     * @param \DateTimeInterface $date
     * @param array $rates
     * @return void
     */
    public function saveRates(\DateTimeInterface $date, array $rates): void
    {
        if ($rates === []) {
            return;
        }

        $values = [];
        foreach ($rates as $charCode => $rate) {
            $values[self::key($date, $charCode)] = $rate;
        }

        $this->redis->mset($values);
    }

    private static function key(\DateTimeInterface $date, string $charCode): string
    {
        return "{$date->format(self::DATE_FORMAT)}-{$charCode}";
    }
}
