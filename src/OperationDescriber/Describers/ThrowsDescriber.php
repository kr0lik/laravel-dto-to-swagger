<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use ArgumentCountError;
use Kr0lik\DtoToSwagger\Attribute\Wrap;
use Kr0lik\DtoToSwagger\Contract\JsonErrorInterface;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use OpenApi\Annotations\JsonContent;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Response;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class ThrowsDescriber implements OperationDescriberInterface
{
    public function __construct(
        private PropertyTypeDescriber $propertyDescriber,
        private PhpDocReader $phpDocReader,
    ) {}

    /**
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $throws = $this->phpDocReader->getThrows($reflectionMethod);

        foreach ($throws as $throwClassName) {
            $reflectionClass = new ReflectionClass($throwClassName);

            $this->addFromAttributes($operation, $reflectionClass);
            $this->addFromInterface($operation, $reflectionClass);
        }
    }

    private function addFromAttributes(Operation $operation, ReflectionClass $reflectionClass): void
    {
        foreach (ClassHelper::getAttributesRecursively($reflectionClass) as $reflectionAttribute) {
            $attributeInstance = $reflectionAttribute->newInstance();

            if ($attributeInstance instanceof Wrap) {
                $this->addFromWrap($operation, $attributeInstance, $reflectionClass);

                return;
            }

            if ($attributeInstance instanceof Response) {
                $this->addFromResponse($operation, $attributeInstance);

                return;
            }
        }
    }

    private function addFromInterface(Operation $operation, ReflectionClass $reflectionClass): void
    {
        $description = $this->getMessageFromException($reflectionClass);
        $code = $this->getCodeFromException($reflectionClass);

        if (null === $code || null === $description) {
            return;
        }

        Util::createCollectionItem($operation, 'responses', Response::class, [
            'response' => $code, 'description' => $description,
        ]);
    }

    private function addFromResponse(Operation $operation, Response $attributeInstance): void
    {
        $response = Util::getCollectionItem($operation, Response::class, [
            'response' => $attributeInstance->response,
        ]);
        Util::merge($response, $attributeInstance);
    }

    private function addFromWrap(Operation $operation, Wrap $attributeInstance, ReflectionClass $reflectionClass): void
    {
        $jsonContent = new JsonContent([]);

        $this->propertyDescriber->describe($jsonContent, []);

        $this->wrapResponse($jsonContent, $attributeInstance);

        $description = $this->getMessageFromException($reflectionClass);
        $code = $this->getCodeFromException($reflectionClass);

        if (null === $code || null === $description) {
            return;
        }

        $response = Util::getCollectionItem($operation, Response::class, [
            'response' => $code, 'description' => $description,
        ]);

        Util::merge($response, [
            'content' => [
                'application/json' => [
                    'schema' => $jsonContent,
                ],
            ],
        ], true);
    }

    private function wrapResponse(JsonContent &$jsonContent, Wrap $wrap): void
    {
        $properties = $wrap->properties;

        $jsonContent = new JsonContent([
            'allOf' => [
                ['$ref' => $wrap->ref],
                ['properties' => $properties],
            ],
        ]);
    }

    private function getMessageFromException(ReflectionClass $reflectionClass): ?string
    {
        try {
            $exceptionInstance = $reflectionClass->newInstance();
            /** @phpstan-ignore-next-line */
        } catch (ArgumentCountError|ReflectionException) {
            return null;
        }

        assert($exceptionInstance instanceof JsonErrorInterface);

        return $exceptionInstance->getMessage();
    }

    private function getCodeFromException(ReflectionClass $reflectionClass): ?int
    {
        try {
            $exceptionInstance = $reflectionClass->newInstance();
            /** @phpstan-ignore-next-line */
        } catch (ArgumentCountError|ReflectionException) {
            return null;
        }

        assert($exceptionInstance instanceof JsonErrorInterface);

        return $exceptionInstance->getCode();
    }
}
