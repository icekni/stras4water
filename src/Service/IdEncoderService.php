<?php

namespace App\Service;

class IdEncoderService
{
    private int $offset;

    public function __construct(int $offset = 5000)
    {
        $this->offset = $offset;
    }

    /**
     * Encode un ID numérique en chaîne hexadécimale "obfusquée".
     */
    public function encode(int $id): string
    {
        return dechex($id + $this->offset);
    }

    /**
     * Décode une chaîne hexadécimale "obfusquée" en ID numérique.
     */
    public function decode(string $encoded): int
    {
        return hexdec($encoded) - $this->offset;
    }
}
