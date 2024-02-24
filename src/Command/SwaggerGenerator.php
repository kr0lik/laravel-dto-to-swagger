<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Command;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Processor\RoutingProcessor;
use Kr0lik\DtoToSwagger\Register\SchemaRegister;
use OpenApi\Annotations\OpenApi;
use ReflectionException;
use RuntimeException;

class SwaggerGenerator extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Command description';

    /**
     * @param array<string, mixed> $baseOpenApiConfig
     */
    public function __construct(
        private RoutingProcessor $routingProcessor,
        private SchemaRegister $schemaRegister,
        private array $baseOpenApiConfig,
        private string $savePath,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function handle(): void
    {
        $openApi = $this->initOpenApi();

        $this->routingProcessor->process($openApi);

        foreach ($this->schemaRegister->getNamedSchemas() as $name => $schema) {
            /** @phpstan-ignore-next-line */
            $openApi->components['schemas'][$name] = $schema;
        }

        $openApi->saveAs($this->savePath);
    }

    private function initOpenApi(): OpenApi
    {
        return new OpenApi($this->baseOpenApiConfig);
    }
}
