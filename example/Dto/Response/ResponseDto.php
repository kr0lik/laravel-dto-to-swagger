<?php

declare(strict_types=1);

namespace App\Dto\Response;

use DateTimeImmutable;
use Kr0lik\DtoToSwagger\Attribute\Context;
use Kr0lik\DtoToSwagger\Attribute\Wrap;
use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;
use OpenApi\Attributes\Property;
use Spatie\LaravelData\Data;

#[Wrap(ref: '#/components/schemas/JsonResponse', to: 'data', properties: [
    'success' => ['default' => true],
    'message' => ['default' => 'success'],
    'errors' => ['default' => null],
])]
final class ResponseDto extends Data implements JsonResponseInterface
{
    public function __construct(
        /** Some Description */
        readonly int $int,
        #[Property(description: 'some description', example: ['string', 'string2'])]
        readonly string $string,
        #[Context(format: 'date')]
        readonly DateTimeImmutable $dateTime,
    ) {
    }
}
