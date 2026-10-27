<?php declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Config\Currency;
use http\QueryString;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/v1.0', name: 'api_v1.0_')]
final class CurrencyController extends AbstractController
{
    #[Route('/currency', name: 'currency_to', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        return $this->json([
            'rate' => 1.12345,
            'diff' => -0.01234,
        ]);
    }
}
