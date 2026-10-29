<?php declare(strict_types=1);

namespace App\Tests\Service\Cbr;

use App\Service\Cbr\Client;
use App\Service\Cbr\Dto\DailyCurrency\Response as DailyCurrencyResponse;
use App\Service\Cbr\Exception\RequestException;
use App\Service\Cbr\Exception\XmlParseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class ClientTest extends TestCase
{
    private const DAILY_XML = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs Date="10.10.2026" name="Foreign Currency Market">
    <Valute ID="R01035">
        <NumCode>826</NumCode>
        <CharCode>GBP</CharCode>
        <Nominal>1</Nominal>
        <Name>Фунт стерлингов</Name>
        <Value>112,2951</Value>
        <VunitRate>112,2951</VunitRate>
    </Valute>
    <Valute ID="R01300">
        <NumCode>364</NumCode>
        <CharCode>IRR</CharCode>
        <Nominal>1000000</Nominal>
        <Name>Иранских риалов</Name>
        <Value>47,9492</Value>
        <VunitRate>4,79492E-05</VunitRate>
    </Valute>
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>Доллар США</Name>
        <Value>84,9048</Value>
        <VunitRate>84,9048</VunitRate>
    </Valute>
    <Valute ID="R01239">
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Nominal>1</Nominal>
        <Name>Евро</Name>
        <Value>94,9745</Value>
        <VunitRate>94,9745</VunitRate>
    </Valute>
</ValCurs>
XML;

    public function testRequest(): void
    {
        $response = new MockResponse(self::DAILY_XML);
        $httpClient = new MockHttpClient($response);

        self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-10'));

        self::assertSame(1, $httpClient->getRequestsCount());
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://cbr.ru/scripts/XML_daily.asp?date_req=10/10/2026', $response->getRequestUrl());
    }

    public function testParseCorrectResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(self::DAILY_XML));

        $result = self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-10'));

        self::assertSame('10.10.2026', $result->getDate()?->format(DailyCurrencyResponse::DATE_FORMAT));

        $currencies = $result->getCurrencies();
        self::assertCount(4, $currencies);

        $found = false;
        foreach ($currencies as $currency) {
            if ($currency->getCharCode() != 'USD') {
                continue;
            }

            $found = true;
            self::assertSame('840', $currency->getNumCode());
            self::assertSame('USD', $currency->getCharCode());
            self::assertSame(1, $currency->getNominal());
            self::assertSame('84,9048', $currency->getValue());
            self::assertSame('84,9048', $currency->getVUnitRate());
        }

        if ($found === false) {
            $this->fail('Не удалось найти в ответе валюту USD');
        }
    }

    public function testParseResponseWithoutCurrencies(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><ValCurs Date="10.10.2026" name="Foreign Currency Market"></ValCurs>';
        $httpClient = new MockHttpClient(new MockResponse($xml));

        $result = self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-10'));

        self::assertSame('10.10.2026', $result->getDate()?->format(DailyCurrencyResponse::DATE_FORMAT));
        self::assertCount(0, $result->getCurrencies());
    }

    public function testThrowRequestExceptionNonOkStatus(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('Internal Server Error', ['http_code' => 500]));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('returned status code 500');

        self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-28'));
    }

    public function testThrowRequestExceptionTransportError(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['error' => 'Could not resolve host: cbr.ru']));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Could not resolve host: cbr.ru');

        self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-28'));
    }

    public function testThrowXmlParseExceptionInvalidXml(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('<ValCurs><Valute>'));

        $this->expectException(XmlParseException::class);

        self::createClient($httpClient)->getDailyCurrency(new \DateTimeImmutable('2026-10-28'));
    }

    public function testReleaseLockAfterRequest(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());
        $httpClient = new MockHttpClient(new MockResponse(self::DAILY_XML));

        (new Client($httpClient, $lockFactory))->getDailyCurrency(new \DateTimeImmutable('2026-10-10'));

        self::assertTrue($lockFactory->createLock('cbr:request')->acquire());
    }

    public function testReleaseLockAfterFailedRequest(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());
        $httpClient = new MockHttpClient(new MockResponse('Internal Server Error', ['http_code' => 500]));

        try {
            (new Client($httpClient, $lockFactory))->getDailyCurrency(new \DateTimeImmutable('2026-10-28'));
            $this->fail('Ожидалось исключение RequestException');
        } catch (RequestException) {
        }

        self::assertTrue($lockFactory->createLock('cbr:request')->acquire());
    }

    private static function createClient(MockHttpClient $httpClient): Client
    {
        return new Client($httpClient, new LockFactory(new InMemoryStore()));
    }
}
