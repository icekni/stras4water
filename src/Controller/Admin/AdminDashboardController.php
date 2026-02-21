<?php
namespace App\Controller\Admin;

use App\Repository\DonationRepository;
use App\Repository\UserRepository;
use App\Repository\AbonnementSouscritRepository;
use App\Repository\CarteSouscriteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCUEIL')]
class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        DonationRepository $donationRepository,
        UserRepository $userRepository,
        AbonnementSouscritRepository $abonnementSouscritRepository,
        CarteSouscriteRepository $carteSouscriteRepository
    ): Response
    {
        $interval = 30;
        $donations = $donationRepository->getLastDonations($interval);

        $totalAmount = array_reduce($donations, fn($total, $don) => $total + round($don->getMontantNet(), 2), 0);

        // Tous les utilisateurs
        $users = $userRepository->findAll();

        // 🔹 Nombre total d’adhérents (utilisateurs ayant une adhésion en cours)
        $totalAdherents = 0;
        foreach ($users as $user) {
            // Ici, on considère qu’un adhérent a au moins une adhésion active
            if ($user->isAdherent()) {
                $totalAdherents++;
            }
        }

        // Comptage des utilisateurs par discipline (abonnements + cartes)
        $usersPerDiscipline = [];

        // Abonnements
        foreach ($abonnementSouscritRepository->findAllActif() as $abonnementSouscrit) {
            $discipline = $abonnementSouscrit->getAbonnement()->getDiscipline()->getNom();
            $userId = $abonnementSouscrit->getUser()->getId();
            $usersPerDiscipline[$discipline][$userId] = true; // clé unique par user
        }

        // Cartes
        foreach ($carteSouscriteRepository->findAllActif() as $carteSouscrite) {
            foreach ($carteSouscrite->getCarte()->getDisciplines() as $discipline) {
                $disciplineNom = $discipline->getNom();
                $userId = $carteSouscrite->getUser()->getId();
                $usersPerDiscipline[$disciplineNom][$userId] = true;
            }
        }

        // Transformation pour le Twig : compter les users uniques par discipline
        foreach ($usersPerDiscipline as $discipline => $userIds) {
            $usersPerDiscipline[$discipline] = count($userIds);
        }

        return $this->render('admin/index.html.twig', [
            'montantTotal' => $totalAmount,
            'donationInterval' => $interval,
            'users' => $users,
            'usersPerDiscipline' => $usersPerDiscipline,
            'totalAdherents' => $totalAdherents, // 👈 ajouté ici
        ]);
    }

}
