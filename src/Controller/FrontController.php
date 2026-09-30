<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\User;
use App\Form\UserType;
use App\Service\EmailService;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class FrontController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('front/static/home.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    #[Route('/rando', name: 'rando')]
    public function rando(): Response
    {
        return $this->render('front/rando.html.twig', []);
    }

    #[Route('/adhesion/ajouter', name: 'cart_add_adhesion', methods: ['POST'])]
    public function addAdhesion(
        CartService $cartService
    ): Response {
        $result = $cartService->addAdhesion();

        $this->addFlash(
            $result->success ? 'success' : 'warning',
            $result->message
        );

        return $this->redirectToRoute('adhesion');
    }

    #[Route('/adhesion', name: 'adhesion')]
    public function adhesion(): Response
    {
        return $this->render('front/adhesion.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/static/about.html.twig', []);
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, EmailService $emailService): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact_form', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $nom = $request->request->get('name');
            $from = $request->request->get('email');
            $subject = $request->request->get('subject');
            $text = $request->request->get('message');

            try {
                $emailService->sendMail($nom, $from, $subject, $text);
                $this->addFlash('success', 'Votre message a été envoyé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l’envoi du message.');
            }

            return $this->redirectToRoute('contact');
        }

        return $this->render('front/static/contact.html.twig', []);
    }

    #[Route('/events', name: 'events')]
    public function events(): Response
    {
        return $this->render('front/events.html.twig', []);
    }

    #[Route('/ml', name: 'ml')]
    public function ml(): Response
    {
        return $this->render('front/static/ml.html.twig', []);
    }

    #[Route('/confidentialite', name: 'confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('front/static/confidentialite.html.twig', []);
    }

    #[Route('/donation_success', name: 'donation_success')]
    public function donation_success(): Response
    {
        $this->addFlash('success', 'Votre don a bien été enregistré. Si vous avez demandé un recu fiscal, vous recevrez bientot un email permettant de le générer.');

        return $this->redirectToRoute('donation');
    }

    #[Route('/donation_cancel', name: 'donation_cancel')]
    public function donation_cancel(): Response
    {
        $this->addFlash('danger', 'Une erreur s\'est produite. Vous ne serez pas débité.');

        return $this->redirectToRoute('donation');
    }

    #[Route('/mon-compte', name: 'app_account')]
    public function app_account(Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
    {
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_account');
        }

        $donations = $em->getRepository(Donation::class)->findBy(['user' => $user]);

        // statut adhérent à calculer selon ta logique (adhésion en cours dans la saison...)
        $isAdherent = $user->getAdhesion() !== null; 
        $saisonEnCours = '2026/2027'; // dynamique si besoin

        return $this->render('front/compte.html.twig', [
            'userForm' => $form,
            'abonnements' => $user->getAbonnementSouscrits(),
            'cartes' => $user->getCarteSouscrites(),
            'donations' => $donations,
            'isAdherent' => $isAdherent,
            'saisonEnCours' => $saisonEnCours
        ]);
    }
}