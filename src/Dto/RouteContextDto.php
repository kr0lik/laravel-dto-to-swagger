<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Dto;

readonly class RouteContextDto
{
    /**
     * @param array<string, array<string, string>> $inPathParametersPerName
     * @param array<array<string, array<mixed>>>   $defaultSecurities
     * @param string[]                             $defaultTags
     */
    public function __construct(
        public array $inPathParametersPerName = [],
        public array $defaultSecurities = [],
        public array $defaultTags = [],
    ) {}
}
