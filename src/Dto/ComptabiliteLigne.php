<?php

namespace App\Dto;

use App\Entity\User;
use App\Enum\MoyenPaiement;

class ComptabiliteLigne
{
    public function __construct(
        public readonly \DateTimeImmutable $date,
        public readonly string $type,
        public readonly string $libelle,
        public readonly ?string $discipline,
        public readonly MoyenPaiement $moyenPaiement,
    ) {
    }
}