<?php declare(strict_types=1);

namespace App\Service\Cbr\Dto\DailyCurrency;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Currency
{
    public const FLOAT_SEPARATOR = ",";

    #[SerializedName('@ID')]
    private string $id;

    #[SerializedName('NumCode')]
    private string $numCode;

    #[SerializedName('CharCode')]
    private string $charCode;

    #[SerializedName('Nominal')]
    private int $nominal;

    #[SerializedName('Name')]
    private string $name;

    #[SerializedName('Value')]
    private string $value;

    #[SerializedName('VunitRate')]
    private string $vUnitRate;

    /**
     * Возвращает внутренний идентификатор валюты в ЦБ
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Возвращает цифровой ISO код валюты
     *
     * @return string
     */
    public function getNumCode(): string
    {
        return $this->numCode;
    }

    /**
     * Возвращает буквенный ISO код валюты
     *
     * @return string
     */
    public function getCharCode(): string
    {
        return $this->charCode;
    }

    /**
     * Возвращает количество валюты, для которой актуален курс обмена из $this->getValue()
     *
     * @return int
     */
    public function getNominal(): int
    {
        return $this->nominal;
    }

    /**
     * Возвращает название валюты
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Вовзращает курс за $this->getNominal() единиц валюты
     *
     * WARNING: разделителем дробной части выступает символ запятой ",", а так же возможно значение
     * с экспотенциальной формы записи
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Возвращает курс за 1 единицу валюты
     *
     * WARNING: разделителем дробной части выступает символ запятой ",", а так же возможно значение
     * с экспотенциальной формы записи
     *
     * @return string
     */
    public function getVUnitRate(): string
    {
        return $this->vUnitRate;
    }
}
