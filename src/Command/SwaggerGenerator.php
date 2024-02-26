<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Command;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Processor\RoutingProcessor;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use ReflectionException;
use RuntimeException;

class SwaggerGenerator extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Command description';

    public function __construct(
        private OpenApiRegister $openApiRegister,
        private RoutingProcessor $routingProcessor,
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
        $openApi = $this->openApiRegister->initOpenApi();

        $this->routingProcessor->process($openApi);

        $openApi->saveAs($this->savePath);
    }
}
