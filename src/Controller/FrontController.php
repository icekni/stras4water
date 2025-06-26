<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use App\Form\DonationType;
use App\Form\UserType;
use App\Repository\DonationRepository;
use App\Service\CountryCodeService;
use App\Service\HelloAssoTokenService;
use App\Service\RecuFiscalService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\BillingPortal\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('front/home.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    #[Route('/adhesion', name: 'adhesion')]
    public function adhesion(): Response
    {
        return $this->render('front/adhesion.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    #[Route('/bachata', name: 'bachata')]
    public function bachata(): Response
    {
        return $this->render('front/bachata.html.twig', []);
    }

    #[Route('/salsa', name: 'salsa')]
    public function salsa(): Response
    {
        return $this->render('front/salsa.html.twig', []);
    }

    #[Route('/kizomba', name: 'kizomba')]
    public function kizomba(): Response
    {
        return $this->render('front/kizomba.html.twig', []);
    }

    #[Route('/anglais', name: 'anglais')]
    public function anglais(): Response
    {
        return $this->render('front/anglais.html.twig', []);
    }

    #[Route('/espagnol', name: 'espagnol')]
    public function espagnol(): Response
    {
        return $this->render('front/espagnol.html.twig', []);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig', []);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig', []);
    }

    #[Route('/events', name: 'events')]
    public function events(): Response
    {
        return $this->render('front/events.html.twig', []);
    }

    #[Route('/ml', name: 'ml')]
    public function ml(): Response
    {
        return $this->render('front/ml.html.twig', []);
    }

    #[Route('/confidentialite', name: 'confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('front/confidentialite.html.twig', []);
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
        $isAdherent = $user->isAdherent(); 
        $saisonEnCours = '2024/2025'; // dynamique si besoin

        return $this->render('front/compte.html.twig', [
            'userForm' => $form,
            'donations' => $donations,
            'isAdherent' => $isAdherent,
            'saisonEnCours' => $saisonEnCours
        ]);
    }

}