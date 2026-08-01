<?php

namespace App\Service;

use App\Dto\ComptabiliteLigne;
use App\Enum\MoyenPaiement;
use App\Repository\AbonnementSouscritRepository;
use App\Repository\AdhesionRepository;
use App\Repository\CarteSouscriteRepository;
use App\Repository\DonationRepository;

class ComptabiliteService
{
    public function __construct(
        private AdhesionRepository $adhesionRepository,
        private AbonnementSouscritRepository $abonnementRepository,
        private CarteSouscriteRepository $carteRepository,
        private DonationRepository $donationRepository
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

        $adhesions = $this->exclureBenevole(
            $this->adhesionRepository->findBetweenDates($from, $to)
        );

        foreach ($adhesions as $adhesion) {
            $lignes[] = new ComptabiliteLigne(
                date: $adhesion->getCreatedAt(),
                type: 'Adhésion',
                libelle: 'Adhésion',
                typeTarif: null,
                discipline: null,
                moyenPaiement: $adhesion->getMoyenPaiement(),
            );
        }


        $abonnements = $this->exclureBenevole(
            $this->abonnementRepository->findBetweenDates($from, $to)
        );

        foreach ($abonnements as $abonnement) {
            $lignes[] = new ComptabiliteLigne(
                date: $abonnement->getCreatedAt(),
                type: 'Abonnement',
                libelle: $abonnement->getAbonnement()->getNom(),                
                typeTarif: $abonnement->isTarifReduit() ? "Tarif réduit" : "Plein tarif",
                discipline: $abonnement->getAbonnement()->getDiscipline()?->getNom(),
                moyenPaiement: $abonnement->getMoyenPaiement(),
            );
        }


        $cartes = $this->exclureBenevole(
            $this->carteRepository->findBetweenDates($from, $to)
        );

        foreach ($cartes as $carte) {
            $lignes[] = new ComptabiliteLigne(
                date: $carte->getCreatedAt(),
                type: 'Carte',
                libelle: $carte->getCarte()->getNom(),
                typeTarif: $carte->isTarifReduit() ? "Tarif réduit" : "Plein tarif",
                discipline: implode(
                    ', ',
                    $carte->getCarte()->getDisciplines()
                        ->map(fn ($d) => $d->getNom())
                        ->toArray()
                ),
                moyenPaiement: $carte->getMoyenPaiement(),
            );
        }

        // foreach ($this->donationRepository->findBetweenDates($from, $to) as $donation) {
        //     $lignes[] = new ComptabiliteLigne(
        //         date: $donation->getCreatedAt(),
        //         type: 'Don',
        //         libelle: sprintf(
        //             'Don %s - %.2f €',
        //             $donation->getTypeDon()?->name,
        //             $donation->getMontant()
        //         ),
        //         discipline: null,
        //         moyenPaiement: $donation->getMoyenPaiement(),
        //     );
        // }

        usort(
            $lignes,
            fn (ComptabiliteLigne $a, ComptabiliteLigne $b) => $b->date <=> $a->date
        );

        return $lignes;
    }


    /**
     * @template T
     * @param T[] $elements
     * @return T[]
     */
    private function exclureBenevole(array $elements): array
    {
        return array_filter(
            $elements,
            fn ($element) => $element->getMoyenPaiement() !== MoyenPaiement::BENEVOLE
        );
    }
}