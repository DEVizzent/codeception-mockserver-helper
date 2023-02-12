<?php

namespace DEVizzent\CodeceptionMockServerHelper\Config;

use InvalidArgumentException;

class NotMatchedRequest
{
    public const ENABLED = 'enabled';
    public const DISABLED = 'disabled';
    private const ALLOWED_VALUES = [self::ENABLED, self::DISABLED];
    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED_VALUES, true)) {
            $message = sprintf(
                '"%s" is not allowed value for notMatchedRequest. Only %s are valid',
                $value,
                implode(', ', self::ALLOWED_VALUES)
            );
            throw new InvalidArgumentException($message);
        }
        $this->value = $value;
    }

    public function isEnabled(): bool
    {
        return self::ENABLED === $this->value;
    }
}
