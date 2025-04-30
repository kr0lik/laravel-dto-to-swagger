<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparerInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Helper\NamespaceHelper;
use phpDocumentor\Reflection\Type as DocType;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionException;
use RuntimeException;
use Symfony\Component\PropertyInfo\Type;

class ObjectDocTypePreparer implements DocTypePreparerInterface
{
    public const SOURCE_CLASS_CONTEXT = 'sourceClass';

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     *
     * @return Type[]
     */
    public function prepare(DocType $docType, array $context = []): array
    {
        assert($docType instanceof Object_);

        $objectType = $docType->getFqsen();

        if (null === $objectType) {
            return [];
        }

        if (array_key_exists(self::SOURCE_CLASS_CONTEXT, $context)) {
            $objectType = NamespaceHelper::getNameSpace($context[self::SOURCE_CLASS_CONTEXT], $objectType->getName());
        }

        $propertyInfo = new Type(Type::BUILTIN_TYPE_OBJECT, false, (string) $objectType);

        return [$propertyInfo];
    }

    public function supports(DocType $docType): bool
    {
        return $docType instanceof Object_;
    }
}
