<?php

declare(strict_types=1);

namespace Example\Dto\Request;

final readonly class SubDto
{
    public function __construct(
        /** Some Description */
        public ?string $stringNullable,
        public int|float|null $intFloatNullable,
        public ?object $objectNullableOptional = null,
    ) {
    }
}
