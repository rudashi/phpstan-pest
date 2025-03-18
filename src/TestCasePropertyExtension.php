<?php

declare(strict_types=1);

namespace Rudashi\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPUnit\Framework\TestCase;

class TestCasePropertyExtension implements IgnoreErrorExtension
{
    private static array $propertyCache = [];

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'property.notFound') {
            return false;
        }

        if (str_contains($error->getMessage(), TestCase::class)) {
            return $this->dontIgnoreTestFile($error, $node, $scope);
        }

        return false;
    }

    private function dontIgnoreTestFile(Error $error, Node $node, Scope $scope): bool
    {
        if ($node instanceof PropertyAssignNode) {
            $context = $node->getPropertyFetch()->var->name;
            $property = $node->getPropertyFetch()->name->name;
            $callStack = $scope->getFunctionCallStack();
            $function = end($callStack)->getName();

            self::$propertyCache[$error->getFile()][$property] = true;

            if ($context === 'this' && $function === 'beforeEach') {
                return true;
            }
        }

        return $node instanceof PropertyFetch && self::$propertyCache[$error->getFile()][$node->name->name];
    }
}
