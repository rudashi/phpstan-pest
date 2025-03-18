<?php

declare(strict_types=1);

namespace Rudashi\PHPStan;

use Pest\Mixins\Expectation;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPUnit\Framework\TestCase;

class TestCasePropertyExtension implements IgnoreErrorExtension
{
    private static array $propertyCache = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'property.notFound') {
            return false;
        }

        if (str_contains($error->getMessage(), TestCase::class)) {
            return $this->dontIgnoreTestFile($error, $node, $scope);
        }

        if (str_contains($error->getMessage(), Expectation::class)) {
            return $this->dontIgnorePestExpectation($node);
        }

        return false;
    }

    private function dontIgnoreTestFile(Error $error, Node $node, Scope $scope): bool
    {
        if ($node instanceof PropertyAssignNode) {
            $context = $node->getPropertyFetch()->var->name;
            $property = $node->getPropertyFetch()->name->name;
            $function = $scope->getFunctionCallStack()[0]->getName();

            self::$propertyCache[$error->getFile()][$property] = true;

            if ($context === 'this' && $function === 'beforeEach') {
                return true;
            }
        }

        return $node instanceof PropertyFetch && self::$propertyCache[$error->getFile()][$node->name->name];
    }

    private function dontIgnorePestExpectation(Node $node): bool
    {
        if ($node instanceof PropertyFetch === false) {
            return false;
        }

        if ($node->var instanceof MethodCall === false) {
            return false;
        }

        if ($node->var->name->name !== 'toBeInstanceOf') {
            return false;
        }

        $type = $node->var->getRawArgs()[0]->value;

        if ($type instanceof ClassConstFetch === false) {
            return false;
        }

        return $this->reflectionProvider->getClass($type->class->name)->hasProperty($node->name->name);
    }
}
