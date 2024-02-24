<?php

declare(strict_types=1);

namespace Example\Dto\Request;

use Kr0lik\DtoToSwagger\Contract\QueryRequestInterface;

class QueryRequest implements QueryRequestInterface
{
    public function __construct(
        readonly int $page,
        readonly int $perPage = 20,
    )
    {
    }
}
