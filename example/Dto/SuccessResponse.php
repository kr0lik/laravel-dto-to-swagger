<?php

declare(strict_types=1);

namespace Example\Dto;

use OpenApi\Attributes\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Response(response: 200, description: 'basic-response', ref: '#/components/schemas/JsonResponse')]
class SuccessResponse extends JsonResponse
{
}
