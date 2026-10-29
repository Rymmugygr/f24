<?php declare(strict_types=1);

namespace App\Tests\Service\Cbr\Dto\DailyCurrency;

use App\Service\Cbr\Dto\DailyCurrency\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public static function toDecimalProvider(): iterable
    {
        yield 'integer' => ['12', '12'];
        yield 'zero' => ['0', '0'];
        yield 'zero with fraction' => ['0,000', '0'];
        yield 'comma separator' => ['84,9048', '84.9048'];
        yield 'less than one' => ['0,5', '0.5'];
        yield 'trailing zeros in fraction' => ['1,000', '1'];
        yield 'leading and trailing zeros' => ['007,500', '7.5'];
        yield 'surrounding whitespace' => [' 84,9048 ', '84.9048'];
        yield 'exponent negative' => ['4,79492E-05', '0.0000479492'];
        yield 'exponent negative without fraction' => ['1E-3', '0.001'];
        yield 'exponent moves point inside digits' => ['123E-2', '1.23'];
        yield 'exponent to zero point' => ['1,5E-1', '0.15'];
        yield 'exponent lowercase' => ['1,5e-1', '0.15'];
        yield 'exponent zero' => ['5E0', '5'];
        yield 'exponent positive with sign' => ['1,5E+1', '15'];
        yield 'exponent positive pads zeros' => ['1,5E2', '150'];
    }

    #[DataProvider('toDecimalProvider')]
    public function testToDecimal(string $value, string $expected): void
    {
        self::assertSame($expected, $this->invoke('toDecimal', $value));
    }

    public static function toDecimalInvalidProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'letters' => ['abc'];
        yield 'dot separator' => ['84.9048'];
        yield 'negative' => ['-1,5'];
        yield 'empty integer part' => [',5'];
        yield 'empty fractional part' => ['1,'];
        yield 'several separators' => ['1,2,3'];
        yield 'empty exponent' => ['1,5E'];
        yield 'exponent without mantissa' => ['E5'];
    }

    #[DataProvider('toDecimalInvalidProvider')]
    public function testToDecimalReturnsNullOnInvalidValue(string $value): void
    {
        self::assertNull($this->invoke('toDecimal', $value));
    }

    public static function toRateProvider(): iterable
    {
        yield 'zero' => ['0', 0];
        yield 'integer' => ['150', 1_500_000_000_000];
        yield 'decimal' => ['84.9048', 849_048_000_000];
        yield 'empty fractional part' => ['12.', 120_000_000_000];
        yield 'small decimal' => ['0.0000479492', 479_492];
        yield 'full precision' => ['1.0123456789', 10_123_456_789];
        yield 'fraction longer than precision is truncated' => ['1.01234567891', 10_123_456_789];
        yield 'below precision' => ['0.00000000001', 0];
        yield 'max int' => ['922337203.6854775807', PHP_INT_MAX];
    }

    #[DataProvider('toRateProvider')]
    public function testToRate(string $decimal, int $expected): void
    {
        self::assertSame($expected, $this->invoke('toRate', $decimal));
    }

    public static function toRateInvalidProvider(): iterable
    {
        yield 'max int overflow' => ['922337203.6854775808'];
        yield 'large integer part' => ['1000000000'];
        yield 'not a number' => ['abc'];
    }

    #[DataProvider('toRateInvalidProvider')]
    public function testToRateReturnsNullOnInvalidValue(string $decimal): void
    {
        self::assertNull($this->invoke('toRate', $decimal));
    }

    public static function getRateProvider(): iterable
    {
        yield 'zero' => ['0', 0];
        yield 'integer' => ['150', 1_500_000_000_000];
        yield 'comma separator' => ['84,9048', 849_048_000_000];
        yield 'exponent negative' => ['4,79492E-05', 479_492];
        yield 'exponent positive' => ['1,5E2', 1_500_000_000_000];
        yield 'fraction longer than precision is truncated' => ['1,01234567891', 10_123_456_789];
    }

    #[DataProvider('getRateProvider')]
    public function testGetRate(string $vUnitRate, int $expected): void
    {
        self::assertSame($expected, $this->createCurrency($vUnitRate)->getRate());
    }

    public static function getRateInvalidProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'dot separator' => ['84.9048'];
        yield 'max int overflow' => ['922337203,6854775808'];
    }

    #[DataProvider('getRateInvalidProvider')]
    public function testGetRateReturnsNullOnInvalidValue(string $vUnitRate): void
    {
        self::assertNull($this->createCurrency($vUnitRate)->getRate());
    }

    private function createCurrency(string $vUnitRate): Currency
    {
        $currency = new Currency();
        (new \ReflectionProperty(Currency::class, 'vUnitRate'))->setValue($currency, $vUnitRate);
        return $currency;
    }

    private function invoke(string $method, string $value): mixed
    {
        $reflection = new \ReflectionClass(Currency::class);
        return $reflection->getMethod($method)->invoke(null, $value);
    }
}
