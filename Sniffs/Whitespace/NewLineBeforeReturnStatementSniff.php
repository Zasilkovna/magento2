<?php

declare(strict_types=1);

namespace code\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class NewLineBeforeReturnStatementSniff implements Sniff
{
    /**
     * Register function.
     */
    public function register(): array
    {
        return [T_RETURN];
    }

    /**
     * Adds new line before return statement
     *
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $tmpPtr = $stackPtr;
        do {
            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($tmpPtr - 1), null, true);
            $tmpPtr--;
        } while ($previous === T_WHITESPACE);

        // Ignore return statements in closures.
        if ($tokens[$previous]['code'] === T_OPEN_CURLY_BRACKET) {
            return;
        }

        if ($tokens[$tmpPtr]['line'] !== ($tokens[$previous]['line'] + 2)) {
            $error = 'There must be an empty line before return statement';
            $fix = $phpcsFile->addFixableError($error, $tmpPtr, 'EmptyLineBeforeReturn');

            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewlineBefore($tmpPtr);
                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
