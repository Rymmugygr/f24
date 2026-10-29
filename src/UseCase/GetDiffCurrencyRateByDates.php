<?php declare(strict_types=1);

namespace App\UseCase;

use App\Repository\CurrencyRepository;
use App\UseCase\Exception\UseCaseExecuteException;
use Psr\Log\LoggerInterface;
use \App\UseCase\Dto\Response\GetDiffCurrencyRateByDates as ResponseGetDiffCurrencyRateByDates;

class GetDiffCurrencyRateByDates
{
    /**
     * Дата деноминации и перехода с кода RUR на RUB
     * Для упрощения отсекаем все что раньше, иначе пришлось бы заниматься конвертациями RUR -> RUB
     */
    private const DEADLINE_DATE = '02-01-1998'; //

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CurrencyRepository $currencyRepository,
        private readonly GetDailyCurrencyRates $getDailyCurrencyRates,
    ) {}

    /**
     * @throws UseCaseExecuteException
     */
    public function execute(\DateTimeImmutable $currDate, string $fromCurrencyCode, string $toCurrencyCode): ResponseGetDiffCurrencyRateByDates
    {
        $prevDate = $currDate->modify('-1 day');

        $currValFrom = $this->get($currDate, strtoupper($fromCurrencyCode));
        $prevValFrom = $this->get($prevDate, $fromCurrencyCode);

        $currValTo = $this->get($currDate, $toCurrencyCode);
        $prevValTo = $this->get($prevDate, $toCurrencyCode);

        if ($currValFrom === null || $currValTo === null || $prevValFrom === null || $prevValTo === null) {
            $msg = "Prevent day {$prevValFrom} -> {$prevValTo} ({$prevDate->format('Y-m-d')}) " .
                "Current day {$prevValFrom} -> {$prevValTo} ({$prevDate->format('Y-m-d')})";
            $this->logger->error("GetDiffCurrencyRateByDates: Insufficient data for calculation. {$msg}");

            throw new UseCaseExecuteException("Insufficient data for calculation");
        }

        $curr = $currValFrom / $currValTo;
        $prev = $prevValFrom / $prevValTo;
        $diff = $curr - $prev;

        return new ResponseGetDiffCurrencyRateByDates(
            $curr,
            $prev,
            $diff,
        );
    }

    private function get(\DateTimeImmutable $date, string $currencyCode): ?int
    {
        $val = $this->currencyRepository->getRate($date, $currencyCode);
        if ($val === null) {
            $this->logger->info("Load currency rates for '{$date->format('Y-m-d')}'");
            try {
                $res = $this->getDailyCurrencyRates->execute($date);
            } catch (\Exception $e) {
                $this->logger->error("failed to receive exchange rates for '{$date->format('Y-m-d')}': {$e->getMessage()}");
                return null;
            }
            foreach ($res->getCurrencies() as $currency) {
                if ($currency->getCharCode() === $currencyCode) {
                    $val = $currency->getRate();
                }
            }
        }
        return $val;
    }
}
