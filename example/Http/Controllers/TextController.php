<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\Request\HeadRequestDto;
use App\Dto\Request\QueryRequest;
use App\Dto\Request\RequestDto;
use App\Dto\Response\ResponseDto;
use DateTimeImmutable;
use Illuminate\Routing\Controller;
use Kr0lik\ParamConverter\Annotation\ParamConverter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Tag;
use Spatie\RouteAttributes\Attributes\Route;
use Spatie\RouteAttributes\Attributes\WhereUlid;

class TextController extends Controller
{
    /**
     * @param string[] $arrayOptionalVar
     */
    #[Route(['post'], 'post-route/{multipleVar}/{arrayOptionalVar}', middleware: ['api', 'auth:sanctum'])]
    #[ParamConverter('requestDto', RequestDto::class, options: ['source' => 'all'])]
    #[Tag('tagFromAttribute')]
    #[Response(response: 300, description: 'response-from-attribute')]
    public function postAction(RequestDto $requestDto, int|string|null $multipleVar, ?array $arrayOptionalVar = []): ResponseDto
    {
        return new ResponseDto(1, 'string', new DateTimeImmutable);
    }

    #[Route(['get'], 'get-route/{uuid}', middleware: ['api'])]
    #[WhereUlid('uuid')]
    #[ParamConverter('queryRequest', QueryRequest::class, options: ['source' => 'query'])]
    public function getAction(QueryRequest $queryRequest, HeadRequestDto $headers, string $uuid): ResponseDto
    {
        return new ResponseDto(1, 'string', new DateTimeImmutable);
    }
}
