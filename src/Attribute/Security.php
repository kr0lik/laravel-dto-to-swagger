<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Attribute;

use Attribute;
use OpenApi\Annotations\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Security extends AbstractAnnotation
{
    /** {@inheritdoc} */
    public static $_types = [
        'name' => 'string',
        'scopes' => '[string]',
    ];

    public static $_required = ['name'];

    /**
     * @param array<string, mixed> $properties
     * @param string[]             $scopes
     */
    public function __construct(
        public readonly array $properties = [],
        public readonly ?string $name = null,
        public readonly array $scopes = []
    ) {
        parent::__construct($properties + [
            'name' => $name,
            'scopes' => $scopes,
        ]);
    }
}
