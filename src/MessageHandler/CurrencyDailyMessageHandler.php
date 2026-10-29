<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CurrencyDailyMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Для каждой полученной даты выполняет команду app:currency:daily
 */
#[AsMessageHandler]
final class CurrencyDailyMessageHandler
{
    private const COMMAND = 'app:currency:daily';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CurrencyDailyMessage $message): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => self::COMMAND,
            'target-date' => $message->getDate(),
        ]);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        $code = $application->run($input, $output);

        if ($code !== Command::SUCCESS) {
            throw new \RuntimeException(
                "Command " . self::COMMAND . " failed for {$message->getDate()} with code {$code}: " . trim($output->fetch())
            );
        }

        $this->logger->info("Currency rates for {$message->getDate()} received");
    }
}
