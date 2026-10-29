<?php declare(strict_types=1);

namespace App\Service\Cbr;

use App\Service\Cbr\Dto\DailyCurrency\Response as DailyCurrencyResponse;
use App\Service\Cbr\Exception\RequestException;
use App\Service\Cbr\Exception\XmlParseException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client
{
    private const GET_DAILY_CURRENCY = "https://cbr.ru/scripts/XML_daily.asp?date_req={date}";

    private const DATE_FORMAT = "d/m/Y";
    private const RESPONSE_FORMAT = 'xml';

    // Общий lock на все запросы к cbr.ru, чтобы параллельные процессы не обращались к ЦБ одновременно
    private const LOCK_KEY = 'cbr:request';
    // TTL lock в секундах, с запасом больше максимальной длительности HTTP запроса, чтобы lock не истёк во время запроса
    private const LOCK_TTL = 90.0;
    private const REQUEST_MAX_DURATION = 60.0;

    private HttpClientInterface $client;
    private LockFactory $lockFactory;
    private SerializerInterface $serializer;

    public function __construct(HttpClientInterface $client, LockFactory $lockFactory)
    {
        $this->client = $client;
        $this->lockFactory = $lockFactory;
        $this->serializer = self::createSerializer();
    }

    private static function createSerializer(): SerializerInterface
    {
        $metadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $typeExtractor = new PropertyInfoExtractor(
            // PropertyNormalizer writes properties directly, so types must come from properties, not accessors
            typeExtractors: [
                new PhpDocExtractor(mutatorPrefixes: [], accessorPrefixes: []),
                new ReflectionExtractor(mutatorPrefixes: [], accessorPrefixes: []),
            ],
        );

        $normalizer = new PropertyNormalizer(
            classMetadataFactory: $metadataFactory,
            nameConverter: new MetadataAwareNameConverter($metadataFactory),
            propertyTypeExtractor: $typeExtractor,
        );

        return new Serializer([$normalizer, new ArrayDenormalizer()], [new XmlEncoder()]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RequestException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws XmlParseException
     */
    public function getDailyCurrency(\DateTimeInterface $date) : DailyCurrencyResponse
    {
        $url = str_replace("{date}", $date->format(self::DATE_FORMAT), self::GET_DAILY_CURRENCY);

        $content = $this->request($url);

        try {
            $dto = $this->serializer->deserialize($content, DailyCurrencyResponse::class, self::RESPONSE_FORMAT);
        } catch (ExceptionInterface $e) {
            throw new XmlParseException($e->getMessage());
        }

        return $dto;
    }

    /**
     * Выполняет GET запрос к cbr.ru под lock и возвращает тело ответа
     * Если lock занят другим процессом, ожидает его освобождения
     *
     * @throws RequestException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function request(string $url): string
    {
        $lock = $this->lockFactory->createLock(self::LOCK_KEY, self::LOCK_TTL);

        try {
            $lock->acquire(true);
        } catch (\Symfony\Component\Lock\Exception\ExceptionInterface $e) {
            throw new RequestException("App\Service\Cbr\Client: could not acquire lock for {$url}: {$e->getMessage()}", 0, $e);
        }

        try {
            // Тело читаем внутри lock, т.к. ответ HttpClient ленивый и запрос фактически выполняется при чтении
            $response = $this->client->request('GET', $url, ['max_duration' => self::REQUEST_MAX_DURATION]);
            if ($response->getStatusCode() !== 200) {
                throw new RequestException("App\Service\Cbr\Client: request {$url} returned status code {$response->getStatusCode()}");
            }

            return $response->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new RequestException($e->getMessage());
        } finally {
            $lock->release();
        }
    }
}
