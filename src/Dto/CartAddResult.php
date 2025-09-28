<?php

namespace App\Dto;

class CartAddResult
{
    public function __construct(
        public bool $success,
        public string $message = ''
    ) {}
}
