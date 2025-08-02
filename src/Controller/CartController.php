<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\AbonnementSouscrit;
use App\Entity\Carte;
use App\Entity\CarteSouscrite;
use App\Enum\Statut;
use App\Repository\AbonnementRepository;
use App\Repository\AbonnementSouscritRepository;
use App\Repository\CarteRepository;
use App\Repository\CarteSouscriteRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart/add', name: 'cart_add_item', methods: ['POST'])]
    public function addItem(
        Request $request,
        AbonnementRepository $abonnementRepository,
        CarteRepository $carteRepository,
        CartService $cartService
    ): Response {
        $itemValue   = $request->request->get('item');
        $tarifChoice = $request->request->get('tarif', 'normal');
        $justifFile  = $request->files->get('justificatif');

        $justificatifPath = null;
        if ($justifFile) {
            $justificatifPath = $justifFile->getClientOriginalName(); // TODO : gérer upload réel
        }

        if (!$itemValue) {
            $this->addFlash('danger', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('cart_show');
        }

        [$type, $id] = explode('-', $itemValue);

        if ($type === 'abonnement') {
            $abonnement = $abonnementRepository->find($id);
            if (!$abonnement) {
                $this->addFlash('danger', 'Abonnement introuvable.');
                return $this->redirectToRoute('cart_show');
            }
            $result = $cartService->addAbonnement($abonnement, $tarifChoice, $justificatifPath);
        } elseif ($type === 'carte') {
            $carte = $carteRepository->find($id);
            if (!$carte) {
                $this->addFlash('danger', 'Carte introuvable.');
                return $this->redirectToRoute('cart_show');
            }
            $result = $cartService->addCarte($carte, $tarifChoice, $justificatifPath);
        } else {
            $this->addFlash('danger', 'Type invalide.');
            return $this->redirectToRoute('cart_show');
        }

        $this->addFlash($result->success ? 'success' : 'warning', $result->message);
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart', name: 'cart_show')]
    public function show(CartService $cartService, EntityManagerInterface $em): Response
    {
        $cart = $cartService->getCart();

        // Récupération et affichage comme avant
        $abIds = array_map(fn($row) => $row['id'], $cart['abonnements']);
        $abEntities = $abIds ? $em->getRepository(Abonnement::class)->findBy(['id' => $abIds]) : [];

        $abIndex = [];
        foreach ($abEntities as $ab) {
            $abIndex[$ab->getId()] = $ab;
        }

        $abView = [];
        foreach ($cart['abonnements'] as $row) {
            $ab = $abIndex[$row['id']] ?? null;
            if (!$ab) { continue; }
            $abView[] = [
                'entity'      => $ab,
                'tarifChoice' => $row['tarif'],
                'price'       => $row['price'],
                'discipline'  => $ab->getDiscipline()?->getNom(),
            ];
        }

        $cIds = array_map(fn($row) => $row['id'], $cart['cartes']);
        $cEntities = $cIds ? $em->getRepository(Carte::class)->findBy(['id' => $cIds]) : [];

        $cIndex = [];
        foreach ($cEntities as $c) {
            $cIndex[$c->getId()] = $c;
        }

        $cView = [];
        foreach ($cart['cartes'] as $row) {
            $carte = $cIndex[$row['id']] ?? null;
            if (!$carte) { continue; }
            $discNames = array_map(fn($d) => $d->getNom(), $carte->getDisciplines()->toArray());
            $cView[] = [
                'entity'      => $carte,
                'tarifChoice' => $row['tarif'],
                'price'       => $row['price'],
                'discipline'  => implode(', ', $discNames),
            ];
        }

        $adhesion = $cart['adhesion'];
        $total = array_sum(array_map(fn($a) => $a['price'], $abView))
            + array_sum(array_map(fn($c) => $c['price'], $cView))
            + ($adhesion ? 10 : 0); //TODO recuperer le tarif d'adhesion depuis la config

        return $this->render('cart/show.html.twig', [
            'abonnements' => $abView,
            'cartes'      => $cView,
            'adhesion'    => $adhesion,
            'total'       => $total,
        ]);
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(CartService $cartService): Response
    {
        $cartService->clear();
        $this->addFlash('info', 'Panier vidé.');
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/remove/{type}/{id}', name: 'cart_remove_item')]
    public function removeItem(string $type, int $id, CartService $cartService): Response
    {
        $cartService->removeItem($type, $id);
        $this->addFlash('success', 'L\'élément a bien été supprimé du panier.');
        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/checkout', name: 'cart_checkout')]
    public function checkout(
        CartService $cartService, 
        Security $security,
        AbonnementRepository $abonnementRepository,
        CarteRepository $carteRepository,
        EntityManagerInterface $em): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez créer un compte ou être connecté pour valider votre panier.');
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartService->getCart();
        $total = 0.0;

        $abonnements = [];
        foreach ($cart['abonnements'] as $abonnementCart) {
            $abonnement = $abonnementRepository->find($abonnementCart['id']);

            $total += $abonnementCart['tarif'] === 'reduit'
                ? $abonnement->getTarifReduit()
                : $abonnement->getTarif();

            $abonnementSouscrit = new AbonnementSouscrit();
            $abonnementSouscrit->setUser($user);
            $abonnementSouscrit->setAbonnement($abonnement);
            $abonnementSouscrit->setStatut(Statut::CREATED);
            $abonnementSouscrit->setIsTarifReduit($abonnementCart['tarif'] === 'reduit');

            $em->persist($abonnementSouscrit);

            $abonnements[] = $abonnementSouscrit;
        }

        $cartes = [];
        foreach ($cart['cartes'] as $carteCart) {
            $carte = $carteRepository->find($carteCart['id']);

            $total += $carteCart['tarif'] === 'reduit'
                ? $carte->getTarifReduit()
                : $carte->getTarif();

            $carteSouscrite = new CarteSouscrite();
            $carteSouscrite->setUser($user);
            $carteSouscrite->setCarte($carte);
            $carteSouscrite->setStatut(Statut::CREATED);
            $carteSouscrite->setIsTarifReduit($carteCart['tarif'] === 'reduit');

            $em->persist($carteSouscrite);

            $cartes[] = $carteSouscrite;
        }
        $em->flush();
        $carteIds = implode(',', array_map(
            fn($souscription) => $souscription->getId(),
            $cartes
        ));
        $abonnementIds = implode(',', array_map(
            fn($souscription) => $souscription->getId(),
            $abonnements
        ));

        if ($cart['adhesion']) {
            $total += 10; // TODO recuperer depuis la config
        }

        $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);

        $product = $stripe->products->create([
            'name' => 'Panier Stras4Water ' . $total . '€', // TODO creer a la volée les produits depuis la configuration
        ]);
        $price = $stripe->prices->create([
            'unit_amount' => $total * 100,
            'currency' => 'eur',
            'product' => $product->id,
        ]);

        $session = $stripe->checkout->sessions->create([
            'success_url' => $this->generateUrl('donation_success', [], 0),
            'cancel_url' => $this->generateUrl('donation_cancel', [], 0),
            'line_items' => [
                [
                'price' => $price->id,
                'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'customer' => $_ENV['STRIPE_ANONYMOUS_CUSTOMER_ID'],
            'payment_intent_data' => [
                'metadata' => [
                    'user_id' => $user->getId(),
                    'adhesion' => $cart['adhesion'],
                    'abonnement_ids' => $abonnementIds,
                    'carte_ids' => $carteIds,
                ]
            ],
        ]);

        return $this->redirect($session->url, 303);
    }
}
