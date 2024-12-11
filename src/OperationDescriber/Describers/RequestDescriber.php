<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\ObjectDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Trait\IsRequiredTrait;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\RequestBody;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes\Parameter;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

class RequestDescriber implements OperationDescriberInterface
{
    use IsRequiredTrait;

    /**
     * @param array<int, array<string, mixed>> $requestErrorResponseSchemas
     */
    public function __construct(
        private PropertyTypeDescriber $propertyDescriber,
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
        $this->addFromAttributes($operation, $reflectionMethod);

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $types) {
            if (1 === count($types) && is_subclass_of($types[0]->getClassName(), JsonRequestInterface::class)) {
                $jsonContent = new Schema([]);

                $context[ObjectDescriber::SKIP_ATTRIBUTES_CONTEXT] = [Parameter::class];

                if (null === $this->fileUploadType || '' === $this->fileUploadType) {
                    $context[ObjectDescriber::SKIP_TYPES_CONTEXT] = [$this->fileUploadType];
                }

                $this->propertyDescriber->describe($jsonContent, $context, ...$types);

                if ($this->isNotEmpty($jsonContent)) {
                    $request = Util::getChild($operation, RequestBody::class);

                    Util::merge($request, [
                        'content' => [
                            'application/json' => [
                                'schema' => $jsonContent,
                            ],
                        ],
                    ], true);
                }

                if ([] !== $this->requestErrorResponseSchemas) {
                    Util::merge($operation, ['responses' => $this->requestErrorResponseSchemas]);
                }

                $this->searchAndDescribeParameters($operation, $types[0]);
                $this->searchAndDescribeFIleUploadType($operation, $types[0]);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addFromAttributes(Operation $operation, ReflectionMethod $reflectionMethod): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof RequestBody) {
                Util::createChild($operation, RequestBody::class, (array) $attributeInstance->jsonSerialize());
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function searchAndDescribeParameters(Operation $operation, Type $type): void
    {
        $class = $type->getClassName();

        if (null === $class) {
            return;
        }

        $reflectionClass = new ReflectionClass($class);

        foreach (ClassHelper::getVisiblePropertiesRecursively($reflectionClass) as $reflectionProperty) {
            foreach ($reflectionProperty->getAttributes() as $reflectionAttribute) {
                $attributeInstance = $reflectionAttribute->newInstance();

                if ($attributeInstance instanceof Parameter) {
                    $name = $attributeInstance->name;

                    if (Generator::UNDEFINED === $name || null === $name || '' === $name) {
                        $name = NameHelper::getName($reflectionProperty);
                    }

                    if (Generator::UNDEFINED === $attributeInstance->in || null === $attributeInstance->in || '' === $attributeInstance->in) {
                        $attributeInstance->in = QueryParameterDescriber::IN;
                    }

                    if ($this->isRequired($reflectionProperty)) {
                        $attributeInstance->required = true;
                    }

                    $newParameter = Util::getOperationParameter($operation, $name, $attributeInstance->in);
                    Util::merge($newParameter, $attributeInstance);

                    /** @var Schema $schema */
                    $schema = Util::getChild($newParameter, Schema::class);

                    $context = ContextHelper::getContext($reflectionProperty);

                    $this->propertyDescriber->describe($schema, $context, ...$this->reflectionPreparer->getTypes($reflectionProperty));
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function searchAndDescribeFIleUploadType(Operation $operation, Type $type): void
    {
        $fileUploadProperties = $this->searchFIleUploadProperties($type);

        if ([] === $fileUploadProperties) {
            return;
        }

        $uploadContent = new Schema([
            'type' => 'object',
            'properties' => $fileUploadProperties,
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

    /**
     * @throws ReflectionException
     *
     * @return array<string, array<string, mixed>>
     */
    private function searchFIleUploadProperties(Type $type): array
    {
        if (null === $this->fileUploadType || '' === $this->fileUploadType) {
            return [];
        }

        $class = $type->getClassName();

        if (null === $class) {
            return [];
        }

        $reflectionClass = new ReflectionClass($class);

        $fileUploadProperties = [];

        foreach (ClassHelper::getVisiblePropertiesRecursively($reflectionClass) as $reflectionProperty) {
            if (
                $reflectionProperty->getType() instanceof ReflectionNamedType
                && (
                    $reflectionProperty->getType()->getName() === $this->fileUploadType
                    || is_subclass_of($reflectionProperty->getType()->getName(), $this->fileUploadType)
                )
            ) {
                $name = NameHelper::getName($reflectionProperty);

                $context = ContextHelper::getContext($reflectionProperty);

                $fileUploadProperties[$name] = array_merge($context, ['type' => 'string']);
            }
        }

        return $fileUploadProperties;
    }

    private function isNotEmpty(Schema $schema): bool
    {
        return (Generator::UNDEFINED !== $schema->properties && [] !== $schema->properties)
            || Generator::UNDEFINED !== $schema->ref;
    }
}
