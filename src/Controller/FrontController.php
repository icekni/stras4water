<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // $user = new User();
        // $user->setNom("Josso");
        // $user->setPrenom("Cédric");
        // $user->setAdresse("48B route de Bischwiller");
        // $dateDon = new DateTimeImmutable();

        // $pdfContent = $recuFiscalService->generate("RF2025-00001", $user, "50.05", $dateDon, new DateTimeImmutable(), TypeDon::NUMERAIRE, MoyenPaiement::CASH);

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

    #[Route('/abonnement', name: 'abonnement')]
    public function abonnement(): Response
    {
        return $this->render('front/abonnement.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }
}