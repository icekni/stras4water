<?php

namespace App\Controller;

use App\Repository\AbonnementRepository;
use App\Repository\CarteRepository;
use App\Repository\DisciplineRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class LangueController extends AbstractController
{
    #[Route('/anglais', name: 'anglais')]
    public function anglais(AbonnementRepository $abonnementRepository, CarteRepository $carteRepository, DisciplineRepository $disciplineRepository): Response
    {
        $discipline = $disciplineRepository->findOneBy(['nom' => 'Anglais']);

        $abonnements = [];
        $cartes = [];

        if ($discipline) {
            $abonnements = $abonnementRepository->findBy([
                "discipline" => $discipline,
                "isActif" => true
            ]);
            $cartes = $carteRepository->findByDiscipline($discipline);
        }

        return $this->render('front/langue/anglais.html.twig', [
            "abonnements" => $abonnements,
            "cartes" => $cartes,
        ]);
    }

    #[Route('/espagnol', name: 'espagnol')]
    public function espagnol(AbonnementRepository $abonnementRepository, CarteRepository $carteRepository, DisciplineRepository $disciplineRepository): Response
    {
        $discipline = $disciplineRepository->findOneBy(['nom' => 'Espagnol']);

        $abonnements = [];
        $cartes = [];

        if ($discipline) {
            $abonnements = $abonnementRepository->findBy([
                "discipline" => $discipline,
                "isActif" => true
            ]);
            $cartes = $carteRepository->findByDiscipline($discipline);
        }

        return $this->render('front/langue/espagnol.html.twig', [
            "abonnements" => $abonnements,
            "cartes" => $cartes,
        ]);
    }

    #[Route('/allemand', name: 'allemand')]
    public function allemand(AbonnementRepository $abonnementRepository, CarteRepository $carteRepository, DisciplineRepository $disciplineRepository): Response
    {
        $discipline = $disciplineRepository->findOneBy(['nom' => 'Allemand']);

        $abonnements = [];
        $cartes = [];

        if ($discipline) {
            $abonnements = $abonnementRepository->findBy([
                "discipline" => $discipline,
                "isActif" => true
            ]);
            $cartes = $carteRepository->findByDiscipline($discipline);
        }

        return $this->render('front/langue/allemand.html.twig', [
            "abonnements" => $abonnements,
            "cartes" => $cartes,
        ]);
    }
}