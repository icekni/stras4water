<?php

namespace App\Service;

use App\Entity\GroupeControle;
use App\Entity\User;
use App\Dto\ValidationResult;

class ControleAccesService
{
    public function controler(User $user, GroupeControle $groupe): array
    {
        $tarifReduitAValider = false;
        $abonnements = [];
        $cartes = [];

        foreach ($user->getAbonnementSouscrits() as $abonnementSouscrit) {
            $abonnement = $abonnementSouscrit->getAbonnement();

            if (!$groupe->getAbonnements()->contains($abonnement)) {
                continue;
            }

            $validation = $abonnementSouscrit->isValid();

            $abonnements[] = [
                'souscrit' => $abonnementSouscrit,
                'validation' => $validation,
                'tarifReduitAValider' =>
                    $validation->code === ValidationResult::CODE_TARIF_REDUIT_A_VERIFIER,
            ];
        }

        foreach ($user->getCarteSouscrites() as $carteSouscrite) {
            $carte = $carteSouscrite->getCarte();

            if (!$groupe->getCartes()->contains($carte)) {
                continue;
            }

            $validation = $carteSouscrite->isValid();

            $cartes[] = [
                'souscrite' => $carteSouscrite,
                'validation' => $validation,
                'tarifReduitAValider' =>
                    $validation->code === ValidationResult::CODE_TARIF_REDUIT_A_VERIFIER,
            ];
        }

        foreach ($abonnements as $abonnement) {
            if ($abonnement['tarifReduitAValider']) {
                $tarifReduitAValider = true;
            }

            if ($abonnement['validation']->isValid) {
                $accesAutorise = true;
                break;
            }
        }

        if (!$accesAutorise) {
            foreach ($cartes as $carte) {
                if ($carte['tarifReduitAValider']) {
                    $tarifReduitAValider = true;
                }

                if ($carte['validation']->isValid) {
                    $accesAutorise = true;
                    break;
                }
            }
        }

        $accesAutorise = false;

        foreach ($abonnements as $abonnement) {
            if ($abonnement['validation']->isValid) {
                $accesAutorise = true;
                break;
            }
        }

        if (!$accesAutorise) {
            foreach ($cartes as $carte) {
                if ($carte['validation']->isValid) {
                    $accesAutorise = true;
                    break;
                }
            }
        }

        return [
            'accesAutorise' => $accesAutorise,
            'tarifReduitAValider' => $tarifReduitAValider,
            'abonnements' => $abonnements,
            'cartes' => $cartes,
        ];
    }
}