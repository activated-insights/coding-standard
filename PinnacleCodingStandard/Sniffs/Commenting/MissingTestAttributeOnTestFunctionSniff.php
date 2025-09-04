<?php

namespace PinnacleCodingStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\Attribute;
use SlevomatCodingStandard\Helpers\AttributeHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * A sniff to check for test functions missing a #[Test] attribute.
 */
class MissingTestAttributeOnTestFunctionSniff implements Sniff
{
    /**
     * The name of the sniff.
     */
    private const NAME = 'MissingTestAttributeOnTestFunction';

    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $namespace    = NamespaceHelper::findCurrentNamespaceName($phpcsFile, $stackPtr);
        $functionName = FunctionHelper::getName($phpcsFile, $stackPtr);

        if (!$this->looksLikeTestFunction($namespace, $functionName)) {
            // Not a test function, don't bother checking for attribute.
            return;
        }

        $tokens        = $phpcsFile->getTokens();
        $previousToken = TokenHelper::findPrevious($phpcsFile, [T_ATTRIBUTE], $stackPtr - 1);

        while ($previousToken !== null && $tokens[$previousToken]['code'] === T_ATTRIBUTE) {
            $attributeNames = array_map(
                static fn (Attribute $name): string => $name->getFullyQualifiedName(),
                AttributeHelper::getAttributes($phpcsFile, $previousToken),
            );

            if (
                in_array('Test', $attributeNames, true) ||
                in_array('PHPUnit\\Framework\\Attributes\\Test', $attributeNames, true) ||
                in_array('\\PHPUnit\\Framework\\Attributes\\Test', $attributeNames, true)
            ) {
                return;
            }

            $previousToken = TokenHelper::findFirstNonWhitespaceOnPreviousLine($phpcsFile, $previousToken);
        }

        // Found a test function without a #[Test] attribute, add an error.
        $phpcsFile->addError(
            sprintf(
                '%s %s() looks like a test but is missing the #[Test] attribute.',
                FunctionHelper::getTypeLabel($phpcsFile, $stackPtr),
                FunctionHelper::getFullyQualifiedName($phpcsFile, $stackPtr)
            ),
            $stackPtr,
            self::NAME
        );
    }

    /**
     * Whether the specified function name looks like a test function according to our format.
     */
    private function looksLikeTestFunction(?string $namespace, string $functionName): bool
    {
        if ($namespace === null || substr($namespace, 0, 6) !== 'Tests\\') {
            // Function doesn't belong to a class in the Tests\ namespace.
            return false;
        }

        // Check if the function matches our naming convention for tests: unitUnderTest_Scenario_ExpectedResult().
        return (bool)preg_match('/^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$/', $functionName);
    }
}
