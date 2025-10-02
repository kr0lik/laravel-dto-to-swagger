<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use Kr0lik\DtoToSwagger\Dto\RouteContextDto;
use Kr0lik\DtoToSwagger\Helper\ContextHelper;
use Kr0lik\DtoToSwagger\Helper\NameHelper;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\ObjectDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
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
use Symfony\Component\PropertyInfo\Type;

class RequestDescriber implements OperationDescriberInterface
{
    use IsRequiredTrait;

    public function __construct(
        private OpenApiRegister $openApiRegister,
        private PropertyTypeDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, RouteContextDto $routeContext): void
    {
        $this->addFromAttributes($operation, $reflectionMethod);

        foreach ($this->reflectionPreparer->getArgumentTypes($reflectionMethod) as $types) {
            if (1 !== count($types) || null === $types[0]->getClassName() || !is_subclass_of($types[0]->getClassName(), JsonRequestInterface::class)) {
                continue;
            }

            $contentType = 'application/json';
            $mainSchema = new Schema([]);

            $context = [];
            $context[ObjectDescriber::SKIP_ATTRIBUTES_CONTEXT] = [Parameter::class];

            $fileUploadType = $this->openApiRegister->getConfig()->fileUploadType ?? '';

            if ('' !== $fileUploadType) {
                $context[ObjectDescriber::SKIP_TYPES_CONTEXT] = [$fileUploadType];
            }

            $this->propertyDescriber->describe($mainSchema, $context, ...$types);

            $fileUploadProperties = $this->searchFileUploadProperties($types[0]);

            if ([] !== $fileUploadProperties['properties']) {
                $contentType = 'multipart/form-data';

                $multipartSchema = new Schema([
                    'type' => 'object',
                    ...$fileUploadProperties
                ]);

                if ($this->isNotEmpty($mainSchema)) {
                    $mainSchema = new Schema(['allOf' => [
                        $mainSchema,
                        $multipartSchema,
                    ]]);
                } else {
                    $mainSchema = $multipartSchema;
                }
            }

            if ($this->isNotEmpty($mainSchema)) {
                $request = Util::getChild($operation, RequestBody::class);

                Util::merge($request, [
                    'content' => [
                        $contentType => [
                            'schema' => $mainSchema,
                        ],
                    ],
                ], true);
            }

            $requestErrorResponseSchemas = $this->openApiRegister->getConfig()->requestErrorResponseSchemas ?? [];

            if ([] !== $requestErrorResponseSchemas) {
                Util::merge($operation, ['responses' => $requestErrorResponseSchemas]);
            }

            $this->searchAndDescribeParameters($operation, $types[0]);
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
                $attributeData = (array) $attributeInstance->jsonSerialize();

                if ([] === $attributeData) {
                    return;
                }

                Util::createChild($operation, RequestBody::class, $attributeData);
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

                if (!$attributeInstance instanceof Parameter) {
                    continue;
                }

                $name = $attributeInstance->name;

                if (Generator::UNDEFINED === $name || '' === $name) {
                    $name = NameHelper::getName($reflectionProperty);
                }

                if (Generator::UNDEFINED === $attributeInstance->in || null === $attributeInstance->in || '' === $attributeInstance->in) {
                    $attributeInstance->in = QueryParameterDescriber::IN;
                }

                /** @var bool|string $attributeInstanceRequired */
                $attributeInstanceRequired = $attributeInstance->required;

                if (Generator::UNDEFINED === $attributeInstanceRequired && $this->isRequired($reflectionProperty)) {
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

    /**
     * @throws ReflectionException
     *
     * @return array<string, array<string, mixed>>
     */
    private function searchFileUploadProperties(Type $type): array
    {
        $fileUploadType = $this->openApiRegister->getConfig()->fileUploadType ?? '';

        if ('' === $fileUploadType) {
            return [];
        }

        $class = $type->getClassName();

        if (null === $class) {
            return [];
        }

        $reflectionClass = new ReflectionClass($class);

        $fileUploadProperties = [];

        $requiredProperties = [];

        foreach (ClassHelper::getVisiblePropertiesRecursively($reflectionClass) as $reflectionProperty) {
            if ($this->isRequired($reflectionProperty)) {
                $requiredProperties[] = $reflectionProperty->getName();
            }

            if (
                $reflectionProperty->getType() instanceof ReflectionNamedType
                && (
                    $reflectionProperty->getType()->getName() === $fileUploadType
                    || is_subclass_of($reflectionProperty->getType()->getName(), $fileUploadType)
                )
            ) {
                $name = NameHelper::getName($reflectionProperty);

                $context = ContextHelper::getContext($reflectionProperty);

                $fileUploadProperties[$name] = array_merge($context, ['type' => 'string', 'format' => 'binary']);
            }
        }

        return ['properties' => $fileUploadProperties, 'required' => $requiredProperties];
    }

    private function isNotEmpty(Schema $schema): bool
    {
        /** @var string|array<Schema> $allOf */
        $allOf = $schema->allOf;

        return (Generator::UNDEFINED !== $schema->properties && [] !== $schema->properties)
            || Generator::UNDEFINED !== $schema->ref
            || Generator::UNDEFINED !== $allOf;
    }
}
