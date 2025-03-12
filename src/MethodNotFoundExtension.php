<?php

declare(strict_types=1);

namespace Rudashi\PHPStan;

use Pest\PendingCalls\TestCall;
use Pest\Support\HigherOrderCallables;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

readonly class MethodNotFoundExtension implements MethodsClassReflectionExtension
{
    private ClassReflection $highOrder;

    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
        $this->highOrder = $reflectionProvider->getClass(HigherOrderCallables::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== TestCall::class) {
            return false;
        }

        return $this->highOrder->hasMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new FluentMethodReflection(
            $this->highOrder->getMethod($methodName, new OutOfClassScope())
        );
    }
}
