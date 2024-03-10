<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Attribute\Wrap;
use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\JsonContent;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Response;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response as ApiResponse;
use Symfony\Component\PropertyInfo\Type;

class ResponseDescriber implements OperationDescriberInterface
{
    /**
     * @param array<int, array<string, mixed>> $defaultErrorResponseSchemas
     */
    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
        private array $defaultErrorResponseSchemas,
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

        if ([] !== $this->defaultErrorResponseSchemas) {
            Util::merge($operation, ['responses' => $this->defaultErrorResponseSchemas]);
        }

        $returnTypes = $this->reflectionPreparer->getReturnTypes($reflectionMethod);

        if (null === $returnTypes) {
            return;
        }

        foreach ($returnTypes as $returnType) {
            if (is_subclass_of($returnType->getClassName(), JsonResponseInterface::class)) {
                $this->addResponseFromObject($operation, $returnType);

                continue;
            }

            if (null === $returnType->getClassName()) {
                continue;
            }

            $reflectionClass = new ReflectionClass($returnType->getClassName());

            foreach (ClassHelper::getAllAttributes($reflectionClass) as $reflectionAttribute) {
                $attributeInstance = $reflectionAttribute->newInstance();

                if ($attributeInstance instanceof Response) {
                    $response = Util::getCollectionItem($operation, Response::class, [
                        'response' => $attributeInstance->response,
                    ]);
                    Util::merge($response, $attributeInstance);
                }
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

            if ($attributeInstance instanceof Response) {
                Util::createCollectionItem($operation, 'responses', Response::class, (array) $attributeInstance->jsonSerialize());
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function addResponseFromObject(Operation $operation, Type $returnType): void
    {
        assert(is_string($returnType->getClassName()));

        $reflectionClass = new ReflectionClass($returnType->getClassName());

        $jsonContent = new JsonContent([]);

        $this->propertyDescriber->describe($jsonContent, [], $returnType);

        foreach (ClassHelper::getAllAttributes($reflectionClass) as $reflectionAttribute) {
            $attributeInstance = $reflectionAttribute->newInstance();

            if ($attributeInstance instanceof Wrap) {
                $this->wrapResponse($jsonContent, $attributeInstance);

                break;
            }
        }

        $response = Util::getCollectionItem($operation, Response::class, [
            'response' => ApiResponse::HTTP_OK, 'description' => 'Success',
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

        $properties[$wrap->to] = $jsonContent;

        $jsonContent = new JsonContent([
            'allOf' => [
                ['$ref' => $wrap->ref],
                ['properties' => $properties],
            ],
        ]);
    }
}
