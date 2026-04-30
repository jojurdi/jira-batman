<?php

namespace App;

class JiraApiException extends \RuntimeException
{
    /** @var string[] */
    public array $errorMessages;
    /** @var array<string,string> field key => message */
    public array $fieldErrors;

    public function __construct(string $message, int $httpCode, array $errorMessages = [], array $fieldErrors = [])
    {
        parent::__construct($message, $httpCode);
        $this->errorMessages = $errorMessages;
        $this->fieldErrors   = $fieldErrors;
    }
}
