<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use App\Form\DonationType;
use App\Form\FiscalDataType;
use App\Repository\DonationRepository;
use App\Service\RecuFiscalService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DonationController extends AbstractController
{
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
            $donation->setMoyenPaiement(MoyenPaiement::CARTE);
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

            $entityManager->flush();

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

    #[Route('/get_recu_fiscal', name: 'get_recu_fiscal')]
    public function get_recu_fiscal(): Response
    {
        return $this->redirectToRoute('donation');
    }

    #[Route('/fillFiscalData/{token}', name: 'fillFiscalData')]
    public function fillFiscalData(string $token, Request $request, DonationRepository $donationRepository, EntityManagerInterface $em, RecuFiscalService $recuFiscalService): Response
    {
        $donation = $donationRepository->findOneBy(['token' => $token]);
        if (!$donation) {
            throw $this->createNotFoundException('Token invalide.');
        }

        if ($donation->getUrlRecuFiscal()) {
            return new Response(file_get_contents($donation->getUrlRecuFiscal()), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="recu-fiscal.pdf"',
            ]);
        }

        $form = $this->createForm(FiscalDataType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $anneeEnCours = new DateTimeImmutable();
            $donation->setNumeroOrdreRF('RF' . $anneeEnCours->format('Y') . '-' . sprintf('%06d', $donationRepository->countByYear($anneeEnCours->format('Y'))));
            $donation = $recuFiscalService->generate($donation,
                                                    TypeDon::NUMERAIRE,
                                                    MoyenPaiement::CARTE,
                                                    $form->get('nom')->getData(),
                                                    $form->get('prenom')->getData(),
                                                    $form->get('numero_rue')->getData(),
                                                    $form->get('rue')->getData(),
                                                    $form->get('code_postal')->getData(),
                                                    $form->get('ville')->getData(),
                                                    $form->get('pays')->getData(),
            );

            $donation->setStatus(DonationStatus::COMPLETED);

            $em->flush();

            return new Response(file_get_contents($donation->getUrlRecuFiscal()), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="recu-fiscal.pdf"',
            ]);
        }

        return $this->render('front/fiscal_data.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
