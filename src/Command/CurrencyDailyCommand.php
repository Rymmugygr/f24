<?php

namespace App\Command;

use App\Service\Cbr\Client;
use App\Service\Cbr\Exception\RequestException;
use App\Service\Cbr\Exception\XmlParseException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


#[AsCommand(
    name: 'app:currency:daily',
    description: 'Get daily currency rates for each day from cbr.ru',
)]
class CurrencyDailyCommand{
    const DATE_FORMAT = 'Y-m-d';

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Date in YYYY-MM-DD format')] string $targetDate = ''
    ): int {
        $date = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $targetDate);

        $io->info("Получение курсов валют за {$date->format(self::DATE_FORMAT)}");

        $transport = new CurlHttpClient();
        $client = new Client($transport);
        try {
            $res = $client->getDailyCurrency($date);
        } catch (\Exception $e) {
            $io->error("Не удалось получить курсы валют. {$e->getMessage()}");
            return Command::FAILURE;
        }

        $cnt = count($res->getCurrencies());
        $io->info("Получено {$cnt} записей");

        return Command::SUCCESS;
    }
}
