<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use ArrayObject;
use InvalidArgumentException;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Context;
use OpenApi\Generator;

use function count;
use function get_class;
use function get_class_vars;
use function in_array;
use function is_a;
use function is_array;
use function is_string;
use function strtolower;

/**
 * This class acts as compatibility layer between NelmioApiDocBundle and swagger-php.
 *
 * It was written to replace the GuilhemN/swagger layer as a lower effort to maintain alternative.
 *
 * The main purpose of this class is to search for and create child Annotations
 * of swagger Annotation classes with the following convenience methods
 * to get or create the respective Annotation instances if not found
 *
 * @see Util::getPath
 * @see Util::getSchema
 * @see Util::getProperty
 * @see Util::getOperation
 * @see Util::getOperationParameter
 *
 * which in turn get or create the Annotation instances through the following more general methods
 * @see Util::getChild
 * @see Util::getCollectionItem
 * @see Util::getIndexedCollectionItem
 *
 * which then searches for an existing Annotation through
 * @see Util::searchCollectionItem
 * @see Util::searchIndexedCollectionItem
 *
 * and if not found the Annotation creates it through
 * @see Util::createCollectionItem
 * @see Util::createContext
 *
 * The merge method @see Util::merge has the main purpose to be able
 * to merge properties from an deeply nested array of Annotation properties in the structure of a
 * generated swagger json decoded array.
 */
final class Util
{
    /**
     * Return an existing PathItem object from $api->paths[] having its member path set to $path.
     * Create, add to $api->paths[] and return this new PathItem object and set the property if none found.
     *
     * @see PathItem::path
     * @see OpenApi
     *
     * @throws InvalidArgumentException
     */
    public static function getPath(OpenApi $api, string $path): PathItem
    {
        $result = self::getIndexedCollectionItem($api, PathItem::class, $path);

        assert($result instanceof PathItem);

        return $result;
    }

    /**
     * Return an existing Schema object from $api->components->schemas[] having its member schema set to $schema.
     * Create, add to $api->components->schemas[] and return this new Schema object and set the property if none found.
     *
     * @see Schema
     * @see Components
     *
     * @throws InvalidArgumentException
     */
    public static function getSchema(OpenApi $api, string $schemaClass): Schema
    {
        /** @var Components|string $components */
        $components = $api->components;

        if (! $components instanceof Components) {
            $api->components = new Components(['_context' => self::createWeakContext($api->_context)]);
        }

        $result = self::getIndexedCollectionItem($api->components, Schema::class, $schemaClass);

        assert($result instanceof Schema);

        return $result;
    }

    /**
     * Return an existing Property object from $schema->properties[]
     * having its member property set to $property.
     *
     * Create, add to $schema->properties[] and return this new Property object
     * and set the property if none found.
     *
     * @see Property
     * @see Schema
     *
     * @throws InvalidArgumentException
     */
    public static function getProperty(Schema $schema, string $property): Property
    {
        $result = self::getIndexedCollectionItem($schema, Property::class, $property);

        assert($result instanceof Property);

        return $result;
    }

    /**
     * Return an existing Operation from $path->{$method}
     * or create, set $path->{$method} and return this new Operation object.
     *
     * @see PathItem
     *
     * @throws InvalidArgumentException
     */
    public static function getOperation(PathItem $path, string $method): Operation
    {
        /** @var class-string<AbstractAnnotation> $className */
        $className = array_keys($path::$_nested, strtolower($method), true)[0];

        $result = self::getChild($path, $className, ['path' => $path->path]);

        assert($result instanceof Operation);

        return $result;
    }

    /**
     * Return an existing Parameter object from $operation->parameters[]
     * having its members name set to $name and in set to $in.
     *
     * Create, add to $operation->parameters[] and return
     * this new Parameter object and set its members if none found.
     *
     * @see Operation
     * @see Parameter
     *
     * @throws InvalidArgumentException
     */
    public static function getOperationParameter(Operation $operation, string $name, string $in): Parameter
    {
        $result = self::getCollectionItem($operation, Parameter::class, ['name' => $name, 'in' => $in]);

        assert($result instanceof Parameter);

        return $result;
    }

    /**
     * Return an existing nested Annotation from $parent->{$property} if exists.
     * Create, add to $parent->{$property} and set its members to $properties otherwise.
     *
     * $property is determined from $parent::$_nested[$class]
     * it is expected to be a string nested property.
     *
     * @see AbstractAnnotation
     *
     * @param class-string<AbstractAnnotation> $className
     * @param array<string, mixed>             $properties
     *
     * @throws InvalidArgumentException
     */
    public static function getChild(AbstractAnnotation $parent, string $className, array $properties = []): AbstractAnnotation
    {
        $nested = $parent::$_nested;
        $property = $nested[$className];

        if ($parent->{$property} === null || $parent->{$property} === Generator::UNDEFINED) {
            $parent->{$property} = self::createChild($parent, $className, $properties);
        }

        $result = $parent->{$property};

        assert($result instanceof AbstractAnnotation);

        return $result;
    }

    /**
     * Return an existing nested Annotation from $parent->{$collection}[]
     * having all $properties set to the respective values.
     *
     * Create, add to $parent->{$collection}[] and set its members
     * to $properties otherwise.
     *
     * $collection is determined from $parent::$_nested[$class]
     * it is expected to be a single value array nested Annotation.
     *
     * @see AbstractAnnotation
     *
     * @param class-string<AbstractAnnotation> $className
     * @param array<string, mixed>             $properties
     *
     * @throws InvalidArgumentException
     */
    public static function getCollectionItem(AbstractAnnotation $parent, string $className, array $properties = []): AbstractAnnotation
    {
        $key = null;
        $nested = $parent::$_nested;
        $collection = $nested[$className][0];

        if ($properties !== []) {
            $key = self::searchCollectionItem(
                $parent->{$collection} && $parent->{$collection} !== Generator::UNDEFINED ? $parent->{$collection} : [],
                $properties
            );
        }

        if ($key === null) {
            $key = self::createCollectionItem($parent, $collection, $className, $properties);
        }

        return $parent->{$collection}[$key];
    }

    /**
     * Return an existing nested Annotation from $parent->{$collection}[]
     * having its mapped $property set to $value.
     *
     * Create, add to $parent->{$collection}[] and set its member $property to $value otherwise.
     *
     * $collection is determined from $parent::$_nested[$class]
     * it is expected to be a double value array nested Annotation
     * with the second value being the mapping index $property.
     *
     * @see AbstractAnnotation
     *
     * @param class-string<AbstractAnnotation> $className
     *
     * @throws InvalidArgumentException
     */
    public static function getIndexedCollectionItem(AbstractAnnotation $parent, string $className, string $value): AbstractAnnotation
    {
        $nested = $parent::$_nested;
        /** @phpstan-ignore-next-line */
        [$collection, $property] = $nested[$className];

        $key = self::searchIndexedCollectionItem(
            $parent->{$collection} && $parent->{$collection} !== Generator::UNDEFINED ? $parent->{$collection} : [],
            $property,
            $value
        );

        if ($key === false) {
            $key = self::createCollectionItem($parent, $collection, $className, [$property => $value]);
        }

        return $parent->{$collection}[$key];
    }

    /**
     * Search for an Annotation within $collection that has all members set
     * to the respective values in the associative array $properties.
     *
     * @param array<string|int, AbstractAnnotation> $collection
     * @param array<string, mixed>                  $properties
     */
    public static function searchCollectionItem(array $collection, array $properties): null|int|string
    {
        foreach ($collection as $i => $child) {
            foreach ($properties as $k => $prop) {
                if ($child->{$k} !== $prop) {
                    continue 2;
                }
            }

            return $i;
        }

        return null;
    }

    /**
     * Search for an Annotation within the $collection that has its member $index set to $value.
     *
     * @param array<string|int, AbstractAnnotation> $collection
     */
    public static function searchIndexedCollectionItem(array $collection, int|string $member, string $value): false|int
    {
        return array_search($value, array_column($collection, $member), true);
    }

    /**
     * Create a new Object of $class with members $properties within $parent->{$collection}[]
     * and return the created index.
     *
     * @param class-string<AbstractAnnotation> $className
     * @param array<string, mixed>             $properties
     *
     * @throws InvalidArgumentException
     */
    public static function createCollectionItem(AbstractAnnotation $parent, string $collection, string $className, array $properties = []): int
    {
        if ($parent->{$collection} === Generator::UNDEFINED || $parent->{$collection} === null) {
            $parent->{$collection} = [];
        }

        $key = count($parent->{$collection});
        $parent->{$collection}[$key] = self::createChild($parent, $className, $properties);

        return $key;
    }

    /**
     * Create a new Object of $class with members $properties and set the context parent to be $parent.
     *
     * @param class-string<AbstractAnnotation> $className
     * @param array<string, mixed>             $properties
     *
     * @throws InvalidArgumentException at an attempt to pass in properties that are found in $parent::$_nested
     */
    public static function createChild(AbstractAnnotation $parent, string $className, array $properties = []): AbstractAnnotation
    {
        $nesting = self::getNestingIndexes($className);

        if (array_intersect(array_keys($properties), $nesting) !== []) {
            throw new InvalidArgumentException('Nesting Annotations is not supported.'.json_encode(array_intersect(array_keys($properties), $nesting)));
        }

        return new $className(
            array_merge($properties, ['_context' => self::createContext(['nested' => $parent], $parent->_context)])
        );
    }

    /**
     * Create a new Context with members $properties and parent context $parent.
     *
     * @see Context
     *
     * @param array<string, mixed> $properties
     */
    public static function createContext(array $properties = [], ?Context $parent = null): Context
    {
        return new Context($properties, $parent);
    }

    /**
     * Create a new Context by copying the properties of the parent, but without a reference to the parent.
     *
     * @see Context
     *
     * @param array<string, mixed> $additionalProperties
     */
    public static function createWeakContext(?Context $parent = null, array $additionalProperties = []): Context
    {
        $propsToCopy = [
            'version',
            'line',
            'character',
            'namespace',
            'class',
            'interface',
            'trait',
            'method',
            'property',
            'logger',
        ];
        $filteredProps = [];

        foreach ($propsToCopy as $prop) {
            $value = $parent->{$prop} ?? null;

            if ($value === null) {
                continue;
            }

            $filteredProps[$prop] = $value;
        }

        return new Context(array_merge($filteredProps, $additionalProperties));
    }

    /**
     * Merge $from into $annotation. $overwrite is only used for leaf scalar values.
     *
     * The main purpose is to create a Swagger Object from array config values
     * in the structure of a json serialized Swagger object.
     *
     * @param array<string, mixed>|ArrayObject<string, mixed>|AbstractAnnotation $from
     *
     * @throws InvalidArgumentException
     */
    public static function merge(AbstractAnnotation $annotation, AbstractAnnotation|array|ArrayObject $from, bool $overwrite = false): void
    {
        if (is_array($from)) {
            self::mergeFromArray($annotation, $from, $overwrite);
        } elseif (is_a($from, AbstractAnnotation::class)) {
            $json = json_encode($from);

            if ($json === false) {
                throw new InvalidArgumentException('Wrong from data for merge');
            }
            /** @var AbstractAnnotation $from */
            self::mergeFromArray($annotation, json_decode($json, true), $overwrite);
        } elseif (is_a($from, ArrayObject::class)) {
            /** @var ArrayObject<array-key, mixed> $from */
            self::mergeFromArray($annotation, $from->getArrayCopy(), $overwrite);
        }
    }

    /**
     * Get assigned property name for property schema.
     */
    public static function getSchemaPropertyName(Schema $schema, Schema $property): ?string
    {
        if ($schema->properties === Generator::UNDEFINED) {
            return null;
        }

        foreach ($schema->properties as $schemaProperty) {
            if ($schemaProperty === $property) {
                return $schemaProperty->property !== Generator::UNDEFINED ? $schemaProperty->property : null;
            }
        }

        return null;
    }

    /**
     * Helper method to modify an annotation value only if its value has not yet been set.
     */
    public static function modifyAnnotationValue(AbstractAnnotation $parameter, string $property, mixed $value): void
    {
        if (! Generator::isDefault($parameter->{$property})) {
            return;
        }

        $parameter->{$property} = $value;
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @throws InvalidArgumentException
     */
    private static function mergeFromArray(AbstractAnnotation $annotation, array $properties, bool $overwrite): void
    {
        $done = [];

        $defaults = get_class_vars(get_class($annotation));

        foreach ($annotation::$_nested as $className => $propertyName) {
            if (is_string($propertyName)) {
                if (array_key_exists($propertyName, $properties)) {
                    if (! is_bool($properties[$propertyName])) {
                        self::mergeChild($annotation, $className, $properties[$propertyName], $overwrite);
                    } elseif ($overwrite || $annotation->{$propertyName} === $defaults[$propertyName]) {
                        // Support for boolean values (for instance for additionalProperties)
                        $annotation->{$propertyName} = $properties[$propertyName];
                    }
                    $done[] = $propertyName;
                }
            } elseif (\array_key_exists($propertyName[0], $properties)) {
                $collection = $propertyName[0];
                $property = $propertyName[1] ?? null;
                self::mergeCollection($annotation, $className, $property, $properties[$collection], $overwrite);
                $done[] = $collection;
            }
        }

        foreach ($annotation::$_types as $propertyName => $type) {
            if (array_key_exists($propertyName, $properties)) {
                self::mergeTyped($annotation, $propertyName, $type, $properties, $defaults, $overwrite);
                $done[] = $propertyName;
            }
        }

        foreach ($properties as $propertyName => $value) {
            if ($propertyName === '$ref') {
                $propertyName = 'ref';
            }

            if (array_key_exists($propertyName, $defaults) && ! in_array($propertyName, $done, true)) {
                self::mergeProperty($annotation, $propertyName, $value, $defaults[$propertyName], $overwrite);
            }
        }
    }

    /**
     * @param class-string<AbstractAnnotation> $className
     *
     * @throws InvalidArgumentException
     */
    private static function mergeChild(AbstractAnnotation $annotation, string $className, mixed $value, bool $overwrite): void
    {
        self::merge(self::getChild($annotation, $className), $value, $overwrite);
    }

    /**
     * @param class-string<AbstractAnnotation>        $className
     * @param array<string|int, array<string, mixed>> $items
     *
     * @throws InvalidArgumentException
     */
    private static function mergeCollection(AbstractAnnotation $annotation, string $className, ?string $property, array $items, bool $overwrite): void
    {
        if ($property !== null) {
            foreach ($items as $prop => $value) {
                $child = self::getIndexedCollectionItem($annotation, $className, (string) $prop);
                self::merge($child, $value);
            }
        } else {
            $nesting = self::getNestingIndexes($className);

            foreach ($items as $props) {
                $create = [];
                $merge = [];

                foreach ($props as $k => $v) {
                    if (in_array($k, $nesting, true)) {
                        $merge[$k] = $v;
                    } else {
                        $create[$k] = $v;
                    }
                }
                self::merge(self::getCollectionItem($annotation, $className, $create), $merge, $overwrite);
            }
        }
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $defaults
     *
     * @throws InvalidArgumentException
     */
    private static function mergeTyped(AbstractAnnotation $annotation, string $propertyName, mixed $type, array $properties, array $defaults, bool $overwrite): void
    {
        if (is_string($type) && strpos($type, '[') === 0) {
            /** @var class-string<AbstractAnnotation> $innerType */
            $innerType = substr($type, 1, -1);

            if (! $annotation->{$propertyName} || $annotation->{$propertyName} === Generator::UNDEFINED) {
                $annotation->{$propertyName} = [];
            }

            if (! class_exists($innerType)) {
                // type is declared as array in @see AbstractAnnotation::$_types
                $annotation->{$propertyName} = array_unique(array_merge(
                    $annotation->{$propertyName},
                    $properties[$propertyName]
                ));

                return;
            }

            // $type == [Schema] for instance
            foreach ($properties[$propertyName] as $child) {
                $annotation->{$propertyName}[] = $annot = self::createChild($annotation, $innerType);
                self::merge($annot, $child, $overwrite);
            }
        } else {
            self::mergeProperty($annotation, $propertyName, $properties[$propertyName], $defaults[$propertyName], $overwrite);
        }
    }

    private static function mergeProperty(AbstractAnnotation $annotation, string $propertyName, mixed $value, mixed $default, bool $overwrite): void
    {
        if ($overwrite === true || $default === $annotation->{$propertyName}) {
            $annotation->{$propertyName} = $value;
        }
    }

    /**
     * @param class-string<AbstractAnnotation> $className
     *
     * @return string[]
     */
    private static function getNestingIndexes(string $className): array
    {
        return array_values(array_map(
            static function (array|string $value): string {
                return is_array($value) ? $value[0] : $value;
            },
            $className::$_nested
        ));
    }
}
