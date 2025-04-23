<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer;

use phpDocumentor\Reflection\Type as DocType;
use Symfony\Component\PropertyInfo\Type;

class DocTypePreparer
{
    /** @var DocTypePreparerInterface[] */
    private array $tagPreparers = [];

    /**
     * @param array<string, mixed> $context
     *
     * @return Type[]
     */
    public function prepare(DocType $docType, array $context = []): array
    {
        foreach ($this->tagPreparers as $tagPreparer) {
            if ($tagPreparer->supports($docType)) {
                return $tagPreparer->prepare($docType, $context);
            }
        }

        return [];
    }

    public function addPreparer(DocTypePreparerInterface $tagPreparer): void
    {
        $this->tagPreparers[] = $tagPreparer;
    }
}
