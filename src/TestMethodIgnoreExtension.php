<?php

declare(strict_types=1);

namespace Rudashi\PHPStan;

use Pest\PendingCalls\TestCall;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

readonly class TestMethodIgnoreExtension implements IgnoreErrorExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'method.notFound') {
            return false;
        }

        if (! $this->isTestClass($error->getMessage())) {
            return false;
        }

        if ($scope instanceof MutatingScope && $node instanceof Node\Expr\MethodCall) {

            $usesClasses = $this->getUsesClasses($scope);

            foreach ($usesClasses as $useClass) {
                if ($this->reflectionProvider->getClass($useClass)->hasNativeMethod($node->name->name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getUsesClasses(MutatingScope $scope): array
    {
        $classList = [];

        $reflection = new ReflectionObject($scope);

        $parser = $reflection->getProperty('parser')->getValue($scope)->parseFile($scope->getFile());

        if (isset($parser[1]) && $parser[1] instanceof Node\Stmt\Namespace_) {
            foreach ($parser[1]->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Node\Expr\FuncCall && $stmt->expr->name->name === 'uses') {
                    foreach ($stmt->expr->getRawArgs() as $arg) {
                        if ($arg->value instanceof Node\Expr\ClassConstFetch) {
                            $classList[] = $arg->value->class->name;
                        }
                    }

                    return $classList;
                }
            }
        }

        return $classList;
    }

    private function isTestClass(string $errorMessage): true
    {
        return str_contains($errorMessage, TestCase::class) || str_contains($errorMessage, TestCall::class);
    }
}
