<?php

namespace App\Exceptions;

use Exception;

class MissingDwollaProfileDataException extends Exception
{
    /**
     * @param  array<string, string>  $missingFields  Field key => human-readable label
     */
    public function __construct(
        public readonly array $missingFields,
        string $message = 'Additional profile information is required for payment account setup.',
    ) {
        parent::__construct($message);
    }
}
