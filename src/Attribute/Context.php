<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Attribute;

use Attribute;
use JsonSerializable;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Context implements JsonSerializable
{
    public function __construct(
        readonly ?string $format = null,
        readonly ?string $pattern = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this));
    }
}
