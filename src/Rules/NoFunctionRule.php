<?php

declare(strict_types=1);

namespace Rudashi\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Catches unnecessary usage of function in tests files.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Function_>
 */
final class NoFunctionRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Function_::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     *
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node|Node\Stmt\Function_ $node, Scope $scope): array
    {
        if (str_contains('Test.php', $scope->getFile())) {
            return [];
        }

        $name = $node->name->name;

        return [
            RuleErrorBuilder::message(sprintf("Defined function '%s', which is not allowed in Test files.", $name))
                ->identifier('pest.noFunctionInTests')
                ->line($node->getStartLine())
                ->file($scope->getFile())
                ->build(),
        ];
    }
}
