<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Name
{
    public function __construct(
        readonly string $name,
    ) {}
}
