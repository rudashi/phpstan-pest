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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

readonly class ExpectationPropertyExtension implements IgnoreErrorExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'property.notFound') {
            return false;
        }
        if (str_contains($error->getMessage(), Expectation::class)) {
            return $this->dontIgnorePestExpectation($node, $scope);
        }

        return false;
    }

    private function dontIgnorePestExpectation(Node $node, Scope $scope): bool
    {
        if ($node instanceof PropertyFetch === false) {
            return false;
        }

        if ($node->var instanceof MethodCall === false) {
            return false;
        }

        if ($node->var->name->name === 'toBeInstanceOf') {
            return $this->checkByInstanceOf($node->var, $node->name->name);
        }

        $valueType = $scope->getType($node->var)->getObjectClassReflections()[1]->getActiveTemplateTypeMap()->getType('TValue');

        if (! $valueType) {
            return false;
        }

        $nonFalsyTypes = TypeCombinator::removeNull($valueType);

        if ($nonFalsyTypes instanceof ObjectType) {
            return $this->reflectionProvider->getClass($nonFalsyTypes->getClassName())->hasProperty($node->name->name);
        }

        return false;
    }

    private function checkByInstanceOf(MethodCall $node, string $propertyName): bool
    {
        $type = $node->getRawArgs()[0]->value;

        if ($type instanceof ClassConstFetch === false) {
            return false;
        }

        return $this->reflectionProvider->getClass($type->class->name)->hasProperty($propertyName);
    }
}
