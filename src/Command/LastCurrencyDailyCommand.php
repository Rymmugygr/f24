<?php declare(strict_types=1);

namespace App\Command;

use App\Message\CurrencyDailyMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;


#[AsCommand(
    name: 'app:currency:last',
    description: 'Generate messages for getting daily currency rates for each of the last days from cbr.ru',
)]
class LastCurrencyDailyCommand{
    private const PERIOD = 180;

    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
    ): int {
        $io->info("Receiving currency rates for last " . self::PERIOD . " days");

        $to = new \DateTimeImmutable('midnight', new \DateTimeZone('UTC'));
        $from = $to->modify('-' . self::PERIOD . ' days');

        $period = new \DatePeriod($from, new \DateInterval('P1D'), $to, \DatePeriod::INCLUDE_END_DATE);

        $cnt = 0;
        try {
            foreach ($period as $date) {
                $this->bus->dispatch(new CurrencyDailyMessage($date->format(CurrencyDailyMessage::DATE_FORMAT)));
                $cnt++;
            }
        } catch (\Throwable $e) {
            $io->error("Failed after {$cnt} events. {$e->getMessage()}");
            return Command::FAILURE;
        }

        $io->info("All events generated: {$cnt}");

        return Command::SUCCESS;
    }
}
