<?php

namespace App\Service;

use App\Dto\ComptabiliteLigne;
use App\Repository\AbonnementSouscritRepository;
use App\Repository\AdhesionRepository;
use App\Repository\CarteSouscriteRepository;

class ComptabiliteService
{
    public function __construct(
        private AdhesionRepository $adhesionRepository,
        private AbonnementSouscritRepository $abonnementRepository,
        private CarteSouscriteRepository $carteRepository,
    ) {
    }

    /**
     * @return ComptabiliteLigne[]
     */
    public function getLignes(
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array {
        $lignes = [];

        foreach ($this->adhesionRepository->findBetweenDates($from, $to) as $adhesion) {
            $lignes[] = new ComptabiliteLigne(
                date: $adhesion->getCreatedAt(),
                type: 'Adhésion',
                libelle: 'Adhésion',
                discipline: null,
                moyenPaiement: $adhesion->getMoyenPaiement(),
            );
        }

        foreach ($this->abonnementRepository->findBetweenDates($from, $to) as $abonnement) {
            $lignes[] = new ComptabiliteLigne(
                date: $abonnement->getCreatedAt(),
                type: 'Abonnement',
                libelle: $abonnement->getAbonnement()->getNom(),
                discipline: $abonnement->getAbonnement()->getDiscipline()?->getNom(),
                moyenPaiement: $abonnement->getMoyenPaiement(),
            );
        }

        foreach ($this->carteRepository->findBetweenDates($from, $to) as $carte) {
            $lignes[] = new ComptabiliteLigne(
                date: $carte->getCreatedAt(),
                type: 'Carte',
                libelle: $carte->getCarte()->getNom(),
                discipline: implode(
                    ', ',
                    $carte->getCarte()->getDisciplines()
                        ->map(fn ($d) => $d->getNom())
                        ->toArray()
                ),
                moyenPaiement: $carte->getMoyenPaiement(),
            );
        }

        usort(
            $lignes,
            fn (ComptabiliteLigne $a, ComptabiliteLigne $b) => $b->date <=> $a->date
        );

        return $lignes;
    }
}