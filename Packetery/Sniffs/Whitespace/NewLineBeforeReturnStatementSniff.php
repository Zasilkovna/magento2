<?php

declare(strict_types=1);

namespace Packetery\Sniffs\Whitespace;

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
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($tokens[$stackPtr]['line'] !== ($tokens[$previous]['line'] + 2)) {
            $error = 'There must be an empty line before return statement';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'EmptyLineBeforeReturn');

            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewlineBefore($stackPtr);
                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
