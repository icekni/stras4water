<?php

namespace App\Controller;

use App\Repository\AbonnementRepository;
use App\Repository\CarteRepository;
use App\Repository\DisciplineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DanseController extends AbstractController
{
    #[Route('/bachata', name: 'bachata')]
    public function bachata(AbonnementRepository $abonnementRepository, CarteRepository $carteRepository, DisciplineRepository $disciplineRepository): Response
    {
        $discipline = $disciplineRepository->findOneBy(['nom' => 'Bachata']);

        return $this->render('front/danse/bachata.html.twig', [
            "abonnements" => $abonnementRepository->findBy([
                "discipline" => $discipline,
                "isActif" => true
            ]),
            "cartes" => $carteRepository->findByDiscipline($discipline),
        ]);
    }

    #[Route('/salsa', name: 'salsa')]
    public function salsa(): Response
    {
        return $this->render('front/danse/salsa.html.twig', []);
    }

    #[Route('/kizomba', name: 'kizomba')]
    public function kizomba(): Response
    {
        return $this->render('front/danse/kizomba.html.twig', []);
    }
}