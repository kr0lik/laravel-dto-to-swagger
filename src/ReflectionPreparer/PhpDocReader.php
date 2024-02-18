<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer;

use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\ObjectDocTypePreparer;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

class PhpDocReader
{
    public function __construct(
        private DocTypePreparer $docTypePreparer,
        private DocBlockFactory $docBlockFactory,
    ) {}

    public function getDescription(ReflectionMethod|ReflectionProperty $reflection): string
    {
        $docBlock = $this->getDockBLock($reflection);

        $description = $docBlock->getSummary();

        return trim($description);
    }

    public function isDeprecated(ReflectionMethod|ReflectionProperty $reflection): bool
    {
        $docBlock = $this->getDockBLock($reflection);

        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof Deprecated) {
                return true;
            }
        }

        $docBlock = $this->getDockBLock($reflection->getDeclaringClass());

        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof Deprecated) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, Type[]>
     */
    public function getNamedParamTypes(ReflectionMethod $reflectionMethod): array
    {
        $docBlock = $this->getDockBLock($reflectionMethod);

        $result = [];

        foreach ($docBlock->getTags() as $tag) {
            if (!$tag instanceof Param) {
                continue;
            }

            $result[$tag->getVariableName()] = $this->docTypePreparer->prepare($tag->getType(), [
                ObjectDocTypePreparer::SOURCE_CLASS_CONTEXT => $reflectionMethod->getDeclaringClass()->getName(),
            ]);
        }

        return $result;
    }

    /**
     * @return Type[]|null
     */
    public function getReturnTypes(ReflectionMethod $reflectionMethod): ?array
    {
        $docBlock = $this->getDockBLock($reflectionMethod);

        foreach ($docBlock->getTags() as $tag) {
            if (!$tag instanceof Return_) {
                continue;
            }

            return $this->docTypePreparer->prepare($tag->getType(), [
                ObjectDocTypePreparer::SOURCE_CLASS_CONTEXT => $reflectionMethod->getDeclaringClass()->getName(),
            ]);
        }

        return null;
    }

    private function getDockBLock(ReflectionClass|ReflectionMethod|ReflectionProperty $reflection): DocBlock
    {
        if (false === $reflection->getDocComment()) {
            return new DocBlock();
        }

        return $this->docBlockFactory->create($reflection);
    }
}
