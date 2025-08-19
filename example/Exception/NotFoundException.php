<?php

namespace App\Exception;

use Exception;
use Kr0lik\DtoToSwagger\Attribute\Wrap;
use Kr0lik\DtoToSwagger\Contract\JsonErrorInterface;
use OpenApi\Attributes\Property;

#[Wrap(
    ref: '#/components/schemas/JsonResponse',
    properties: [
        'success' => new Property(default: false),
        'message' => new Property(default: 'not found'),
    ],
)]
class NotFoundException extends Exception implements JsonErrorInterface
{

}