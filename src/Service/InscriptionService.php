<?php

namespace App\Service;

use App\Dto\InscriptionData;
use App\Entity\AbonnementSouscrit;
use App\Entity\Adhesion;
use App\Entity\CarteSouscrite;
use App\Entity\User;
use App\Entity\Saison;
use App\Enum\Statut;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use App\Service\CarteDeMembreGenerator;
use App\Service\EmailService;

class InscriptionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CarteDeMembreGenerator $carteDeMembreGenerator,
        private EmailService $emailService,
    ) {
    }

    public function inscrire(
        InscriptionData $data,
        ?User $existingUser = null
    ): User {
        $this->validate($data, $existingUser);

        $user = $existingUser;

        if ($user === null) {
            $user = new User();
            $user->setNom($data->nom);
            $user->setPrenom($data->prenom);
            $user->setEmail($data->email);

            $this->em->persist($user);
        }

        $adhesionAjoutee = false;

        /*
         * Adhésion
         */
        if ($data->adhesion && $user->getAdhesion() === null) {
            $adhesion = new Adhesion();
            $adhesion->setUser($user);
            $adhesion->setCreatedAt(new \DateTimeImmutable());
            $adhesion->setMoyenPaiement($data->moyenPaiement);

            $this->em->persist($adhesion);

            $adhesionAjoutee = true;
        }

        /*
         * Abonnements
         */
        foreach ($data->abonnements as $abonnement) {
            $souscrit = new AbonnementSouscrit();
            $souscrit->setUser($user);
            $souscrit->setAbonnement($abonnement);
            $souscrit->setStatut(Statut::ACTIVE);
            $souscrit->setMoyenPaiement($data->moyenPaiement);

            $isTarifReduit =
                $data->tarifReduit && $abonnement->HasTarifReduit();

            $souscrit->setIsTarifReduit($isTarifReduit);
            $souscrit->setTarifReduitVerifie(
                $isTarifReduit && $data->justificatif === 'fourni'
            );

            $this->em->persist($souscrit);
        }

        /*
         * Cartes
         */
        foreach ($data->cartes as $carte) {
            $souscrit = new CarteSouscrite();
            $souscrit->setUser($user);
            $souscrit->setCarte($carte);
            $souscrit->setStatut(Statut::ACTIVE);
            $souscrit->setMoyenPaiement($data->moyenPaiement);
            $souscrit->setSeancesRestantes($carte->getNombreSeances());

            $isTarifReduit =
                $data->tarifReduit && $carte->HasTarifReduit();

            $souscrit->setIsTarifReduit($isTarifReduit);
            $souscrit->setTarifReduitVerifie(
                $isTarifReduit && $data->justificatif === 'fourni'
            );

            $this->em->persist($souscrit);
        }

        $this->em->flush();

        /*
         * Une nouvelle adhésion implique la génération
         * et l'envoi de la carte de membre.
         */
        if ($adhesionAjoutee) {
            $this->carteDeMembreGenerator->generate(
                $user,
                $this->getSaisonAdhesion()
            );

            $this->emailService->sendMembershipCard($user);
        }

        return $user;
    }

    private function validate(
        InscriptionData $data,
        ?User $existingUser
    ): void {
        $hasAbonnements = count($data->abonnements) > 0;
        $hasCartes = count($data->cartes) > 0;
        $hasActivites = $hasAbonnements || $hasCartes;

        /*
         * Il faut au moins une opération.
         */
        $hasOperation = $data->adhesion || $hasActivites;

        if (!$hasOperation) {
            throw new InvalidArgumentException(
                'Aucune adhésion ni activité n’a été sélectionnée.'
            );
        }

        /*
         * Pas d'activité = pas de tarif réduit.
         */
        if (!$hasActivites && $data->tarifReduit) {
            throw new InvalidArgumentException(
                'Le tarif réduit ne peut pas être utilisé sans activité.'
            );
        }

        /*
         * Pas de tarif réduit = pas de justificatif.
         */
        if (!$data->tarifReduit && $data->justificatif !== null) {
            throw new InvalidArgumentException(
                'Un justificatif ne peut être renseigné sans tarif réduit.'
            );
        }

        /*
         * Tarif réduit :
         * au moins une activité doit accepter le tarif réduit.
         */
        if ($data->tarifReduit) {
            $hasTarifReduitPossible = false;

            foreach ($data->abonnements as $abonnement) {
                if ($abonnement->HasTarifReduit()) {
                    $hasTarifReduitPossible = true;
                    break;
                }
            }

            if (!$hasTarifReduitPossible) {
                foreach ($data->cartes as $carte) {
                    if ($carte->HasTarifReduit()) {
                        $hasTarifReduitPossible = true;
                        break;
                    }
                }
            }

            if (!$hasTarifReduitPossible) {
                throw new InvalidArgumentException(
                    'Aucune activité sélectionnée ne permet le tarif réduit.'
                );
            }

            /*
             * Si tarif réduit, le justificatif doit être renseigné.
             */
            if ($data->justificatif === null) {
                throw new InvalidArgumentException(
                    'Le justificatif doit être renseigné pour le tarif réduit.'
                );
            }
        }

        /*
         * Pour un utilisateur existant, on ne doit pas recréer
         * une adhésion déjà existante.
         *
         * Normalement l'interface ne permettra pas de le faire,
         * mais on garde la sécurité côté service.
         */
        if (
            $existingUser !== null
            && $data->adhesion
            && $existingUser->getAdhesion() !== null
        ) {
            throw new InvalidArgumentException(
                'Cet utilisateur possède déjà une adhésion.'
            );
        }

        /*
         * Empêcher les doublons d'abonnements.
         */
        if ($existingUser !== null) {
            foreach ($data->abonnements as $abonnement) {
                foreach ($existingUser->getAbonnementSouscrits() as $souscrit) {
                    if (
                        $souscrit->getAbonnement()->getId()
                        === $abonnement->getId()
                    ) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'L’abonnement "%s" est déjà souscrit.',
                                $abonnement->getNom()
                            )
                        );
                    }
                }
            }

            /*
             * Empêcher les doublons de cartes.
             */
            foreach ($data->cartes as $carte) {
                foreach ($existingUser->getCarteSouscrites() as $souscrit) {
                    if (
                        $souscrit->getCarte()->getId()
                        === $carte->getId()
                    ) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'La carte "%s" est déjà souscrite.',
                                $carte->getNom()
                            )
                        );
                    }
                }
            }
        }
    }

    private function getSaisonAdhesion(): string
    {
        $now = new \DateTimeImmutable();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n'); // 1 à 12

        if ($month >= 7) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}