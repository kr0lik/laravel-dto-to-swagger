<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\AttributeHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Tag;
use ReflectionException;
use ReflectionMethod;

class TagDescriber implements OperationDescriberInterface
{
    public function __construct(
        private OpenApiRegister $openApiRegister,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        if ([] !== $routeContext->defaultTags) {
            Util::merge($operation, ['tags' => $routeContext->defaultTags]);
        }

        $this->addTagFromControllerName($operation, $reflectionMethod);
        $this->addTagFromFolder($operation, $reflectionMethod);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach (AttributeHelper::getAttributes($reflectionMethod) as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Tag) {
                Util::merge($operation, ['tags' => [$instance->name]]);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addTagFromControllerName(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        if (!$this->openApiRegister->getConfig()->tagFromControllerName) {
            return;
        }

        if ('__invoke' === $reflectionMethod->getName()) {
            return;
        }

        $name = basename(str_replace('\\', '/', $reflectionMethod->getDeclaringClass()->getName()), 'Controller');

        Util::merge($operation, ['tags' => [$name]]);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addTagFromFolder(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        if (
            !($this->openApiRegister->getConfig()->tagFromActionFolder && '__invoke' === $reflectionMethod->getName())
            && !$this->openApiRegister->getConfig()->tagFromControllerFolder
        ) {
            return;
        }

        $pathParts = explode('/', str_replace('\\', '/', $reflectionMethod->getDeclaringClass()->getName()));

        $name = $pathParts[count($pathParts) - 1];

        Util::merge($operation, ['tags' => [$name]]);
    }
}
