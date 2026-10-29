<?php declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\Api\V1\Dto\Request\ConvertCurrencyRequest;
use App\UseCase\Exception\UseCaseExecuteException;
use App\UseCase\GetDiffCurrencyRateByDates;
use DateTime;
use http\QueryString;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/v1.0', name: 'api_v1.0_')]
final class CurrencyController extends AbstractController
{
    private const DEFAULT_CURRENCY = 'RUR';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly GetDiffCurrencyRateByDates $getDiffCurrencyRateByDates,
    )
    {
    }

    #[Route('/currency', name: 'currency_to', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        try {
            $convertRequest = new ConvertCurrencyRequest(
                trim($request->query->getString('date')),
                strtoupper(trim($request->query->getString('from', self::DEFAULT_CURRENCY))),
                strtoupper(trim($request->query->getString('to'))),
            );
        } catch (BadRequestException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($convertRequest);
        if (count($violations) > 0) {
            return $this->json(['message' => $violations->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $this->getDiffCurrencyRateByDates->execute(
                $convertRequest->getDate(),
                $convertRequest->from,
                $convertRequest->to,
            );
        } catch (UseCaseExecuteException $e) {
            $this->logger->error($e->getMessage());
            var_dump($e->getMessage());
            return $this->internalError("Failed to receive exchange rates");
        }

        return $this->json([
            'rate' => $response->getCurrRate(),
            'diff' => $response->getDiff(),
        ], Response::HTTP_OK);
    }

    private function internalError(string $message): JsonResponse
    {
        return $this->json(['message' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
