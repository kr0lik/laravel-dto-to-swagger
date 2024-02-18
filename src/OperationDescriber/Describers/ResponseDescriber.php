<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\OperationDescriber\Describers;

use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriber;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use OpenApi\Annotations\JsonContent;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Response;
use OpenApi\Annotations\Schema;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response as ApiResponse;
use Symfony\Component\PropertyInfo\Type;

class ResponseDescriber implements OperationDescriberInterface
{
    /**
     * @param array<string, mixed>             $wrapSuccessResponsesToSchema
     * @param array<int, array<string, mixed>> $defaultErrorResponseSchemas
     */
    public function __construct(
        private PropertyDescriber $propertyDescriber,
        private ReflectionPreparer $reflectionPreparer,
        private array $wrapSuccessResponsesToSchema,
        private array $defaultErrorResponseSchemas,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function describe(Operation $operation, ReflectionMethod $reflectionMethod, array $context = []): void
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Response) {
                Util::createCollectionItem($operation, 'responses', Response::class, (array) $attributeInstance->jsonSerialize());
            }
        }

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

            foreach ($reflectionClass->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();

                if ($attributeInstance instanceof Response) {
                    Util::createCollectionItem($operation, 'responses', Response::class, (array) $attributeInstance->jsonSerialize());
                }
            }
        }
    }

    private function addResponseFromObject(Operation $operation, Type $returnType): void
    {
        $jsonContent = new JsonContent([]);

        if ([] !== $this->wrapSuccessResponsesToSchema) {
            $this->wrapResponse($jsonContent, $returnType);
        } else {
            $this->propertyDescriber->describe($jsonContent, $returnType);
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

    private function wrapResponse(JsonContent $jsonContent, Type $returnType): void
    {
        $properties = $this->wrapSuccessResponsesToSchema['properties'];

        $to = $this->wrapSuccessResponsesToSchema['to'];

        $newSchema = new Schema([]);

        $this->propertyDescriber->describe($newSchema, $returnType);

        $properties[$to] = $newSchema;

        Util::merge($jsonContent, [
            'allOf' => [
                ['$ref' => $this->wrapSuccessResponsesToSchema['ref']],
                ['properties' => $properties],
            ],
        ], true);
    }
}
