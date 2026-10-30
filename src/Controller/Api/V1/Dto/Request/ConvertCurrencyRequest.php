<?php declare(strict_types=1);

namespace App\Controller\Api\V1\Dto\Request;

use App\Config\Currency;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ConvertCurrencyRequest
{
    public const DATE_FORMAT = 'Y-m-d';
    public const MIN_DATE = '1998-01-01';

    public function __construct(
        #[Assert\Sequentially([
            new Assert\NotBlank(message: "Parameter 'date' is required"),
            new Assert\Date(message: "Parameter 'date' must be a valid date in format YYYY-MM-DD"),
            new Assert\GreaterThan(value: self::MIN_DATE, message: "Parameter 'date' must be greater than {{ compared_value }}"),
        ])]
        public readonly string $date,

        #[Assert\Sequentially([
            new Assert\NotBlank(message: "Parameter 'from' is required"),
            new Assert\Choice(callback: [self::class, 'getCurrencyCodes'], message: "Parameter 'from' has unsupported currency code {{ value }}"),
        ])]
        public readonly string $from,

        #[Assert\Sequentially([
            new Assert\NotBlank(message: "Parameter 'to' is required"),
            new Assert\Choice(callback: [self::class, 'getCurrencyCodes'], message: "Parameter 'to' has unsupported currency code {{ value }}"),
        ])]
        public readonly string $to,
    ) {}

    /**
     * @return string[]
     */
    public static function getCurrencyCodes(): array
    {
        return array_column(Currency::cases(), 'value');
    }

    /**
     * Возвращает null для невалидной даты (в т.ч. с переполнением, например 2026-13-01)
     */
    public function getDate(): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $this->date);

        return $date === false || $date->format(self::DATE_FORMAT) !== $this->date ? null : $date;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $date = $this->getDate();
        if ($date !== null && $date > new \DateTimeImmutable('today')) {
            $context->buildViolation("Parameter 'date' must not be in the future")
                ->atPath('date')
                ->addViolation();
        }

        if ($this->from !== '' && $this->from === $this->to) {
            $context->buildViolation("Parameters 'from' and 'to' must be different")
                ->atPath('to')
                ->addViolation();
        }
    }
}
