<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use App\Form\DonationType;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(RecuFiscalService $recuFiscalService): Response
    {
        // $user = new User();
        // $user->setNom("Josso");
        // $user->setPrenom("Cédric");
        // $user->setAdresse("48B route de Bischwiller");
        // $dateDon = new DateTimeImmutable();

        // $pdfContent = $recuFiscalService->generate("RF2025-00001", 
        //                                             "Josso",
        //                                             "Cédric",
        //                                             "48 B",
        //                                             "Route de Bischwiller",
        //                                             "67300",
        //                                             "Schiltigheim",
        //                                             "FRANCE",
        //                                             "50.05", 
        //                                             new DateTimeImmutable(), 
        //                                             TypeDon::NUMERAIRE, 
        //                                             MoyenPaiement::CARTE);

        // return new Response($pdfContent, 200, [
        //     'Content-Type' => 'application/pdf',
        //     'Content-Disposition' => 'inline; filename="recu_fiscal.pdf"', // ou "attachment;" pour forcer le téléchargement
        // ]);

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

    #[Route('/donation', name: 'donation')]
    public function donation(Request $request, EntityManagerInterface $entityManager, HttpClientInterface $httpClient): Response
    {
        $donation = new Donation();

        // Préremplir l'utilisateur s'il est connecté
        if ($this->getUser()) {
            $donation->setUser($this->getUser());
        }

        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            $entityManager->flush();
            
            $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);

            $product = $stripe->products->create([
                'name' => 'Don Stras4Water ' . $donation->getMontant() . '€',
            ]);
            $price = $stripe->prices->create([
                'unit_amount' => $donation->getMontant() * 100,
                'currency' => 'eur',
                'product' => $product->id,
            ]);

            $session = $stripe->checkout->sessions->create([
                'success_url' => $this->generateUrl('donation_success', [], 0),
                // 'return_url' => $this->generateUrl('donation', [], 0),
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
                        'don_id' => $donation->getId(),
                    ]
                ],
            ]);

            return $this->redirect($session->url, 303);
        }

        return $this->render('front/donation.html.twig', [
            'form' => $form->createView(),
        ]);
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

    // #[Route('/donationValidate', name: 'donationValidate', methods: ['GET'])]
    // public function donationValidate(Request $request, RecuFiscalService $recuFiscalService, DonationRepository $donationRepository, EntityManagerInterface $entityManager): Response
    // {
    //     $donation = $donationRepository->findOneBy(['checkoutId' => $request->query->get('checkoutIntentId')]);

    //     if ($request->query->get('code') != 'succeeded') {
    //         $donation->setStatus(DonationStatus::CANCELLED);
    //         $this->addFlash(
    //             'danger',
    //             'Une erreur s\'est produite!'
    //         );
    //     }
    //     else 
    //     {
    //         $anneeEnCours = new DateTimeImmutable();
    //         $numeroOrdre = 'RF' . $anneeEnCours->format('Y') . '-' . sprintf('%06d', $donationRepository->countByYear($anneeEnCours->format('Y')));
    //         $donation = $recuFiscalService->generate($donation, $numeroOrdre);

    //         $donation->setStatus(DonationStatus::PAID);
    //         $entityManager->persist($donation);
    //         $entityManager->flush();
            
    //         $this->addFlash(
    //             'success',
    //             'Votre don à bien été enregistré. Si le paiement est validé par l\'organisme, vous recevrez par mail votre recu fiscal.'
    //         );
    //     }
        
    //     // return new Response($pdfContent, 200, [
    //     //     'Content-Type' => 'application/pdf',
    //     //     'Content-Disposition' => 'inline; filename="recu_fiscal.pdf"', // ou "attachment;" pour forcer le téléchargement
    //     // ]);

    //     return $this->redirectToRoute('donation');
    // }

    // #[Route('/donationCancel', name: 'donationCancel')]
    // public function donationCancel(): Response
    // {
    //     $this->addFlash(
    //         'danger',
    //         'Une erreur s\'est produite.'
    //     );

    //     return $this->redirectToRoute('donation');
    // }
}