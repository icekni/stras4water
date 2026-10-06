<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Abonnement;
use App\Entity\Carte;
use App\Entity\User;
use App\Dto\CartAddResult;
use App\Entity\Discipline;
use App\Entity\Saison;
use App\Repository\AbonnementRepository;
use App\Repository\CarteRepository;

class CartService
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly AbonnementRepository $abonnementRepository,
        private readonly CarteRepository $carteRepository,
    ) {
    }

    /**
     * Session récupérée à l'usage, jamais au constructeur :
     * évite "There is currently no session available" au cache:clear/warmup.
     */
    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * Retourne la structure complète du panier.
     */
    public function getCart(): array
    {
        return $this->getSession()->get('cart', [
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
        $this->getSession()->set('cart', $cart);

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

        // if ($this->isCarteConflitAvecCartesUtilisateur($user, $carte)) {
        //     return new CartAddResult(false, 'Vous avez deja cette carte.');
        // }

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
        $this->getSession()->set('cart', $cart);

        return new CartAddResult(true, 'Carte ajoutée au panier.');
    }

    /**
     * Ajoute l'adhésion si nécessaire (si user non connecté ou non adhérent).
     */
    private function checkAdhesion(array &$cart): void
    {
        $user = $this->security->getUser();
        $needsAdhesion = !($user instanceof User && $user->getAdhesion() !== null);
        if ($needsAdhesion) {
            if (!$cart['adhesion']) {
                $cart['adhesion'] = true;
            }
        } else {
            $cart['adhesion'] = false;
        }
    }

    public function addAdhesion(): CartAddResult
    {
        $user = $this->security->getUser();

        // Déjà adhérent
        if ($user instanceof User && $user->getAdhesion() !== null) {
            return new CartAddResult(
                false,
                'Vous êtes déjà adhérent de Stras4Water.'
            );
        }

        $cart = $this->getCart();

        // Déjà dans le panier
        if ($cart['adhesion']) {
            return new CartAddResult(
                false,
                'L’adhésion est déjà dans votre panier.'
            );
        }

        $cart['adhesion'] = true;

        $this->getSession()->set('cart', $cart);

        return new CartAddResult(
            true,
            'Adhésion ajoutée au panier.'
        );
    }

    public function clear(): void
    {
        $this->getSession()->remove('cart');
    }

    public function removeItem(string $type, int $id): void
    {
        $session = $this->getSession();
        $cart = $session->get('cart', []);

        if ($type === 'abonnement' && isset($cart['abonnements'])) {
            $cart['abonnements'] = array_values(array_filter($cart['abonnements'], fn($item) => $item['id'] != $id));
        }

        if ($type === 'carte' && isset($cart['cartes'])) {
            $cart['cartes'] = array_values(array_filter($cart['cartes'], fn($item) => $item['id'] != $id));
        }

        if ($type === 'adhesion') {
            unset($cart['adhesion']);
        }

        $session->set('cart', $cart);
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

    public function getCount(): int
    {
        $cart = $this->getCart();

        $count = count($cart['abonnements']) + count($cart['cartes']);

        if (!empty($cart['adhesion'])) {
            $count++;
        }

        return $count;
    }

    /**
     * Réévalue la ligne adhésion après un changement d'état de connexion.
     * À appeler au login (listener) : si l'utilisateur est déjà adhérent,
     * on retire l'adhésion du panier — il ne doit pas la repayée.
     */
    public function refreshAdhesion(): void
    {
        $user = $this->security->getUser();

        if (!($user instanceof User)) {
            return; // pas connecté : on ne touche à rien
        }

        $session = $this->getSession();
        $cart = $session->get('cart', []);
        if ($cart === []) {
            return;
        }

        if ($user->getAdhesion() !== null) {
            // Déjà adhérent → plus de ligne adhésion
            $cart['adhesion'] = false;
        } elseif (!empty($cart['abonnements']) || !empty($cart['cartes'])) {
            // Non adhérent mais articles → adhésion obligatoire
            $cart['adhesion'] = true;
        }
        // cas non adhérent + panier sans article : on laisse l'état tel quel
        // (l'adhésion seule peut être volontaire, cf. randonneurs)

        $session->set('cart', $cart);
    }
}