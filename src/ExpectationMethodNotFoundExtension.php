<?php

declare(strict_types=1);

namespace Rudashi\PHPStan;

use Pest\Mixins\Expectation;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

readonly class ExpectationMethodNotFoundExtension implements MethodsClassReflectionExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== Expectation::class) {
            return false;
        }

        $valueType = $classReflection->getActiveTemplateTypeMap()->getType('TValue');

        if (! $valueType) {
            return false;
        }

        $nonFalsyTypes = TypeCombinator::removeNull($valueType);

        if ($nonFalsyTypes instanceof ObjectType) {
            return $this->reflectionProvider->getClass($nonFalsyTypes->getClassName())->hasMethod($methodName);
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new FluentMethodReflection($this->reflectionProvider
            ->getClass(Expectation::class)
            ->getMethod('toBeTrue', new OutOfClassScope())
        );
    }
}
