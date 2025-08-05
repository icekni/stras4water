<?php

namespace App\Service;

use App\Repository\AbonnementSouscritRepository;
use App\Repository\CarteSouscriteRepository;
use App\Entity\User;

class CommandeDetailsBuilder
{
    public function __construct(
        private AbonnementSouscritRepository $abonnementSouscritRepository,
        private CarteSouscriteRepository $carteSouscriteRepository,
    ) {}

    /**
     * @param bool $adhesion
     * @param string[] $abonnementIds
     * @param string[] $carteIds
     * @param User $user
     * @return array<int, array{type: string, libelle: string, duree: string}>
     */
    public function build(bool $adhesion, array $abonnementIds, array $carteIds, User $user, string $annee): array
    {
        $details = [];

        if ($adhesion) {
            $now = new \DateTimeImmutable();
            $annee = (int) $now->format('Y');
            $mois = (int) $now->format('m');
            $saison = $mois >= 9 ? "$annee/" . ($annee + 1) : ($annee - 1) . "/$annee";

            $details[] = [
                'type' => 'Adhésion',
                'libelle' => 'Adhésion ' . $annee,
            ];
        }

        foreach ($abonnementIds as $id) {
            $abonnementSouscrit = $this->abonnementSouscritRepository->find($id);
            if ($abonnementSouscrit && $abonnementSouscrit->getUser() === $user) {
                $details[] = [
                    'type' => 'Abonnement',
                    'libelle' => $abonnementSouscrit->getAbonnement()->getNom(),
                ];
            }
        }

        foreach ($carteIds as $id) {
            $carteSouscrite = $this->carteSouscriteRepository->find($id);
            if ($carteSouscrite && $carteSouscrite->getUser() === $user) {
                $details[] = [
                    'type' => 'Carte',
                    'libelle' => $carteSouscrite->getCarte()->getNom(),
                ];
            }
        }

        return $details;
    }
}
