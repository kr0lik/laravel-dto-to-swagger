<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\RequestBody;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes\Parameter;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\PropertyInfo\Type;

class RequestDescriber implements OperationDescriberInterface
{
    /**
     * @param array<int, array<string, mixed>> $requestErrorResponseSchemas
     */
    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
        private array $requestErrorResponseSchemas,
        private ?string $fileUploadType,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof RequestBody) {
                Util::createChild($operation, RequestBody::class, (array) $attributeInstance->jsonSerialize());
            }
        }

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $types) {
            if (1 === count($types) && is_subclass_of($types[0]->getClassName(), JsonRequestInterface::class)) {
                $jsonContent = new Schema([]);

                $this->propertyDescriber->describe($jsonContent, ...$types);

                $request = Util::getChild($operation, RequestBody::class);

                Util::merge($request, [
                    'content' => [
                        'application/json' => [
                            'schema' => $jsonContent,
                        ],
                    ],
                ], true);

                if ([] !== $this->requestErrorResponseSchemas) {
                    Util::merge($operation, ['responses' => $this->requestErrorResponseSchemas]);
                }

                $this->searchParameters($operation, $types[0]);
                $this->searchFIleUploadType($operation, $types[0]);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function searchParameters(Operation $operation, Type $type): void
    {
        $class = $type->getClassName();

        if (null === $class) {
            return;
        }

        $reflectionClass = new ReflectionClass($class);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($reflectionProperty->getAttributes() as $reflectionAttribute) {
                $attributeInstance = $reflectionAttribute->newInstance();

                if ($attributeInstance instanceof Parameter) {
                    $newParameter = Util::getOperationParameter($operation, $reflectionProperty->getName(), $attributeInstance->in);
                    Util::merge($newParameter, $attributeInstance);

                    /** @var Schema $schema */
                    $schema = Util::getChild($newParameter, Schema::class);

                    $this->propertyDescriber->describe($schema, ...$this->reflectionPreparer->getTypes($reflectionProperty));
                }
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function searchFIleUploadType(Operation $operation, Type $type): void
    {
        if (null === $this->fileUploadType || '' === $this->fileUploadType) {
            return;
        }

        $class = $type->getClassName();

        if (null === $class) {
            return;
        }

        $reflectionClass = new ReflectionClass($class);

        $fileUploadProperties = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (
                $reflectionProperty->getType() instanceof ReflectionNamedType
                && (
                    $reflectionProperty->getType()->getName() === $this->fileUploadType
                    || is_subclass_of($reflectionProperty->getType()->getName(), $this->fileUploadType)
                )
            ) {
                $fileUploadProperties[] = $reflectionProperty->getName();
            }
        }

        if ([] === $fileUploadProperties) {
            return;
        }

        $uploadContent = new Schema([
            'type' => 'object',
            'properties' => array_map(static fn (): array => ['type' => 'string', 'format' => 'binary'], array_flip($fileUploadProperties)),
        ]);

        $request = Util::getChild($operation, RequestBody::class);

        Util::merge($request, [
            'content' => [
                'multipart/form-data' => [
                    'schema' => $uploadContent,
                ],
            ],
        ]);
    }
}
