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

    #[Route('/donation', name: 'donation')]
    public function donation(Request $request, EntityManagerInterface $entityManager, HttpClientInterface $httpClient, HelloAssoTokenService $helloAssoTokenService, CountryCodeService $countryCodeService): Response
    {
        $donation = new Donation();

        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donation->setDate(new DateTimeImmutable());
            $donation->setStatus(DonationStatus::CREATED);
            $donation->setTypeDon(TypeDon::NUMERAIRE);
            $donation->setMoyenPaiement(MoyenPaiement::CARTE);
            
            $backUrl = $this->generateUrl('donation', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $backUrl = preg_replace('/^http:/', 'https:', $backUrl);
            
            $returnUrl = $this->generateUrl('donationValidate', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $returnUrl = preg_replace('/^http:/', 'https:', $returnUrl);
            
            $errorUrl = $this->generateUrl('donationCancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $errorUrl = preg_replace('/^http:/', 'https:', $errorUrl);
            
            $payload = [
                "totalAmount" => intval($donation->getMontant() * 100),
                "initialAmount" => intval($donation->getMontant() * 100),
                "currency" => "EUR",
                "itemName" => "Don par carte - ID: " . $donation->getId(),
                "backUrl" => $backUrl,
                "returnUrl" => $returnUrl,
                "ErrorUrl" => $errorUrl,
                "containsDonation" => true,
                "metadata" => [
                    "donation_id" => $donation->getId(),
                ],
                "manualContribution" => [
                    "amount" => 0,
                ],
                "payer" => [
                    "firstName" => $donation->getPrenom(),
                    "lastName" => $donation->getNom(),
                    "dateOfBirth" => $donation->getDateDeNaissance()->format('Y-m-d'),
                    "email" => $donation->getEmail(),
                    "address" => $donation->getAdresseNumero() . " " . $donation->getAdresseRue(),
                    "city" => $donation->getAdresseVille(),
                    "zipcode" => $donation->getAdresseCodePostal(),
                    "country" => $countryCodeService->getIsoCodes($donation->getAdressePays()),
                    // "acceptContributions" => false
                ],
                "customContribution" => [
                    "amount" => 0
                ],
            ];
                
            $response = $httpClient->request('POST', 'https://api.helloasso-sandbox.com/v5/organizations/stras4water/checkout-intents', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $helloAssoTokenService->getValidAccessToken(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            $data = $response->toArray();            
            
            $donation->setCheckoutId($data['id']);

            $entityManager->persist($donation);
            $entityManager->flush();

            return $this->redirect($data['redirectUrl']);
        }

        return $this->render('front/donation.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/donationValidate', name: 'donationValidate', methods: ['GET'])]
    public function donationValidate(Request $request, RecuFiscalService $recuFiscalService, DonationRepository $donationRepository, EntityManagerInterface $entityManager): Response
    {
        $donation = $donationRepository->findOneBy(['checkoutId' => $request->query->get('checkoutIntentId')]);

        if ($request->query->get('code') != 'succeeded') {
            $donation->setStatus(DonationStatus::CANCELLED);
            $this->addFlash(
                'danger',
                'Une erreur s\'est produite!'
            );
        }
        else 
        {
            $anneeEnCours = new DateTimeImmutable();
            $numeroOrdre = 'RF' . $anneeEnCours->format('Y') . '-' . sprintf('%06d', $donationRepository->countByYear($anneeEnCours->format('Y')));
            $donation = $recuFiscalService->generate($donation, $numeroOrdre);

            $donation->setStatus(DonationStatus::PENDING);
            $entityManager->persist($donation);
            $entityManager->flush();
            
            $this->addFlash(
                'success',
                'Votre don à bien été enregistré. Si le paiement est validé par l\'organisme, vous recevrez par mail votre recu fiscal.'
            );
        }
        
        // return new Response($pdfContent, 200, [
        //     'Content-Type' => 'application/pdf',
        //     'Content-Disposition' => 'inline; filename="recu_fiscal.pdf"', // ou "attachment;" pour forcer le téléchargement
        // ]);

        return $this->redirectToRoute('donation');
    }

    #[Route('/donationCancel', name: 'donationCancel')]
    public function donationCancel(): Response
    {
        $this->addFlash(
            'danger',
            'Une erreur s\'est produite.'
        );

        return $this->redirectToRoute('donation');
    }
}