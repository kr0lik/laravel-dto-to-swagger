<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Attribute;

use Attribute;
use OpenApi\Attributes\Property;

#[Attribute(Attribute::TARGET_CLASS)]
class Wrap
{
    /**
     * @param array<string, Property> $properties
     */
    public function __construct(
        readonly string $ref,
        readonly string $to,
        readonly array $properties = [],
    ) {}
}
