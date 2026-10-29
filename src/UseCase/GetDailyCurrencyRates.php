<?php declare(strict_types=1);

namespace App\UseCase;

use App\Repository\CurrencyRepository;
use App\Service\Cbr\Client;
use App\Service\Cbr\Dto\DailyCurrency\Response as DailyCurrencyResponse;
use App\Service\Cbr\Exception\RequestException;
use Psr\Log\LoggerInterface;

class GetDailyCurrencyRates
{
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

        /**
         * TODO: довольно грязный хак, и выглядит как будто ему тут не место
         * Добавлен для упрощения работы с российскими рублями, в том числе до деноминации 1998
         */
        $rates = [
            'RUR' => 10_000_000,
            'RUB' => 10_000_000_000,
        ];
        foreach ($response->getCurrencies() as $dto) {
            $rate = $dto->getRate();
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
}
