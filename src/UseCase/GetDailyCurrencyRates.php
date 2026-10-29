<?php declare(strict_types=1);

namespace App\UseCase;

use App\Repository\CurrencyRepository;
use App\Service\Cbr\Client;
use App\Service\Cbr\Dto\DailyCurrency\Response as DailyCurrencyResponse;
use App\Service\Cbr\Exception\RequestException;
use Psr\Log\LoggerInterface;

class GetDailyCurrencyRates
{
    private const RATE_SCALE = 10;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Client $client,
        private readonly CurrencyRepository $repository,
    ) {
    }

    /**
     * Получает курсы валют за дату из ЦБ и сохраняет их, перезаписывая уже сохранённые за эту дату
     *
     * @param \DateTimeInterface $date
     * @return DailyCurrencyResponse
     * @throws RequestException
     */
    public function execute(\DateTimeInterface $date): DailyCurrencyResponse
    {
        try {
            $response = $this->client->getDailyCurrency($date);
        } catch (\Throwable $e) {
            $this->logger->error("UseCase GetDailyCurrencyRates error: could not get response" . $e->getMessage());
            throw new RequestException("UseCase GetDailyCurrencyRates error: " . $e->getMessage(), $e->getCode(), $e);
        }

        $rates = [];
        foreach ($response->getCurrencies() as $dto) {
            $decimal = self::toDecimal($dto->getVUnitRate());
            $rate = $decimal === null ? null : self::toRate($decimal);
            // Валюты с нераспознанным курсом пропускаем, чтобы не сохранить некорректное значение
            if ($rate === null) {
                $this->logger->warning("UseCase GetDailyCurrencyRates error: unsupported value " . $dto->getVUnitRate());
                continue;
            }
            $rates[$dto->getCharCode()] = $rate;
        }
        /**
         * Note: записываем именно с $date, а не с $response->getDate(), что бы учесть дни без торгов. и не задаваться
         * Например для 2026-01-01 $response->getDate() будет возвращать 2025-12-31 (базируется на атрибуты ответа)
         * и курсы по нему. Но т.к. мы игнорируем этот факт, то сможем в дальнейшем опираться на календарные дни, где
         * по факуту будут лежать значения валют на актуальный торговый день
         */
        $this->repository->saveRates($date, $rates);

        return $response;
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
