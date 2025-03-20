<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Processor;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\ConfigDto;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use OpenApi\Annotations\OpenApi;

abstract class AbstractProcessor
{
    public function __construct(
        protected OpenApiRegister $openApiRegister,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function process(ConfigDto $config): OpenApi
    {
        $this->openApiRegister->initOpenApi($config);

        $this->prepare();

        return $this->openApiRegister->getOpenApi();
    }

    abstract protected function prepare(): void;
}
