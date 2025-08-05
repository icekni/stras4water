<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Entity\User;
use App\Dto\CartAddResult;
use App\Entity\Discipline;
use App\Entity\Saison;
use App\Enum\CartResult;
use App\Repository\AbonnementRepository;
use App\Repository\CarteRepository;

class CartService
{
    private $session;
    private $security;
    private $abonnementRepository;
    private $carteRepository;

    public function __construct(RequestStack $requestStack, 
                                Security $security,
                                AbonnementRepository $abonnementRepository,
                                CarteRepository $carteRepository)
    {
        $this->session  = $requestStack->getSession();
        $this->security = $security;
        $this->abonnementRepository = $abonnementRepository;
        $this->carteRepository = $carteRepository;
    }

    /**
     * Retourne la structure complète du panier.
     */
    public function getCart(): array
    {
        return $this->session->get('cart', [
            'abonnements' => [],
            'cartes'      => [],
            'adhesion'    => null, // null = pas dans panier, array = présente
        ]);
    }

    /**
     * Ajoute un abonnement avec tarif choisi.
     */
    public function addAbonnement(Abonnement $abonnement, string $tarifChoice = 'normal', ?string $justificatifPath = null): CartAddResult
    {
        $user       = $this->security->getUser();
        $discipline = $abonnement->getDiscipline();
        $saison     = $abonnement->getSaison();

        $cart = $this->getCart();

        if ($user instanceof User && $this->isUserDejaAbonne($user, $discipline, $saison)) {
            return new CartAddResult(false, 'Vous avez déjà un abonnement actif pour cette discipline cette saison.');
        }

        if ($this->isAbonnementDejaDansPanier($cart, $abonnement)) {
            return new CartAddResult(false, 'Un abonnement pour cette discipline est déjà dans votre panier.');
        }

        foreach ($cart['abonnements'] as $row) {
            if ($row['id'] === $abonnement->getId()) {
                if ($row['tarif'] === $tarifChoice) {
                    return new CartAddResult(false, 'Cet abonnement est déjà dans votre panier avec le tarif sélectionné.');
                } else {
                    return new CartAddResult(false, 'Cet abonnement est déjà dans votre panier avec un autre tarif.');
                }
            }
        }

        $price = $abonnement->getTarif();
        if ($tarifChoice === 'reduit' && $abonnement->hasTarifReduit() && $abonnement->getTarifReduit() !== null) {
            $price = $abonnement->getTarifReduit();
        }

        $cart['abonnements'][] = [
            'id'          => $abonnement->getId(),
            'tarif'       => $tarifChoice,
            'price'       => $price,
        ];

        $this->checkAdhesion($cart);
        $this->session->set('cart', $cart);

        return new CartAddResult(true, 'Abonnement ajouté au panier.');
    }

    /**
     * Ajoute une carte avec tarif choisi.
     */
    public function addCarte(Carte $carte, string $tarifChoice = 'normal', ?string $justificatifPath = null): CartAddResult
    {
        $user = $this->security->getUser();
        $cart = $this->getCart();

        if ($tarifChoice === 'reduit') {
            if (!$carte->hasTarifReduit() || $carte->getTarifReduit() === null) {
                return new CartAddResult(false, 'Cette carte ne propose pas de tarif réduit.');
            }
        }

        // if ($user instanceof User && $this->isCarteConflitAvecAbonnementsUtilisateur($user, $carte)) {
        //     return new CartAddResult(false, 'Vous avez déjà un abonnement actif pour une discipline couverte par cette carte.');
        // }

        if ($this->isCarteConflitAvecCartesUtilisateur($user, $carte)) {
            return new CartAddResult(false, 'Vous avez deja cette carte.');
        }

        foreach ($cart['cartes'] as $row) {
            if ($row['id'] === $carte->getId()) {
                if ($row['tarif'] !== $tarifChoice) {
                    return new CartAddResult(false, 'Cette carte est déjà dans votre panier avec un autre tarif.');
                } else {
                    return new CartAddResult(false, 'Cette carte est déjà dans votre panier.');
                }
            }
        }

        $price = $carte->getTarif();
        if ($tarifChoice === 'reduit') {
            $price = $carte->getTarifReduit();
        }

        $cart['cartes'][] = [
            'id'           => $carte->getId(),
            'tarif'        => $tarifChoice,
            'price'        => $price,
        ];

        $this->checkAdhesion($cart);
        $this->session->set('cart', $cart);

        return new CartAddResult(true, 'Carte ajoutée au panier.');
    }

    /**
     * Ajoute l'adhésion si nécessaire (si user non connecté ou non adhérent).
     */
    private function checkAdhesion(array &$cart): void
    {
        $user = $this->security->getUser();
        $needsAdhesion = !($user instanceof User && $user->isAdherent());
        if ($needsAdhesion) {
            if (!$cart['adhesion']) {
                $cart['adhesion'] = true;
            }
        } else {
            $cart['adhesion'] = false;
        }
    }

    public function clear(): void
    {
        $this->session->remove('cart');
    }

    public function removeItem(string $type, int $id): void
    {
        $cart = $this->session->get('cart', []);

        if ($type === 'abonnement' && isset($cart['abonnements'])) {
            $cart['abonnements'] = array_values(array_filter($cart['abonnements'], fn($item) => $item['id'] != $id));
        }

        if ($type === 'carte' && isset($cart['cartes'])) {
            $cart['cartes'] = array_values(array_filter($cart['cartes'], fn($item) => $item['id'] != $id));
        }

        if ($type === 'adhesion') {
            unset($cart['adhesion']);
        }

        $this->session->set('cart', $cart);
    }

    private function isUserDejaAbonne(User $user, Discipline $discipline, Saison $saison): bool
    {
        foreach ($user->getAbonnementSouscrits() as $souscrit) {
            $ab = $souscrit->getAbonnement();
            if ($ab && $ab->getDiscipline() === $discipline && $souscrit->isValid()) {
                return true;
            }
        }
        return false;
    }

    private function isAbonnementDejaDansPanier(array $cart, Abonnement $abonnement): bool
    {
        foreach ($cart['abonnements'] as $row) {
            $ab = $this->abonnementRepository->find($row['id']);
            if ($ab && $ab->getDiscipline() === $abonnement->getDiscipline()) {
                return true;
            }
        }
        return false;
    }

    private function isCarteDejaDansPanier(array $cart, Carte $carte): bool
    {
        foreach ($cart['cartes'] as $row) {
            if ($row['id'] === $carte->getId()) {
                return true;
            }
        }
        return false;
    }

    private function hasDisciplineConflictWithCart(Carte $carte, array $cart): bool
    {
        foreach ($cart['abonnements'] as $row) {
            $ab = $this->abonnementRepository->find($row['id']);
            if ($ab && $carte->getDisciplines()->contains($ab->getDiscipline())) {
                return true;
            }
        }

        foreach ($cart['cartes'] as $row) {
            $otherCarte = $this->carteRepository->find($row['id']);
            if ($otherCarte && count(array_intersect(
                $carte->getDisciplines()->toArray(),
                $otherCarte->getDisciplines()->toArray()
            )) > 0) {
                return true;
            }
        }

        return false;
    }

    private function isCarteConflitAvecAbonnementsUtilisateur(User $user, Carte $carte): bool
    {
        foreach ($user->getAbonnementSouscrits() as $souscrit) {
            $ab = $souscrit->getAbonnement();
            if ($ab && $souscrit->isValid() && $carte->getDisciplines()->contains($ab->getDiscipline())) {
                return true;
            }
        }
        return false;
    }

    private function isCarteConflitAvecCartesUtilisateur(User $user, Carte $carte): bool
    {
        foreach ($user->getCarteSouscrites() as $souscrit) {
            $ab = $souscrit->getCarte();
            if ($ab && $souscrit->isValid() && $ab == $carte) {
                return true;
            }
        }
        return false;
    }

}
