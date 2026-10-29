<?php declare(strict_types=1);

namespace App\Command;

use App\Model\Currency;
use App\UseCase\GetDailyCurrencyRates;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'app:currency:daily',
    description: 'Get daily currency rates for each day from cbr.ru',
)]
class CurrencyDailyCommand{
    const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly GetDailyCurrencyRates $getDailyCurrencyRates,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Date in YYYY-MM-DD format')] string $targetDate = ''
    ): int {
        if ($targetDate === '') {
            $date = new \DateTimeImmutable('today');
        } else {
            $date = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $targetDate);
            if ($date === false || $date->format(self::DATE_FORMAT) !== $targetDate) {
                $io->error("Incorrect date in YYYY-MM-DD format \"{$targetDate}\"");
                return Command::INVALID;
            }
        }

        $io->info("Receiving currency rates for {$date->format(self::DATE_FORMAT)}");

        try {
            $res = $this->getDailyCurrencyRates->execute($date);
        } catch (\Exception $e) {
            $io->error("Failed. {$e->getMessage()}");
            return Command::FAILURE;
        }

        $cnt = count($res->getCurrencies());
        $io->info("Got {$cnt} records");

        return Command::SUCCESS;
    }
}
