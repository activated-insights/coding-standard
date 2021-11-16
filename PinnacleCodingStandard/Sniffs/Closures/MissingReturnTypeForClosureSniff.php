<?php

declare(strict_types=1);

namespace PinnacleCodingStandard\Sniffs\Closures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class MissingReturnTypeForClosureSniff implements Sniff
{
    /**
     * The name of the sniff.
     */
    private const NAME = 'MissingReturnTypeForClosure';

    public function register()
    {
        return [
            T_CLOSURE,
        ];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $endOfStatementPointer   = $phpcsFile->findEndOfStatement($stackPtr);
        $endOfDeclarationPointer = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPtr, $endOfStatementPointer);
        $returnTypeColonPointer  = $phpcsFile->findNext(T_COLON, $stackPtr, $endOfDeclarationPointer);
        if ($returnTypeColonPointer === false) {
            $phpcsFile->addError('Closure missing return type.', $stackPtr, self::NAME);
        }
    }
}
