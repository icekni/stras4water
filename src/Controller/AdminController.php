<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\Lien;
use App\Enum\DonationStatus;
use App\Form\DonManuelType;
use App\Form\LienType;
use App\Repository\DonationRepository;
use App\Repository\LienRepository;
use App\Repository\SeanceEssaiRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\QrCodeGenerator;
use App\Service\RecuFiscalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            $pdfPath = $donation->getUrlRecuFiscal();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable.');
        }

        return new BinaryFileResponse($pdfPath, 200, [
            'Content-Type' => 'application/pdf',
        ], false, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/admin/utilisateurs/search', name: 'admin_user_search')]
    public function search(Request $request, UserRepository $userRepo, SeanceEssaiRepository $essaiRepo): JsonResponse
    {
        $query = $request->query->get('q', '');
        $disciplineId = $request->query->get('discipline');
        $results = [];

        if (!$query || !$disciplineId) {
            return $this->json([]);
        }

        if (mb_strlen($query) >= 2) {
            $users = $userRepo->findBySimilarName($query);
            $essais = $essaiRepo->findBySimilarName($query);

            foreach ($users as $user) {
                $results[] = [
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'type' => 'Compte',
                ];
            }

            foreach ($essais as $essai) {
                $results[] = [
                    'nom' => $essai->getNom(),
                    'prenom' => $essai->getPrenom(),
                    'email' => null,
                    'type' => 'Essai',
                ];
            }
        }

        return $this->json($results);
    }
    
    #[Route('/admin/liens', name: 'admin_lien_index')]
    public function liens_index(LienRepository $repo): Response
    {
        return $this->render('admin/lien/index.html.twig', [
            'liens' => $repo->findAll(),
        ]);
    }

    #[Route('/admin/lien/new', name: 'admin_lien_new')]
    public function lien_new(Request $request, EntityManagerInterface $em, QrCodeGenerator $qrCodeGenerator): Response
    {
        $lien = new Lien();

        $form = $this->createForm(LienType::class, $lien);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $qrPath = $qrCodeGenerator->generate($lien);
            $lien->setQrCodePath($qrPath);
            $em->persist($lien);
            $em->flush();

            return $this->redirectToRoute('admin_lien_index');
        }

        return $this->render('admin/lien/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $lien->getId() !== null,
        ]);
    }

    #[Route('/admin/lien/{id}/edit', name: 'admin_lien_edit')]
    public function lien_edit(Lien $lien, Request $request, EntityManagerInterface $em, QrCodeGenerator $qrCodeGenerator): Response
    {
        $form = $this->createForm(LienType::class, $lien);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {     
            $qrPath = $qrCodeGenerator->generate($lien);
            $lien->setQrCodePath($qrPath);
            $em->persist($lien);
            $em->flush();

            return $this->redirectToRoute('admin_lien_index');
        }

        return $this->render('admin/lien/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $lien->getId() !== null,
        ]);
    }

    #[Route('/admin/lien/{id}/delete', name: 'admin_lien_delete', methods: ['POST'])]
    public function lien_delete(Request $request, Lien $lien, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_lien_' . $lien->getId(), $request->request->get('_token'))) {
            $em->remove($lien);
            $em->flush();
        }

        return $this->redirectToRoute('admin_lien_index');
    }

    #[Route('/admin/lien/{id}', name: 'admin_lien_show')]
    public function stats(Lien $lien): Response
    {
        $clics = $lien->getClics();
        $dateFrom = (new \DateTimeImmutable('-60 days'))->setTime(0, 0);
        $dateTo = new \DateTimeImmutable('today'); 

        $clicsParJour = [];
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($dateFrom, $interval, $dateTo->modify('+1 day'));

        foreach ($period as $date) {
            $jour = $date->format('Y-m-d');
            $clicsParJour[$jour] = 0;
        }

        foreach ($clics as $clicDate) {
            if ($clicDate >= $dateFrom) {
                $jour = $clicDate->format('Y-m-d');
                $clicsParJour[$jour] = ($clicsParJour[$jour] ?? 0) + 1;
            }
        }

        ksort($clicsParJour);

        return $this->render('admin/lien/stats.html.twig', [
            'lien' => $lien,
            'clics' => $clicsParJour,
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
            $pdfPath = $donation->getUrlRecuFiscal();

        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable.');
        }

        return new BinaryFileResponse($pdfPath, 200, [
            'Content-Type' => 'application/pdf',
        ], false, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
