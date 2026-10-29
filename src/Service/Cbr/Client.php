<?php declare(strict_types=1);

namespace App\Service\Cbr;

use App\Service\Cbr\Dto\DailyCurrency\Response as DailyCurrencyResponse;
use App\Service\Cbr\Exception\RequestException;
use App\Service\Cbr\Exception\XmlParseException;
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

    private HttpClientInterface $client;
    private SerializerInterface $serializer;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
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

        try {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                throw new RequestException("App\Service\Cbr\Client: request {$url} returned status code {$response->getStatusCode()}");
            }
        }  catch (TransportExceptionInterface $e) {
            throw new RequestException($e->getMessage());
        }

        try {
            $dto = $this->serializer->deserialize($response->getContent(), DailyCurrencyResponse::class, self::RESPONSE_FORMAT);
        } catch (ExceptionInterface $e) {
            throw new XmlParseException($e->getMessage());
        }

        return $dto;
    }
}
