<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Command;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\ConfigDto;
use Kr0lik\DtoToSwagger\Processor\AbstractProcessor;

class SwaggerGenerator extends Command
{
    protected $signature = 'swagger:generate {configKey=default}';

    protected $description = 'Swagger generate command';

    /**
     * @param array<string, ConfigDto> $configsPerKey
     */
    public function __construct(
        private AbstractProcessor $processor,
        private array $configsPerKey,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        $config = $this->getConfig();

        $openApi = $this->processor->process($config);

        $openApi->saveAs($config->savePath);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getConfig(): ConfigDto
    {
        /** @var string $key */
        $key = $this->argument('configKey');

        if (!array_key_exists($key, $this->configsPerKey)) {
            throw new InvalidArgumentException("Swagger config key '{$key}' not exist. Available keys: ".implode(',', array_keys($this->configsPerKey)));
        }

        return $this->configsPerKey[$key];
    }
}
