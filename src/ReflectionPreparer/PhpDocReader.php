<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer;

use Kr0lik\DtoToSwagger\Contract\JsonErrorInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\ObjectDocTypePreparer;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type as DocType;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

class PhpDocReader
{
    public function __construct(
        private DocTypePreparer $docTypePreparer,
        private DocBlockFactory $docBlockFactory,
        private ContextFactory $contextFactory,
    ) {}

    public function getDescription(ReflectionMethod|ReflectionProperty $reflection): string
    {
        $docBlock = $this->getDockBLock($reflection);

        $description = $docBlock->getSummary();

        return trim($description);
    }

    /**
     * @return string[]
     */
    public function getThrows(ReflectionMethod|ReflectionProperty $reflection): array
    {
        $docBlock = $this->getDockBLock($reflection, true);

        $tags = $docBlock->getTagsWithTypeByName('throws');
        $result = [];

        foreach ($tags as $tag) {
            $throwClassName = (string) $tag->getType();

            if (class_exists($throwClassName)
                && is_array(class_implements($throwClassName))
                && in_array(JsonErrorInterface::class, class_implements($throwClassName), true)
            ) {
                $result[] = trim($throwClassName, '\\');
            }
        }

        return $result;
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
     * @return Type[]
     */
    public function getPropertyType(ReflectionProperty $reflectionProperty): array
    {
        $docBlock = $this->getDockBLock($reflectionProperty);

        foreach ($docBlock->getTags() as $tag) {
            if (!$tag instanceof Var_) {
                continue;
            }

            $tagType = $tag->getType();

            assert($tagType instanceof DocType);

            return $this->docTypePreparer->prepare($tagType, [
                ObjectDocTypePreparer::SOURCE_CLASS_CONTEXT => $reflectionProperty->getDeclaringClass()->getName(),
            ]);
        }

        $constructor = $reflectionProperty->getDeclaringClass()->getConstructor();

        if (null === $constructor) {
            return [];
        }

        foreach ($this->getNamedParamTypes($constructor) as $name => $types) {
            if ($name === $reflectionProperty->getName()) {
                return $types;
            }
        }

        return [];
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

            if (null === $tag->getVariableName()) {
                continue;
            }

            $tagType = $tag->getType();

            assert($tagType instanceof DocType);

            $result[$tag->getVariableName()] = $this->docTypePreparer->prepare($tagType, [
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

            $tagType = $tag->getType();

            assert($tagType instanceof DocType);

            return $this->docTypePreparer->prepare($tagType, [
                ObjectDocTypePreparer::SOURCE_CLASS_CONTEXT => $reflectionMethod->getDeclaringClass()->getName(),
            ]);
        }

        return null;
    }

    private function getDockBLock(ReflectionClass|ReflectionMethod|ReflectionProperty $reflection, bool $withContext = false): DocBlock
    {
        $context = null;

        if ($withContext) {
            $context = $this->getContext($reflection);
        }

        if (false === $reflection->getDocComment()) {
            return new DocBlock('', null, [], $context);
        }

        return $this->docBlockFactory->create($reflection, $context);
    }

    private function getContext(ReflectionClass|ReflectionMethod|ReflectionProperty $reflection): Context
    {
        if ($reflection instanceof ReflectionClass) {
            return $this->contextFactory->createFromReflector($reflection);
        }

        return $this->contextFactory->createFromReflector($reflection->getDeclaringClass());
    }
}
