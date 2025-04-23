<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Contract\HeaderRequestInterface;
use OpenApi\Attributes\Parameter;

final class HeadRequestDto implements HeaderRequestInterface
{
    public function __construct(
        #[Parameter(name: 'X-DEVICE-ID')]
        readonly string $deviceId,
    ) {}
}
