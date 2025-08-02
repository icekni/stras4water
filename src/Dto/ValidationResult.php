<?php

namespace App\Dto;

class ValidationResult
{
    public bool $isValid;
    public string $reason;

    public function __construct(bool $isValid, string $reason = '')
    {
        $this->isValid = $isValid;
        $this->reason = $reason;
    }
}
