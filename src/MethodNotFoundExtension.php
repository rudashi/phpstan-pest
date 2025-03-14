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
    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== TestCall::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(HigherOrderCallables::class)->hasMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new FluentMethodReflection(
            $this->reflectionProvider
                ->getClass(HigherOrderCallables::class)
                ->getMethod($methodName, new OutOfClassScope())
        );
    }
}
