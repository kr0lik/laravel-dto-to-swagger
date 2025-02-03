<?php

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Attribute\Name;
use Kr0lik\DtoToSwagger\Contract\QueryRequestInterface;

class SortDto implements QueryRequestInterface
{
    public function __construct(
        #[Name('sort[field]')]
        public readonly string $field,

        #[Name('sort[direction]')]
        public readonly string $direction,
    ) {}
}