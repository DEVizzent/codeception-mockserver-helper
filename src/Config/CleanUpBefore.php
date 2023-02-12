<?php

namespace DEVizzent\CodeceptionMockServerHelper\Config;

use InvalidArgumentException;
use PHP_CodeSniffer\Tests\Core\File\testFECNClass;

class CleanUpBefore
{
    public const TEST = 'test';
    public const SUITE = 'suite';
    public const NEVER = 'never';
    private const ALLOWED_VALUES = [self::TEST, self::SUITE, self::NEVER];
    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED_VALUES, true)) {
            $message = sprintf(
                '"%s" is not allowed value for cleanUpBefore. Only %s are valid',
                $value,
                implode(', ', self::ALLOWED_VALUES)
            );
            throw new InvalidArgumentException($message);
        }
        $this->value = $value;
    }

    public function isTest(): bool
    {
        return self::TEST === $this->value;
    }

    public function isSuite(): bool
    {
        return self::SUITE === $this->value;
    }
}
