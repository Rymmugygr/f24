<?php declare(strict_types=1);

namespace App\Service\Cbr\Dto\DailyCurrency;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Currency
{
    public const FLOAT_SEPARATOR = ",";
    private const RATE_SCALE = 10;

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
        return strtoupper($this->charCode);
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

    /**
     * Возвращает курс за 1 единицу валюты в виде целого числа с фиксированной точностью RATE_SCALE знаков
     * (например "84,9048" -> 849048000000). Если значение не удалось распознать - возвращает null
     *
     * @return int|null
     */
    public function getRate(): ?int
    {
        $decimal = self::toDecimal($this->vUnitRate);

        return $decimal === null ? null : self::toRate($decimal);
    }

    /**
     * Преобразует число из экспонентциальной формы ("4,79492E-05") в десятичную форму ("0.0000479492")
     * Если не удалось - возвращает null
     */
    private static function toDecimal(string $value): ?string
    {
        if (!preg_match('/^(\d+)(?:,(\d+))?(?:E([+-]?\d+))?$/i', trim($value), $m)) {
            return null;
        }

        $digits = $m[1] . ($m[2] ?? '');
        $point = strlen($m[1]) + (int)($m[3] ?? 0);

        if ($point <= 0) {
            $digits = str_repeat('0', 1 - $point) . $digits;
            $point = 1;
        } elseif ($point > strlen($digits)) {
            $digits = str_pad($digits, $point, '0');
        }

        $integer = ltrim(substr($digits, 0, $point), '0') ?: '0';
        $fraction = rtrim(substr($digits, $point), '0');

        return $fraction === '' ? $integer : "{$integer}.{$fraction}";
    }

    /**
     * Преобразует десятичную строку в целое число с фиксированной точностью RATE_SCALE знаков
     * (целая часть × 10^RATE_SCALE + дробная часть, дополненная нулями справа до RATE_SCALE знаков).
     * Если не удалось - возвращает null
     */
    private static function toRate(string $decimal): ?int
    {
        [$integer, $fraction] = explode('.', $decimal) + [1 => ''];

        $rate = ltrim($integer . str_pad(substr($fraction, 0, self::RATE_SCALE), self::RATE_SCALE, '0'), '0') ?: '0';

        if ((string)(int)$rate !== $rate) {
            return null;
        }

        return (int)$rate;
    }
}
