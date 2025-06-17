<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Form\DonManuelType;
use App\Repository\DonationRepository;
use App\Service\EmailService;
use App\Service\RecuFiscalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(DonationRepository $donationRepository): Response
    {
        $interval = 30;
        $montantTotal = array_reduce($donationRepository->getLastDonations($interval), function($total, $don)
        {
            return $total + round($don->getMontantNet(), 2);
        });

        return $this->render('admin/index.html.twig', [
            'montantTotal' => $montantTotal ?? 0,
            'donationInterval' => $interval,
        ]);
    }

    #[Route('/admin/dons', name: 'admin_dons')]
    public function admin_dons(Request $request, DonationRepository $donationRepository): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = $donationRepository->count([]);
        $dons = $donationRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        return $this->render('admin/dons.html.twig', [
            'dons' => $dons,
            'page' => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    #[Route('/admin/don/create', name: 'admin_don_create')]
    public function admin_don_create(Request $request, EmailService $emailService, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();

        $form = $this->createForm(DonManuelType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            
            if ($donation && $donation->isWantsRecuFiscal()) {
                $token = bin2hex(random_bytes(32));
                $donation->setToken($token);
                $donation->setStatus(DonationStatus::PAID);
                
                $url = $this->generateUrl('fillFiscalData', ['token' => $token ], UrlGeneratorInterface::ABSOLUTE_URL);
                $emailService->sendRequestFiscalData($donation, $url);
            }
            elseif ($donation && !$donation->isWantsRecuFiscal()) {
                $donation->setStatus(DonationStatus::COMPLETED);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le don de ' . $donation->getMontant() .'€ a bien été enregistré');

            return $this->redirectToRoute('admin_don_create');
        }

        return $this->render('admin/don_manuel.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/dons/refund/{id}', name: 'admin_dons_refund')]
    public function admin_dons_refund(
        Donation $donation,
        EntityManagerInterface $entityManager,
        RecuFiscalService $recuFiscalService,
    ): Response
    {
        if (!$donation->getCheckoutId()) {
            $this->addFlash('danger', 'Impossible d’annuler : aucun identifiant Stripe.');
            return $this->redirectToRoute('admin_dons');
        }
        
        $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);

        try {
            $stripe->refunds->create([
                'payment_intent' => $donation->getCheckoutId(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('admin_dons');
        }

        // Annulation du reçu fiscal
        $recuFiscalService->invalidate($donation);

        // Mise à jour de l’état
        $donation->setStatus(DonationStatus::REFUNDED);
        $entityManager->flush();

        $this->addFlash('success', 'Don annulé et remboursé avec succès.');
        return $this->redirectToRoute('admin_dons');
    }

    #[Route('/admin/dons/resend/{id}', name: 'admin_dons_resend')]
    public function admin_dons_resend(
        Request $request,
        Donation $donation,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
    ): Response
    {
        $email = $request->request->get('email');
        if ($donation->getEmail() !== $email) {
            $donation->setEmail($email);
        }
        
        $token = bin2hex(random_bytes(32));
        $donation->setToken($token);

        $url = $this->generateUrl('fillFiscalData', ['token' => $token ], UrlGeneratorInterface::ABSOLUTE_URL);
        $emailService->sendRequestFiscalData($donation, $url);

        $entityManager->flush();

        $this->addFlash('success', 'L\'email permettant la génération du recu fiscal a été à nouveau envoyé.');

        return $this->redirectToRoute('admin_dons');
    }

    #[Route('/admin/get_recu_fiscal/{id}', name: 'admin_get_recu_fiscal')]
    public function admin_get_recu_fiscal(Donation $donation): Response
    {
            $pdfPath = $donation->getUrlRecuFiscal(); // ex: /var/www/html/Stras4water/public/recuFiscaux/...

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable.');
        }

        return new BinaryFileResponse($pdfPath, 200, [
            'Content-Type' => 'application/pdf',
        ], false, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
