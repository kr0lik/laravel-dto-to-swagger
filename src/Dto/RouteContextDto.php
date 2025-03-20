<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Dto;

readonly class RouteContextDto
{
    /**
     * @param array<string, array<string, string>> $inPathParametersPerName
     */
    public function __construct(
        public array $inPathParametersPerName = [],
    ) {}
}
