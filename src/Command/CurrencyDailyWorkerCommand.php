<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:currency:worker',
    description: 'Consume messages from currency_daily queue and get currency rates for each date',
)]
class CurrencyDailyWorkerCommand{
    private const TRANSPORT = 'currency_daily';

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(
        Application $application,
        OutputInterface $output,
    ): int {
        $parameters = [
            'command' => 'messenger:consume',
            'receivers' => [self::TRANSPORT],
        ];

        $input = new ArrayInput($parameters);
        $input->setInteractive(false);

        return $application->find('messenger:consume')->run($input, $output);
    }
}
