<?php declare(strict_types=1);

namespace App\UseCase\Dto\Response;

use JsonSerializable;

class GetDiffCurrencyRateByDates
{
    private const FORMAT = 8;
    private const SEPARATOR = ".";
    private const THOUSANDS_SEPARATOR = "";

    public function __construct(
        public readonly float $currRate,
        public readonly float $prevRate,
        public readonly float $diff,
    ){}

    public function getCurrRate(): string
    {
        return number_format($this->currRate, self::FORMAT, self::SEPARATOR, self::THOUSANDS_SEPARATOR);
    }

    public function getPrevRate(): string
    {
        return number_format($this->prevRate, self::FORMAT, self::SEPARATOR, self::THOUSANDS_SEPARATOR);
    }

    public function getDiff(): string
    {
        return number_format($this->diff, self::FORMAT, self::SEPARATOR, self::THOUSANDS_SEPARATOR);
    }
}
