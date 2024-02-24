<?php

declare(strict_types=1);

namespace Example\Http\Controllers;

use Example\Dto\Request\JsonRequestDto;
use Example\Dto\Request\QueryRequest;
use Example\Dto\Response\JsonResponseDto;
use Illuminate\Routing\Controller;
use Kr0lik\ParamConverter\Annotation\ParamConverter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Tag;
use Spatie\RouteAttributes\Attributes\Route;

class TextController extends Controller
{
    /**
     * @param string[] $arrayOptionalVar
     */
    #[Route(['post'], 'post-route/{multipleVar}/{arrayOptionalVar}', middleware: ['api', 'auth:sanctum'])]
    #[ParamConverter('requestDto', JsonRequestDto::class, options: ['source' => 'all'])]
    #[Tag('tagFromAttribute')]
    #[Response(response: 300, description: 'response-from-attribute')]
    public function postAction(JsonRequestDto $requestDto, int|string|null $multipleVar, ?array $arrayOptionalVar = []): JsonResponseDto
    {
        return new JsonResponseDto(1, 'string', 1.1);
    }

    #[Route(['get'], 'get-route/{id}', middleware: ['api'])]
    #[ParamConverter('queryRequest', QueryRequest::class, options: ['source' => 'query'])]
    public function getAction(QueryRequest $queryRequest, int $id): JsonResponseDto
    {
        return new JsonResponseDto(1, 'string', 1.1);
    }
}
