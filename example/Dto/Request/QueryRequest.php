<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Contract\QueryRequestInterface;
use OpenApi\Attributes\Parameter;

class QueryRequest implements QueryRequestInterface
{
    public function __construct(
        readonly int $page,
        #[Parameter(name: 'per-page')]
        readonly int $perPage = 20,
    ) {
    }
}
