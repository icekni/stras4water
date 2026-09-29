<?php

namespace App\Dto;

use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Enum\MoyenPaiement;

class InscriptionData
{
    public ?string $nom = null;

    public ?string $prenom = null;

    public ?string $email = null;

    public bool $adhesion = false;

    /**
     * @var Abonnement[]
     */
    public array $abonnements = [];

    /**
     * @var Carte[]
     */
    public array $cartes = [];

    public bool $tarifReduit = false;

    /**
     * 'fourni' ou 'plus_tard'
     */
    public ?string $justificatif = null;

    public ?MoyenPaiement $moyenPaiement = null;
}