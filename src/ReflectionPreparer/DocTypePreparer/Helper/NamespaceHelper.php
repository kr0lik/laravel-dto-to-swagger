<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Helper;

use ReflectionClass;
use ReflectionException;
use RuntimeException;

class NamespaceHelper
{
    /**
     * @var array<string, array<string, string>>
     */
    private static array $register = [];

    /**
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public static function getNameSpace(string $fromClass, string $type): string
    {
        $refClass = new ReflectionClass($fromClass);

        if (class_exists($refClass->getNamespaceName().'\\'.$type)) {
            return $refClass->getNamespaceName().'\\'.$type;
        }

        if (!array_key_exists($fromClass, self::$register)) {
            self::registerUses($refClass);
        }

        if (!array_key_exists($type, self::$register[$fromClass])) {
            throw new RuntimeException('No namespace found for '.$type);
        }

        return self::$register[$fromClass][$type];
    }

    /**
     * @throws RuntimeException
     */
    private static function registerUses(ReflectionClass $refClass): void
    {
        $filename = $refClass->getFileName();

        if (false === $filename) {
            throw new RuntimeException('No file for '.$refClass->getName());
        }

        $fileContent = file_get_contents($filename);

        if (false === $fileContent) {
            throw new RuntimeException('File not found '.$refClass->getName());
        }

        $pattern = '/use\s+(.*?);/';
        preg_match_all($pattern, $fileContent, $namespaceMatches);

        foreach ($namespaceMatches[1] as $namespace) {
            if (str_contains($namespace, ' as ')) {
                [$namespace, $alias] = explode(' as ', $namespace);
            } else {
                $alias = basename(str_replace('\\', '/', $namespace));
            }

            self::$register[$refClass->getName()][$alias] = $namespace;
        }
    }
}
