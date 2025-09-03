<?php

declare(strict_types=1);

if (! function_exists('buildSentenceCaseMessage')) {
    /**
     * Get a formatted validation message.
     *
     * @param  string $key    The message key.
     * @param  array  $params The parameters to replace in the message.
     *                        Example: buildSentenceCaseMessage('min_length', 'Password', 8)
     * @return string The formatted message.
     *
     * @throws \InvalidArgumentException if the key is invalid.
     */
    function buildSentenceCaseMessage($key, ...$params)
    {
        if (! is_string($key) || empty($key)) {
            throw new \InvalidArgumentException('The key must be a non-empty string.');
        }

        $message = config('validation_messages.' . $key);
        if (! $message) {
            error_log('Validation message key not found: ' . $key);

            return sprintf('Error: "%s" is not defined.', $key);
        }

        try {
            return vsprintf($message, $params);
        } catch (\Throwable $e) {
            error_log('Error formatting message: ' . $e->getMessage());

            return 'An error occurred while formatting the message.';
        }
    }
}
