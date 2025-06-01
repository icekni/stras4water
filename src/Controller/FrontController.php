<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Form\DonationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/bachata', name: 'bachata')]
    public function abonnement(): Response
    {
        return $this->render('front/bachata.html.twig', []);
    }

    #[Route('/donation', name: 'donation')]
    public function donation(Request $request): Response
    {
        $don = new Donation();

        $form = $this->createForm(DonationType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dd($form);
            // $don est automatiquement rempli avec les données du formulaire
            // Tu peux maintenant le persister
        }

        return $this->render('front/donation.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}