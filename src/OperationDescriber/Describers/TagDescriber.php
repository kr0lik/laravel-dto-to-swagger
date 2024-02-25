<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Tag;
use ReflectionMethod;

class TagDescriber implements OperationDescriberInterface
{
    public const DEFAULT_TAGS_CONTEXT = 'default_tags';

    public function __construct(
        private bool $tagFromControllerName,
        private bool $tagFromActionFolder,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        if (
            array_key_exists(self::DEFAULT_TAGS_CONTEXT, $context)
            && is_array($context[self::DEFAULT_TAGS_CONTEXT])
            && [] !== $context[self::DEFAULT_TAGS_CONTEXT]
        ) {
            Util::merge($operation, ['tags' => $context[self::DEFAULT_TAGS_CONTEXT]]);
        }

        $this->addTagFromControllerName($operation, $reflectionMethod);
        $this->addTagFromActionFolder($operation, $reflectionMethod);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
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
        if (!$this->tagFromControllerName) {
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
    private function addTagFromActionFolder(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        if (!$this->tagFromActionFolder) {
            return;
        }

        if ('__invoke' !== $reflectionMethod->getName()) {
            return;
        }

        $pathParts = explode('/', str_replace('\\', '/', $reflectionMethod->getDeclaringClass()->getName()));

        $name = $pathParts[count($pathParts) - 1];

        Util::merge($operation, ['tags' => [$name]]);
    }
}
