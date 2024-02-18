<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Context
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        readonly array $context = [],
    ) {}
}
