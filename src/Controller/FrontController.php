<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Service\RecuFiscalService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function abonnement(): Response
    {
        return $this->render('front/bachata.html.twig', []);
    }

    #[Route('/donation', name: 'donation')]
    public function donation(Request $request, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();

        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donation->setDate(new DateTimeImmutable());
            $donation->setStatus(DonationStatus::CREATED);
            $donation->setTypeDon(TypeDon::NUMERAIRE);
            $donation->setMoyenPaiement(MoyenPaiement::CARTE);
            // TODO checkoutId à remplacer par le retour de helloasso

            $entityManager->persist($donation);
            $entityManager->flush();

            // TODO rediction vers paiement helloasso qui lui redirige vers validate
            return $this->redirectToRoute('donationValidate', [
                'checkoutId' => $donation->getId(),
                'error' => null,
            ]);
        }
        return $this->render('front/donation.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/donationValidate', name: 'donationValidate', methods: ['GET'])]
    public function donationValidate(Request $request, RecuFiscalService $recuFiscalService, DonationRepository $donationRepository, EntityManagerInterface $entityManager): Response
    {
        // TODO a remplacer par le retour de helloasso
        $donation = $donationRepository->findOneBy(['id' => $request->query->get('checkoutId')]);

        if ($request->query->get('error') != null) {
            $donation->setStatus(DonationStatus::CANCELLED);
            $this->addFlash(
                'danger',
                'Votre don à bien été enregistré. Si le paiement est validé par l\'organisme, vous recevrez par mail votre recu fiscal.'
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
}