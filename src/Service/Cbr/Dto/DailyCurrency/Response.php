<?php declare(strict_types=1);

namespace App\Service\Cbr\Dto\DailyCurrency;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\SerializedName;

class Response
{
    public const DATE_FORMAT = 'd.m.Y';

    #[SerializedName('@Date')]
    private string $date;

    #[SerializedName('@name')]
    private string $name;

    /** @var Currency[] */
    #[SerializedName('Valute')]
    private array $currencies = [];

    /**
     * Возвращает дату, по которой были получены курсы валют
     *
     * @return DateTimeImmutable|null
     */
    public function getDate(): ?DateTimeImmutable
    {
        if (!isset($this->date)) {
            return null;
        }

        return DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $this->date);
    }

    /**
     * Возвращает массив с курсами валют
     *
     * @return Currency[]
     */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}
