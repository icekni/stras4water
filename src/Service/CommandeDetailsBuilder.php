<?php

namespace App\Service;

use App\Repository\AbonnementSouscritRepository;
use App\Repository\CarteSouscriteRepository;
use App\Entity\User;

class CommandeDetailsBuilder
{
    public function __construct(
        private AbonnementSouscritRepository $abonnementRepository,
        private CarteSouscriteRepository $carteRepository,
    ) {}

    /**
     * @param bool $adhesion
     * @param string[] $abonnementIds
     * @param string[] $carteIds
     * @param User $user
     * @return array<int, array{type: string, libelle: string, duree: string}>
     */
    public function build(bool $adhesion, array $abonnementIds, array $carteIds, User $user): array
    {
        $details = [];

        if ($adhesion) {
            $now = new \DateTimeImmutable();
            $annee = (int) $now->format('Y');
            $mois = (int) $now->format('m');
            $saison = $mois >= 9 ? "$annee/" . ($annee + 1) : ($annee - 1) . "/$annee";

            $details[] = [
                'type' => 'Adhésion',
                'libelle' => 'Adhésion membre',
                'duree' => 'Saison ' . $saison,
            ];
        }

        foreach ($abonnementIds as $id) {
            $abonnement = $this->abonnementRepository->find($id);
            if ($abonnement && $abonnement->getUser() === $user) {
                $cours = $abonnement->getCours()?->getNom() ?? 'Cours inconnu';
                $details[] = [
                    'type' => 'Abonnement',
                    'libelle' => $cours,
                    'duree' => 'Saison 2025/2026',
                ];
            }
        }

        foreach ($carteIds as $id) {
            $carte = $this->carteRepository->find($id);
            if ($carte && $carte->getUser() === $user) {
                $cours = $carte->getCours()?->getNom() ?? 'Cours inconnu';
                $details[] = [
                    'type' => 'Carte',
                    'libelle' => $cours,
                    'duree' => 'Valable 12 mois',
                ];
            }
        }

        return $details;
    }
}
