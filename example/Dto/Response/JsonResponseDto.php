<?php

declare(strict_types=1);

namespace Example\Dto\Response;

use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;
use OpenApi\Attributes\Property;
use Spatie\LaravelData\Data;

final class JsonResponseDto extends Data implements JsonResponseInterface
{
    public function __construct(
        /** Some Description */
        readonly int $int,
        #[Property(description: 'some description', example: ['string', 'string2'])]
        readonly string $string,
        readonly float $float,
    ) {
    }
}
