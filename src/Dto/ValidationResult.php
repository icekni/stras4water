<?php

namespace App\Dto;

class ValidationResult
{
    public const CODE_TARIF_REDUIT_A_VERIFIER = 'TARIF_REDUIT_A_VERIFIER';

    public bool $isValid;
    public string $reason;
    public ?string $code;

    public function __construct(
        bool $isValid,
        string $reason = '',
        ?string $code = null
    ) {
        $this->isValid = $isValid;
        $this->reason = $reason;
        $this->code = $code;
    }
}